<?php
declare(strict_types=1);
/**
 * Dữ liệu thật từ database cho các trang danh sách (giữ nguyên layout giao diện chung).
 * Khi không tải được dữ liệu, trả null để giao diện báo lỗi, không dùng dữ liệu mẫu.
 * Khi làm chức năng thật, mỗi thành viên thay truy vấn của module mình bằng service riêng.
 */
require_once dirname(__DIR__) . '/config/database.php';

function live_all(string $sql, array $params = []): array {
    $st = db()->prepare($sql); $st->execute($params);
    return $st->fetchAll(PDO::FETCH_NUM);
}
function live_val(string $sql): int { return (int)db()->query($sql)->fetchColumn(); }
function live_available(): bool {
    static $ok = null;
    if ($ok === null) { try { db()->query('SELECT 1 FROM ThiSinh LIMIT 1'); $ok = true; } catch (Throwable $e) { $ok = false; } }
    return $ok;
}

/** Hàng dữ liệu cho module dạng bảng (cột khớp includes/module_config.php) hoặc null. */
function live_rows(string $key, ?string $studentId = null): ?array {
    if (!live_available()) return null;
    $q = [
      'candidates' => "SELECT t.maThiSinh, t.hoTen, c.tenTruongTHCS, t.trangThaiDangKyDuThi FROM ThiSinh t JOIN TruongTHCS c ON c.maTruongTHCS=t.maTruongTHCS ORDER BY t.maThiSinh",
      'schools' => "SELECT maTruongTHPT, tenTruongTHPT, k.tenKhuVuc, 'THPT' FROM TruongTHPT p JOIN KhuVuc k ON k.maKhuVuc=p.maKhuVuc
                    UNION ALL SELECT maTruongTHCS, tenTruongTHCS, k.tenKhuVuc, 'THCS' FROM TruongTHCS c JOIN KhuVuc k ON k.maKhuVuc=c.maKhuVuc ORDER BY 4,1",
      'admission-info' => "SELECT i.maThongTin, CONCAT(p.tenTruongTHPT,' · chỉ tiêu ',i.chiTieuTuyenSinh), CONCAT(d.namTuyenSinh,'–',d.namTuyenSinh+1), i.trangThaiCongBo
                    FROM ThongTinTuyenSinh i JOIN TruongTHPT p ON p.maTruongTHPT=i.maTruongTHPT JOIN DotTuyenSinh d ON d.maDot=i.maDot ORDER BY i.maThongTin",
      'aspirations' => "SELECT n.maThiSinh, MAX(CASE WHEN thuTuNguyenVong=1 THEN p.tenTruongTHPT END), MAX(CASE WHEN thuTuNguyenVong=2 THEN p.tenTruongTHPT END), COALESCE(MAX(CASE WHEN thuTuNguyenVong=3 THEN p.tenTruongTHPT END),'—')
                    FROM NguyenVong n JOIN TruongTHPT p ON p.maTruongTHPT=n.maTruongTHPT GROUP BY n.maThiSinh ORDER BY n.maThiSinh",
      'exam' => "SELECT r.maPhong, d.tenDiemThi, r.sucChua,
                    CASE WHEN x.n IS NULL THEN 'Chưa xếp phòng' WHEN x.n >= r.sucChua THEN 'Đã đầy' ELSE CONCAT('Sẵn sàng (', x.n, '/', r.sucChua, ')') END
                    FROM PhongThi r JOIN DiemThi d ON d.maDiemThi=r.maDiemThi
                    LEFT JOIN (SELECT maPhong, COUNT(*) n FROM PhanPhongThi WHERE trangThai='Đã phân' GROUP BY maPhong) x ON x.maPhong=r.maPhong ORDER BY r.maPhong",
      'councils' => "SELECT h.maHoiDongThi, h.tenHoiDongThi, h.loaiHoiDong, k.tenKyThi FROM HoiDongThi h JOIN KyThi k ON k.maKyThi=h.maKyThi ORDER BY h.maHoiDongThi",
      'staff' => "SELECT c.maCanBo, c.hoTen, COALESCE(t.tenTruongTHCS, p.tenTruongTHPT, '—'), COALESCE(h.loaiHoiDong, c.chucVu, '—')
                    FROM CanBo c LEFT JOIN TruongTHCS t ON t.maTruongTHCS=c.maTruongTHCS LEFT JOIN TruongTHPT p ON p.maTruongTHPT=c.maTruongTHPT LEFT JOIN HoiDongThi h ON h.maHoiDongThi=c.maHoiDongThi ORDER BY c.maCanBo",
      'papers' => "SELECT l.maLoBaiThi, m.tenMonThi, l.maPhong,
                    CASE WHEN SUM(b.trangThaiCham='Chưa chấm')=0 THEN 'Đã chấm xong' ELSE CONCAT('Đang chấm (', SUM(b.trangThaiCham='Chưa chấm'), ' bài chờ)') END
                    FROM LoBaiThi l JOIN MonThi m ON m.maMonThi=l.maMonThi LEFT JOIN BaiThi b ON b.maLoBaiThi=l.maLoBaiThi GROUP BY l.maLoBaiThi, m.tenMonThi, l.maPhong ORDER BY l.maLoBaiThi",
      'grading' => "SELECT b.maBaiThi, m.tenMonThi, COALESCE(FORMAT(k.diemThi,2),'—'), b.trangThaiCham FROM BaiThi b JOIN MonThi m ON m.maMonThi=b.maMonThi LEFT JOIN KetQuaChamThi k ON k.maBaiThi=b.maBaiThi ORDER BY b.maBaiThi",
      'appeals' => "SELECT y.maYeuCau, y.maThiSinh, m.tenMonThi, y.trangThai FROM YeuCauPhucKhao y JOIN BaiThi b ON b.maBaiThi=y.maBaiThi JOIN MonThi m ON m.maMonThi=b.maMonThi ORDER BY y.maYeuCau",
      'admission' => "SELECT CONCAT('XT-NV', n.thuTuNguyenVong), d.tenDot, CONCAT('NV', n.thuTuNguyenVong),
                    CASE WHEN SUM(x.trangThaiXetTuyen<>'Chưa xét')=0 THEN 'Chưa chạy' ELSE 'Đã xét' END
                    FROM NguyenVong n JOIN KetQuaXetTuyen x ON x.maNguyenVong=n.maNguyenVong CROSS JOIN DotTuyenSinh d GROUP BY n.thuTuNguyenVong, d.tenDot ORDER BY n.thuTuNguyenVong",
      'enrollment' => "SELECT t.maThiSinh, COALESCE(p.tenTruongTHPT,'—'), COALESCE(DATE_FORMAT(h.thoiGianXacNhanNhapHoc,'%d/%m/%Y'),'—'), h.trangThaiNhapHoc
                    FROM TinhTrangNhapHoc h JOIN KetQuaXetTuyen x ON x.maKetQua=h.maKetQua JOIN ThiSinh t ON t.maThiSinh=x.maThiSinh LEFT JOIN TruongTHPT p ON p.maTruongTHPT=x.maTruongTHPT ORDER BY t.maThiSinh",
      'publication' => "SELECT CONCAT('DIEM_', m.maMonThi), CONCAT('Điểm thi - ', m.tenMonThi), '—',
                    CASE WHEN SUM(k.trangThaiCongBo='Chưa công bố')=0 THEN 'Đã công bố' ELSE CONCAT('Chưa công bố (', SUM(k.trangThaiCongBo='Đã công bố'), '/', COUNT(*), ')') END
                    FROM KetQuaChamThi k JOIN BaiThi b ON b.maBaiThi=k.maBaiThi JOIN MonThi m ON m.maMonThi=b.maMonThi GROUP BY m.maMonThi, m.tenMonThi
                    UNION ALL SELECT maDanhSach, 'Kết quả trúng tuyển', trangThaiPheDuyet, trangThaiCongBo FROM DanhSachKetQuaTuyenSinh",
    ];
    if (!isset($q[$key])) return null;
    $params = [];
    if ($studentId !== null) {
        if ($key === 'appeals') { $q[$key] = str_replace(' ORDER BY y.maYeuCau', ' WHERE y.maThiSinh = ? ORDER BY y.maYeuCau', $q[$key]); $params = [$studentId]; }
        elseif ($key === 'enrollment') { $q[$key] = str_replace(' ORDER BY t.maThiSinh', ' WHERE t.maThiSinh = ? ORDER BY t.maThiSinh', $q[$key]); $params = [$studentId]; }
        elseif ($key === 'admission-info') $q[$key] = str_replace(' ORDER BY i.maThongTin', " WHERE i.trangThaiCongBo='Đã công bố' ORDER BY i.maThongTin", $q[$key]);
        else return [];
    }
    try { return live_all($q[$key], $params); } catch (Throwable $e) { error_log("live_rows($key): " . $e->getMessage()); return null; }
}

/** Số liệu cho bảng điều khiển / thống kê. */
function live_stats(): ?array {
    if (!live_available()) return null;
    try {
        $dot = db()->query("SELECT namTuyenSinh FROM DotTuyenSinh ORDER BY namTuyenSinh DESC LIMIT 1")->fetchColumn();
        $total = live_val('SELECT COUNT(*) FROM ThiSinh');
        $ok = live_val("SELECT COUNT(*) FROM ThiSinh WHERE trangThaiDangKyDuThi='Đã duyệt'");
        return [
            'nam' => $dot ? $dot . '–' . ($dot + 1) : '',
            'thiSinh' => $total, 'duyet' => $ok,
            'phanTramHoSo' => $total ? (int)round($ok / $total * 100) : 0,
            'truong' => live_val('SELECT COUNT(*) FROM TruongTHPT'),
            'nvToiDa' => live_val('SELECT COALESCE(MAX(soLuongNguyenVongToiDa),0) FROM QuyDinhNguyenVong'),
            'nguyenVong' => live_val('SELECT COUNT(*) FROM NguyenVong'),
            'daXet' => live_val("SELECT COUNT(*) FROM KetQuaXetTuyen WHERE trangThaiXetTuyen<>'Chưa xét'"),
             'baiThi' => live_val('SELECT COUNT(*) FROM BaiThi'),
            'ketQuaChamThi' => live_val('SELECT COUNT(*) FROM KetQuaChamThi'),
            'database' => (string)db()->query('SELECT DATABASE()')->fetchColumn(),
            'theoTHCS' => live_all("SELECT c.tenTruongTHCS, COUNT(t.maThiSinh) FROM TruongTHCS c JOIN ThiSinh t ON t.maTruongTHCS=c.maTruongTHCS GROUP BY c.maTruongTHCS,c.tenTruongTHCS ORDER BY c.maTruongTHCS"),
            'theoTruong' => live_all("SELECT p.tenTruongTHPT,
                (SELECT COUNT(*) FROM NguyenVong n WHERE n.maTruongTHPT=p.maTruongTHPT AND n.thuTuNguyenVong=1),
                COALESCE((SELECT SUM(i.chiTieuTuyenSinh) FROM ThongTinTuyenSinh i WHERE i.maTruongTHPT=p.maTruongTHPT
                    AND i.maDot=(SELECT maDot FROM DotTuyenSinh ORDER BY namTuyenSinh DESC, maDot DESC LIMIT 1)),0)
                FROM TruongTHPT p ORDER BY p.maTruongTHPT"),
        ];
    } catch (Throwable $e) { error_log('live_stats: ' . $e->getMessage()); return null; }
}

/** Danh sách phân công coi thi + lựa chọn cho biểu mẫu UC11. */
function live_assignments(): ?array {
    if (!live_available()) return null;
    try {
        $rows = live_all("SELECT p.maPhanCong, c.hoTen, m.tenMonThi, p.maPhong, p.vaiTro FROM PhanCongCoiThi p JOIN CanBo c ON c.maCanBo=p.maCanBo JOIN MonThi m ON m.maMonThi=p.maMonThi ORDER BY p.maPhanCong LIMIT 8");
        return [
            'list' => array_map(fn($r) => ['id'=>$r[0],'staff'=>$r[1],'subject'=>$r[2],'room'=>$r[3],'role'=>$r[4],'status'=>'Đã phân công'], $rows),
            'total' => live_val('SELECT COUNT(*) FROM PhanCongCoiThi'),
            'staff' => live_all("SELECT maCanBo, hoTen FROM CanBo WHERE maHoiDongThi='HD02' OR maHoiDongThi IS NULL ORDER BY maCanBo"),
            'subjects' => live_all('SELECT maMonThi, tenMonThi FROM MonThi ORDER BY maMonThi'),
            'rooms' => live_all('SELECT maPhong, tenPhong FROM PhongThi ORDER BY maPhong'),
        ];
    } catch (Throwable $e) { error_log('live_assignments: ' . $e->getMessage()); return null; }
}

/** Chỉ tiêu và số nguyện vọng 1 của từng trường THPT cho trang chủ. */
function live_home_schools(): ?array {
    if (!live_available()) return null;
    try {
        return live_all("SELECT p.maTruongTHPT, p.tenTruongTHPT, k.tenKhuVuc, COALESCE((SELECT SUM(i.chiTieuTuyenSinh) FROM ThongTinTuyenSinh i WHERE i.maTruongTHPT=p.maTruongTHPT AND i.maDot=(SELECT maDot FROM DotTuyenSinh ORDER BY namTuyenSinh DESC, maDot DESC LIMIT 1)),0)
                         FROM TruongTHPT p JOIN KhuVuc k ON k.maKhuVuc=p.maKhuVuc ORDER BY p.maTruongTHPT");
    } catch (Throwable $e) { return null; }
}

/** Personal dashboard: never query aggregate system statistics for a student. */
function live_student_dashboard(string $studentId): ?array {
    try {
        $st = db()->prepare("SELECT t.maThiSinh,t.maHoSo,t.hoTen,t.ngaySinh,t.soBaoDanh,t.trangThaiDangKyDuThi,c.tenTruongTHCS FROM ThiSinh t JOIN TruongTHCS c ON c.maTruongTHCS=t.maTruongTHCS WHERE t.maThiSinh=?");
        $st->execute([$studentId]);
        $profile = $st->fetch();
        if (!$profile) return null;
        $st = db()->prepare("SELECT n.thuTuNguyenVong,p.tenTruongTHPT FROM NguyenVong n JOIN TruongTHPT p ON p.maTruongTHPT=n.maTruongTHPT WHERE n.maThiSinh=? ORDER BY n.thuTuNguyenVong");
        $st->execute([$studentId]);
        return ['profile'=>$profile, 'wishes'=>$st->fetchAll()];
    } catch (Throwable $e) { error_log('student_dashboard: '.$e->getMessage()); return null; }
}

/** Published admission notices for the student's read-only information page. */
function live_student_admission_info(string $query = ''): ?array {
    try {
        $sql = "SELECT i.maThongTin,i.chiTieuTuyenSinh,i.heDaoTao,i.phuongThucTuyenSinh,i.hocPhi,i.noiDungChiTiet,
                       p.tenTruongTHPT,k.tenKhuVuc,d.tenDot,d.namTuyenSinh
                FROM ThongTinTuyenSinh i JOIN TruongTHPT p ON p.maTruongTHPT=i.maTruongTHPT
                JOIN KhuVuc k ON k.maKhuVuc=p.maKhuVuc JOIN DotTuyenSinh d ON d.maDot=i.maDot
                WHERE i.trangThaiCongBo='Đã công bố'";
        $params = [];
        if ($query !== '') {
            $sql .= " AND (p.tenTruongTHPT LIKE ? ESCAPE '!' OR k.tenKhuVuc LIKE ? ESCAPE '!')";
            $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $query) . '%';
            $params = [$like, $like];
        }
        $st = db()->prepare($sql . ' ORDER BY d.namTuyenSinh DESC,p.maTruongTHPT,i.maThongTin');
        $st->execute($params);
        return $st->fetchAll();
    } catch (Throwable $e) { error_log('student_admission_info: '.$e->getMessage()); return null; }
}
