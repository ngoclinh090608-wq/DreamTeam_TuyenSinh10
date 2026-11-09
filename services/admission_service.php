<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/business_exception.php';
require_once __DIR__ . '/appeal_service.php';

/** UC17: quy tắc đồ án, không dùng thay quy chế tuyển sinh chính thức. */
function uc17_plan(array $data, string $examId, array $options): array {
    $index = static fn(array $rows, string $key): array => array_column($rows, null, $key);
    $exams = $index($data['KyThi'], 'maKyThi');
    $exam = $exams[$examId] ?? null;
    if (!$exam) throw new BusinessException('Vui lòng chọn kỳ thi hợp lệ.');
    $round = $index($data['DotTuyenSinh'], 'maDot')[$exam['maDot']];
    if (strtotime($exam['thoiGianKetThuc']) > time()) throw new BusinessException('Kỳ thi chưa kết thúc.');
    if (empty($options['closed']) || empty($options['appealsEnded'])) throw new BusinessException('Dữ liệu điểm thi chưa được chốt sau phúc khảo. Không thể tiến hành xét tuyển!');
    $weights = [];
    foreach (['TOAN', 'VAN', 'NN'] as $subject) {
        $value = $options['weights'][$subject] ?? null;
        if (!is_numeric($value) || (float)$value <= 0 || (float)$value > 10 || abs((float)$value * 100 - round((float)$value * 100)) > 0.00001) throw new BusinessException('Hệ số môn phải lớn hơn 0, tối đa 10 và có tối đa hai chữ số thập phân.');
        $weights[$subject] = (float)$value;
    }
    $quotas = [];
    foreach ($data['ThongTinTuyenSinh'] as $info) {
        if ($info['maDot'] !== $round['maDot']) continue;
        if ($info['trangThaiCongBo'] !== 'Đã công bố') throw new BusinessException('Chỉ tiêu của đợt chưa được công bố đầy đủ.');
        if (isset($quotas[$info['maTruongTHPT']])) throw new BusinessException('Có nhiều chỉ tiêu cho cùng trường; cần quy định phân hệ trước khi xét.');
        if ($info['phuongThucTuyenSinh'] !== 'Thi tuyển') throw new BusinessException('UC17 hiện chỉ hỗ trợ phương thức Thi tuyển.');
        $quotas[$info['maTruongTHPT']] = (int)$info['chiTieuTuyenSinh'];
    }
    if (!$quotas) throw new BusinessException('Chưa có chỉ tiêu cho đợt tuyển sinh.');
    $allocations = [];
    foreach ($quotas as $school => $total) {
        $values = $options['allocation'][$school] ?? [];
        foreach ([1,2,3] as $priority) {
            $value = $values[$priority] ?? null;
            if (filter_var($value, FILTER_VALIDATE_INT) === false || (int)$value < 0) throw new BusinessException('Chỉ tiêu NV1–NV3 phải là số nguyên không âm.');
            $allocations[$school][$priority] = (int)$value;
        }
        if (array_sum($allocations[$school]) !== $total) throw new BusinessException('Tổng chỉ tiêu NV1–NV3 phải bằng chỉ tiêu được giao của ' . $school . '.');
    }
    $regulations = $index($data['QuyDinhNguyenVong'], 'maQuyDinh');
    $students = $index($data['ThiSinh'], 'maThiSinh');
    $wishes = [];
    foreach ($data['NguyenVong'] as $wish) {
        $regulation = $regulations[$wish['maQuyDinh']];
        if ((int)$regulation['namTuyenSinh'] !== (int)$round['namTuyenSinh']) continue;
        if (strtotime($regulation['thoiGianKetThuc']) >= time()) throw new BusinessException('Chưa hết thời hạn đăng ký nguyện vọng.');
        if ((int)$wish['thuTuNguyenVong'] > (int)$regulation['soLuongNguyenVongToiDa']) throw new BusinessException('Nguyện vọng vượt số lượng cho phép.');
        if (($wish['lopChuyen'] ?? 'Thường') !== 'Thường') throw new BusinessException('Có nguyện vọng chuyên; cần quy định điểm môn chuyên và chỉ tiêu riêng trước khi xét.');
        if (!array_key_exists($wish['maTruongTHPT'], $quotas)) throw new BusinessException('Có trường nguyện vọng chưa được giao chỉ tiêu trong đợt.');
        $wishes[] = $wish;
    }
    $byStudent = [];
    foreach ($wishes as $wish) $byStudent[$wish['maThiSinh']][] = $wish;
    foreach ($byStudent as $id => $studentWishes) {
        $orders = array_map('intval', array_column($studentWishes, 'thuTuNguyenVong')); sort($orders);
        if ($orders !== range(1,count($orders)) || count($orders) > 3 || count(array_unique(array_column($studentWishes,'maTruongTHPT'))) !== count($orders)) throw new BusinessException('Nguyện vọng không hợp lệ của ' . $id . ': cần NV1–NV3 liên tiếp và không lặp trường.');
    }
    if (!$wishes) throw new BusinessException('Không có nguyện vọng trong năm tuyển sinh.');
    foreach ($data['KyThi'] as $other) if ($other['maDot'] === $round['maDot'] && $other['maKyThi'] !== $examId) throw new BusinessException('Đợt có nhiều kỳ thi; cần cấu hình kỳ thi chính thức trước khi xét.');
    $grades = $index($data['KetQuaChamThi'], 'maBaiThi');
    $subjects = $index($data['MonThi'], 'maMonThi');
    $papers = []; $required = [];
    foreach ($data['BaiThi'] as $paper) {
        if ($paper['maKyThi'] !== $examId) continue;
        $publishedAt = $grades[$paper['maBaiThi']]['thoiDiemCongBo'] ?? null;
        $window = appeal_window($publishedAt);
        if (!$window['end']) throw new BusinessException('Chưa xác định được thời điểm công bố điểm để kiểm tra hạn phúc khảo.');
        if (new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh')) < $window['end']) throw new BusinessException('Chưa hết thời hạn nhận yêu cầu phúc khảo 7 ngày.');
        $required[$paper['maMonThi']] = true;
        $papers[$paper['maThiSinh']][$paper['maMonThi']] = $paper;
    }
    if (!$required) throw new BusinessException('Kỳ thi chưa có bài thi.');
    foreach (['TOAN', 'VAN', 'NN'] as $subject) if (!isset($required[$subject])) throw new BusinessException('Chưa đủ dữ liệu để tính điểm xét tuyển: cần Toán, Ngữ văn, Ngoại ngữ.');
    if (count($required) !== 3) throw new BusinessException('Kỳ thi có môn chuyên hoặc môn chưa cấu hình; chưa thể xét hệ thường.');
    $completedAppeals = array_column($data['KetQuaPhucKhao'], null, 'maYeuCau');
    $appeals = [];
    $paperIds = [];
    foreach ($papers as $studentPapers) foreach ($studentPapers as $paper) $paperIds[$paper['maBaiThi']] = true;
    foreach ($data['YeuCauPhucKhao'] as $request) {
        if (!isset($paperIds[$request['maBaiThi']])) continue;
        if (!isset($completedAppeals[$request['maYeuCau']])) throw new BusinessException('Giai đoạn phúc khảo chưa hoàn thành: còn yêu cầu chưa có kết quả.');
        $appeals[$request['maBaiThi']] = $completedAppeals[$request['maYeuCau']];
    }
    foreach ($papers as $id => $_) if (!isset($byStudent[$id]) && (int)$students[$id]['dieuKienDuThi'] && $students[$id]['trangThaiDangKyDuThi'] === 'Đã duyệt') throw new BusinessException('Thí sinh ' . $id . ' chưa có nguyện vọng hợp lệ trong năm tuyển sinh.');
    $scores = []; $excluded = [];
    foreach (array_unique(array_column($wishes, 'maThiSinh')) as $id) {
        $student = $students[$id];
        if (!(int)$student['dieuKienDuThi'] || $student['trangThaiDangKyDuThi'] !== 'Đã duyệt') {
            $excluded[$id] = 'Hồ sơ chưa duyệt hoặc không đủ điều kiện'; continue;
        }
        $total = 0;
        foreach ($required as $subject => $_) {
            $paper = $papers[$id][$subject] ?? null;
            $grade = $paper ? ($grades[$paper['maBaiThi']] ?? null) : null;
            if (!$paper || !$grade || $paper['trangThaiCham'] !== 'Đã chấm') {
                throw new BusinessException('Chưa đủ điểm thi của thí sinh ' . $id . '; chưa thể xét tuyển.');
            }
            if ($grade['trangThaiCongBo'] !== 'Đã công bố') throw new BusinessException('Điểm thi chưa được công bố đầy đủ.');
            $effective = $appeals[$paper['maBaiThi']]['diemSauPhucKhao'] ?? $grade['diemThi'];
            $points = (int)round((float)$effective * 100);
            if ($points < 0 || $points > (int)round((float)$subjects[$subject]['thangDiem'] * 100)) throw new BusinessException('Có điểm thi ngoài thang điểm.');
            $total += $points * $weights[$subject];
        }
        foreach (['priority', 'encouragement'] as $kind) {
            $bonus = $options['bonuses'][$id][$kind] ?? 0;
            if (!is_numeric($bonus) || (float)$bonus < 0 || (float)$bonus > 100 || abs((float)$bonus*100-round((float)$bonus*100)) > 0.00001) throw new BusinessException('Điểm ưu tiên/khuyến khích không hợp lệ của ' . $id . '.');
            $total += (float)$bonus * 100;
        }
        $total = (int)round($total);
        if ($total > 99999) throw new BusinessException('Tổng điểm vượt khả năng lưu trữ của hệ thống.');
        $scores[$id] = $total;
    }
    foreach (array_keys($options['bonuses'] ?? []) as $id) if (!in_array($id, array_column($wishes, 'maThiSinh'), true)) throw new BusinessException('Mã thí sinh điểm cộng không thuộc danh sách xét tuyển: ' . $id);
    $accepted = []; $cutoffs = []; $warnings = []; $unresolved = false; $used = array_fill_keys(array_keys($quotas), 0);
    for ($priority = 1; $priority <= 3; $priority++) {
        foreach ($quotas as $school => $quota) {
            $pool = array_values(array_filter($wishes, static fn($w) => (int)$w['thuTuNguyenVong'] === $priority && $w['maTruongTHPT'] === $school && !isset($accepted[$w['maThiSinh']]) && !isset($excluded[$w['maThiSinh']])));
            usort($pool, static fn($a, $b) => ($scores[$b['maThiSinh']] <=> $scores[$a['maThiSinh']]) ?: strcmp($a['maThiSinh'], $b['maThiSinh']));
            $remaining = $allocations[$school][$priority];
            $take = $remaining;
            if ($remaining > 0 && count($pool) > $remaining && $scores[$pool[$remaining-1]['maThiSinh']] === $scores[$pool[$remaining]['maThiSinh']]) {
                $boundary = $scores[$pool[$remaining-1]['maThiSinh']];
                $choice = $options['ties'][$school][$priority] ?? '';
                if (!in_array($choice, ['all', 'higher'], true)) $unresolved = true;
                $take = count(array_filter($pool, static fn($w) => $choice === 'all' ? $scores[$w['maThiSinh']] >= $boundary : $scores[$w['maThiSinh']] > $boundary));
                $warnings[] = $school . ' NV' . $priority . ': bằng điểm ở ' . number_format($boundary/100, 2) . '; ' . ($choice === 'all' ? 'nhận toàn bộ nhóm bằng điểm' : ($choice === 'higher' ? 'nâng điểm chuẩn, tuyển thiếu' : 'cần chọn phương án A/B'));

            }
            $selected = array_slice($pool, 0, $take);
            $cutoffs[$school][$priority] = ['count' => count($selected), 'score' => $selected ? $scores[$selected[count($selected)-1]['maThiSinh']]/100 : null];
            foreach ($selected as $wish) {
                $accepted[$wish['maThiSinh']] = $wish['maNguyenVong']; $used[$school]++;
            }
        }
    }
    $rows = [];
    foreach ($wishes as $wish) {
        $id = $wish['maThiSinh'];
        $won = ($accepted[$id] ?? null) === $wish['maNguyenVong'];
        $rows[] = $wish + ['hoTen' => $students[$id]['hoTen'], 'diem' => isset($scores[$id]) ? $scores[$id]/100 : null,
            'trangThai' => $won ? 'Trúng tuyển' : 'Không trúng tuyển',
            'lyDo' => $won ? 'Đạt chỉ tiêu NV' . $wish['thuTuNguyenVong'] : ($excluded[$id] ?? (isset($accepted[$id]) ? 'Đã trúng tuyển nguyện vọng khác' : 'Không đủ thứ hạng trong chỉ tiêu'))];
    }
    return ['exam' => $exam, 'round' => $round, 'rows' => $rows, 'quotas' => $quotas, 'used' => $used, 'accepted' => count($accepted), 'cutoffs' => $cutoffs, 'warnings' => $warnings, 'unresolved' => $unresolved];
}

/** Snapshot nhất quán. Khi ghi, khóa theo thứ tự cố định để không xét trên điểm/chỉ tiêu đang đổi. */
function uc17_snapshot(PDO $pdo, bool $lock = false): array {
    $keys = ['DotTuyenSinh'=>'maDot','KyThi'=>'maKyThi','ThongTinTuyenSinh'=>'maThongTin',
        'QuyDinhNguyenVong'=>'maQuyDinh','ThiSinh'=>'maThiSinh','NguyenVong'=>'maNguyenVong',
        'MonThi'=>'maMonThi','BaiThi'=>'maBaiThi','KetQuaChamThi'=>'maKetQuaChamThi','YeuCauPhucKhao'=>'maYeuCau','KetQuaPhucKhao'=>'maKetQua',
        'DanhSachKetQuaTuyenSinh'=>'maDanhSach','KetQuaXetTuyen'=>'maKetQua','KetQuaTuyenSinh'=>'maKetQua',
        'TinhTrangNhapHoc'=>'maTinhTrang'];
    $data = [];
    $publicationColumn = $pdo->query("SHOW COLUMNS FROM KetQuaChamThi LIKE 'thoiDiemCongBo'")->fetch() ? 'thoiDiemCongBo' : 'NULL AS thoiDiemCongBo';
    foreach ($keys as $table => $key) {
        // XAMPP exports may spell this column DiemThi. Alias the PDO key explicitly.
        $columns = $table === 'KetQuaChamThi'
            ? 'maKetQuaChamThi, diemThi AS diemThi, nguoiNhap, thoiDiemNhap, trangThaiCongBo, maBaiThi, ' . $publicationColumn
            : '*';
        $data[$table] = $pdo->query("SELECT $columns FROM $table ORDER BY $key" . ($lock ? ' FOR UPDATE' : ''))->fetchAll();
    }
    return $data;
}

function uc17_fingerprint(array $data): string { return hash('sha256', serialize($data)); }

function uc17_preview(string $exam, array $options): array {
    $pdo = db(); $pdo->beginTransaction();
    try {
        $data = uc17_snapshot($pdo); $plan = uc17_plan($data, $exam, $options);
        $pdo->commit();
        return $plan + ['fingerprint' => uc17_fingerprint($data)];
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}

function uc17_save(array $actor, string $exam, string $fingerprint, array $options): string {
    if (($actor['vaiTro'] ?? '') !== 'HD_TUYEN_SINH') throw new BusinessException('Chỉ Hội đồng tuyển sinh được chạy xét tuyển.');
    $pdo = db(); $pdo->beginTransaction();
    try {
        $data = uc17_snapshot($pdo, true);
        if (!hash_equals(uc17_fingerprint($data), $fingerprint)) throw new BusinessException('Dữ liệu đã thay đổi. Vui lòng xem trước lại trước khi lưu.');
        $plan = uc17_plan($data, $exam, $options);
        if ($plan['unresolved']) throw new BusinessException('Cần chọn phương án cho tất cả trường hợp bằng điểm và xem trước lại.');
        $listId = 'XT17_' . substr(hash('sha256', $plan['round']['maDot']), 0, 15);
        $wishIds = array_fill_keys(array_column($plan['rows'], 'maNguyenVong'), true);
        $existing = array_column($data['KetQuaXetTuyen'], null, 'maNguyenVong');
        $resultIds = [];
        foreach ($existing as $wish => $row) if (isset($wishIds[$wish])) $resultIds[$row['maKetQua']] = true;
        foreach ($data['DanhSachKetQuaTuyenSinh'] as $list) {
            if ($list['maDot'] === $plan['round']['maDot'] && ($list['trangThaiPheDuyet'] === 'Đã phê duyệt' || $list['trangThaiCongBo'] === 'Đã công bố')) throw new BusinessException('Đợt đã có danh sách được phê duyệt/công bố; không được xét lại.');
        }
        foreach ($data['KetQuaTuyenSinh'] as $link) {
            if (isset($resultIds[$link['maKetQuaXetTuyen']]) && $link['maDanhSach'] !== $listId) throw new BusinessException('Kết quả đang thuộc danh sách khác; không được ghi đè.');
        }
        foreach ($data['TinhTrangNhapHoc'] as $enrollment) {
            if (isset($resultIds[$enrollment['maKetQua']])) throw new BusinessException('Đã có hồ sơ nhập học liên quan; không được xét lại.');
        }
        $stmt = $pdo->prepare("INSERT INTO DanhSachKetQuaTuyenSinh (maDanhSach,namTuyenSinh,maDot) VALUES (?,?,?) ON DUPLICATE KEY UPDATE maDanhSach=VALUES(maDanhSach)");
        $stmt->execute([$listId, $plan['round']['namTuyenSinh'], $plan['round']['maDot']]);
        // Các liên kết cũ do UC17 tạo được xây lại; không xóa kết quả hay danh sách của chức năng khác.
        $pdo->prepare('DELETE FROM KetQuaTuyenSinh WHERE maDanhSach=?')->execute([$listId]);
        $update = $pdo->prepare('UPDATE KetQuaXetTuyen SET diemXetTuyen=?,trangThaiXetTuyen=?,maTruongTHPT=? WHERE maKetQua=?');
        $insert = $pdo->prepare('INSERT INTO KetQuaXetTuyen (diemXetTuyen,trangThaiXetTuyen,maTruongTHPT,maKetQua,maThiSinh,maNguyenVong) VALUES (?,?,?,?,?,?)');
        $link = $pdo->prepare('INSERT INTO KetQuaTuyenSinh (maKetQua,maDanhSach,maKetQuaXetTuyen) VALUES (?,?,?)');
        foreach ($plan['rows'] as $row) {
            $resultId = $existing[$row['maNguyenVong']]['maKetQua'] ?? ('X17_' . bin2hex(random_bytes(8)));
            $values = [$row['diem'], $row['trangThai'], $row['trangThai'] === 'Trúng tuyển' ? $row['maTruongTHPT'] : null, $resultId];
            if (isset($existing[$row['maNguyenVong']])) $update->execute($values);
            else $insert->execute([...$values, $row['maThiSinh'], $row['maNguyenVong']]);
            $link->execute(['L17_' . bin2hex(random_bytes(8)), $listId, $resultId]);
        }
        $statuses = $pdo->prepare('UPDATE NguyenVong SET trangThaiNguyenVong=? WHERE maNguyenVong=?');
        foreach ($plan['rows'] as $row) $statuses->execute([$row['trangThai'] === 'Trúng tuyển' ? 'Trúng tuyển NV' . $row['thuTuNguyenVong'] : 'Không trúng tuyển', $row['maNguyenVong']]);
        $audit = $pdo->prepare('INSERT INTO XetTuyenLanChay (maLan,maKyThi,maDanhSach,nguoiThucHien,thongSo,tongTrungTuyen) VALUES (?,?,?,?,?,?)');
        $audit->execute(['RUN_' . bin2hex(random_bytes(8)), $exam, $listId, (string)($actor['maTaiKhoan'] ?? $actor['tenDangNhap'] ?? ''), json_encode(['options'=>$options,'cutoffs'=>$plan['cutoffs'],'fingerprint'=>$fingerprint], JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR), $plan['accepted']]);
        $pdo->commit(); return $listId;
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
