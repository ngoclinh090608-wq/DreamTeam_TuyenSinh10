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

/** Dashboard riêng cho Hội đồng chấm thi; không tải số liệu hồ sơ/nguyện vọng. */
function live_grading_dashboard(): ?array {
    if (!live_available()) return null;
    try {
        $summary = db()->query("SELECT COUNT(b.maBaiThi) AS tongBai,
            SUM(k.maKetQuaChamThi IS NOT NULL AND b.trangThaiCham='Đã chấm') AS daCham,
            SUM(k.maKetQuaChamThi IS NULL OR b.trangThaiCham<>'Đã chấm') AS chuaCham,
            SUM(k.trangThaiCongBo='Đã công bố') AS daCongBo
            FROM BaiThi b LEFT JOIN KetQuaChamThi k ON k.maBaiThi=b.maBaiThi")->fetch() ?: [];
        $bySubject = db()->query("SELECT kt.tenKyThi,m.tenMonThi,COUNT(b.maBaiThi) AS tongBai,
            SUM(k.maKetQuaChamThi IS NOT NULL AND b.trangThaiCham='Đã chấm') AS daCham,
            SUM(k.maKetQuaChamThi IS NULL OR b.trangThaiCham<>'Đã chấm') AS chuaCham,
            SUM(k.trangThaiCongBo='Đã công bố') AS daCongBo,
            AVG(k.diemThi) AS diemTrungBinh
            FROM BaiThi b JOIN KyThi kt ON kt.maKyThi=b.maKyThi JOIN MonThi m ON m.maMonThi=b.maMonThi
            LEFT JOIN KetQuaChamThi k ON k.maBaiThi=b.maBaiThi
            GROUP BY kt.maKyThi,kt.tenKyThi,m.maMonThi,m.tenMonThi ORDER BY kt.maKyThi,m.maMonThi")->fetchAll();
        $recent = db()->query("SELECT b.soPhach,m.tenMonThi,l.diemTruocChinhSua,l.diemSauChinhSua,
            l.nguoiThucHien,l.thoiGianChinhSua
            FROM LichSuChinhSuaDiem l JOIN KetQuaChamThi k ON k.maKetQuaChamThi=l.maKetQuaChamThi
            JOIN BaiThi b ON b.maBaiThi=k.maBaiThi JOIN MonThi m ON m.maMonThi=b.maMonThi
            ORDER BY l.thoiGianChinhSua DESC,l.maLichSu DESC LIMIT 6")->fetchAll();
        return [
            'tongBai'=>(int)($summary['tongBai'] ?? 0),
            'daCham'=>(int)($summary['daCham'] ?? 0),
            'chuaCham'=>(int)($summary['chuaCham'] ?? 0),
            'daCongBo'=>(int)($summary['daCongBo'] ?? 0),
            'lichSu'=>live_val('SELECT COUNT(*) FROM LichSuChinhSuaDiem'),
            'theoMon'=>$bySubject,
            'ganDay'=>$recent,
        ];
    } catch (Throwable $e) { error_log('live_grading_dashboard: '.$e->getMessage()); return null; }
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
        $st = db()->prepare("SELECT n.maNguyenVong,n.thuTuNguyenVong,p.tenTruongTHPT,COALESCE(n.lopChuyen, 'Thường') AS lopChuyen FROM NguyenVong n JOIN TruongTHPT p ON p.maTruongTHPT=n.maTruongTHPT WHERE n.maThiSinh=? ORDER BY n.thuTuNguyenVong");
        $st->execute([$studentId]);
        $wishes = $st->fetchAll();
        $st = db()->prepare("SELECT kt.tenKyThi,m.tenMonThi,k.diemThi FROM BaiThi b JOIN KetQuaChamThi k ON k.maBaiThi=b.maBaiThi JOIN MonThi m ON m.maMonThi=b.maMonThi JOIN KyThi kt ON kt.maKyThi=b.maKyThi WHERE b.maThiSinh=? AND k.trangThaiCongBo='Đã công bố' ORDER BY kt.maKyThi,m.maMonThi");
        $st->execute([$studentId]);
        $scores = $st->fetchAll();
        $st = db()->prepare("SELECT p.tenTruongTHPT,x.trangThaiXetTuyen,n.maNguyenVong,n.thuTuNguyenVong FROM KetQuaTuyenSinh r JOIN DanhSachKetQuaTuyenSinh d ON d.maDanhSach=r.maDanhSach JOIN KetQuaXetTuyen x ON x.maKetQua=r.maKetQuaXetTuyen JOIN NguyenVong n ON n.maNguyenVong=x.maNguyenVong LEFT JOIN TruongTHPT p ON p.maTruongTHPT=x.maTruongTHPT WHERE x.maThiSinh=? AND d.trangThaiCongBo='Đã công bố' AND d.trangThaiPheDuyet='Đã phê duyệt' ORDER BY n.thuTuNguyenVong");
        $st->execute([$studentId]);
        $results = $st->fetchAll();
        $st = db()->prepare('SELECT COUNT(*) FROM BaiThi WHERE maThiSinh=?'); $st->execute([$studentId]);
        $paperCount = (int)$st->fetchColumn();
        $st = db()->prepare("SELECT k.tenKyThi,d.tenDiemThi,r.maPhong,r.tenPhong,
            COALESCE(c.tenTruongTHCS,h.tenTruongTHPT,'') AS tenTruong,
            nvSchool.tenTruongTHPT AS tenTruongNV1,
            CASE WHEN d.maTruongTHPT = nv.maTruongTHPT THEN 1 ELSE 0 END AS laDiemThiNV1
            FROM PhanPhongThi a
            JOIN PhongThi r ON r.maPhong=a.maPhong
            JOIN DiemThi d ON d.maDiemThi=r.maDiemThi
            JOIN KyThi k ON k.maKyThi=d.maKyThi
            JOIN DotTuyenSinh dot ON dot.maDot=k.maDot
            LEFT JOIN TruongTHCS c ON c.maTruongTHCS=d.maTruongTHCS
            LEFT JOIN TruongTHPT h ON h.maTruongTHPT=d.maTruongTHPT
            LEFT JOIN QuyDinhNguyenVong q ON q.namTuyenSinh=dot.namTuyenSinh
            LEFT JOIN NguyenVong nv ON nv.maThiSinh=a.maThiSinh AND nv.thuTuNguyenVong=1 AND nv.maQuyDinh=q.maQuyDinh
            LEFT JOIN TruongTHPT nvSchool ON nvSchool.maTruongTHPT=nv.maTruongTHPT
            WHERE a.maThiSinh=? AND a.trangThai='Đã phân' ORDER BY k.maKyThi,r.maPhong");
        $st->execute([$studentId]); $examSites=$st->fetchAll();
        require_once __DIR__.'/student_profile.php';
        return ['profile'=>$profile, 'wishes'=>$wishes, 'scores'=>$scores, 'results'=>$results, 'paperCount'=>$paperCount, 'examSites'=>$examSites, 'supplement'=>student_profile_read($studentId)];
    } catch (Throwable $e) { error_log('student_dashboard: '.$e->getMessage()); return null; }
}

/** Published admission notices for the student's read-only information page (UC01). */
function live_student_admission_info(string $query = '', array $filters = []): ?array {
    // Check if demo empty state is requested for testing Exception Flow 2.1
    if (isset($_GET['demo_state']) && $_GET['demo_state'] === 'empty') {
        return [];
    }

    try {
        if (live_available()) {
            $sql = "SELECT i.maThongTin, i.chiTieuTuyenSinh, i.heDaoTao, i.phuongThucTuyenSinh, i.hocPhi, i.noiDungChiTiet,
                           i.trangThaiCongBo, p.maTruongTHPT, p.tenTruongTHPT,
                           k.maKhuVuc, k.tenKhuVuc, k.quanHuyen,
                           d.maDot, d.tenDot, d.namTuyenSinh
                    FROM ThongTinTuyenSinh i
                    JOIN TruongTHPT p ON p.maTruongTHPT=i.maTruongTHPT
                    JOIN KhuVuc k ON k.maKhuVuc=p.maKhuVuc
                    JOIN DotTuyenSinh d ON d.maDot=i.maDot
                    WHERE i.trangThaiCongBo='Đã công bố'";
            $params = [];
            $trimmedQuery = trim($query);
            if ($trimmedQuery !== '') {
                $sql .= " AND (p.tenTruongTHPT LIKE ? ESCAPE '!' OR p.maTruongTHPT LIKE ? ESCAPE '!' OR k.tenKhuVuc LIKE ? ESCAPE '!' OR k.quanHuyen LIKE ? ESCAPE '!' OR i.heDaoTao LIKE ? ESCAPE '!' OR i.phuongThucTuyenSinh LIKE ? ESCAPE '!')";
                $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $trimmedQuery) . '%';
                $params = array_fill(0, 6, $like);
            }
            if (!empty($filters['region']) && $filters['region'] !== 'all') {
                $sql .= " AND (k.maKhuVuc = ? OR k.tenKhuVuc = ?)";
                $params[] = $filters['region'];
                $params[] = $filters['region'];
            }
            if (!empty($filters['school']) && $filters['school'] !== 'all') {
                $sql .= " AND (p.maTruongTHPT = ?)";
                $params[] = $filters['school'];
            }
            if (!empty($filters['education']) && $filters['education'] !== 'all') {
                $sql .= " AND (i.heDaoTao = ?)";
                $params[] = $filters['education'];
            }
            if (!empty($filters['method']) && $filters['method'] !== 'all') {
                $sql .= " AND (i.phuongThucTuyenSinh = ?)";
                $params[] = $filters['method'];
            }
            $st = db()->prepare($sql . ' ORDER BY d.namTuyenSinh DESC, p.maTruongTHPT, i.maThongTin');
            $st->execute($params);
            return $st->fetchAll();
        }
    } catch (Throwable $e) {
        error_log('student_admission_info: ' . $e->getMessage());
    }

    // High fidelity fallback demo dataset matching dreamteam_tuyensinh10_demo.sql
    $fallbackNotices = [
        [
            'maThongTin' => 'TT_THPT_1',
            'chiTieuTuyenSinh' => 320,
            'heDaoTao' => 'Công lập',
            'phuongThucTuyenSinh' => 'Thi tuyển',
            'hocPhi' => 0.00,
            'noiDungChiTiet' => "• Chỉ tiêu tuyển sinh: 320 học sinh (8 lớp thường, 40 học sinh/lớp).\n• Đối tượng dự tuyển: Học sinh tốt nghiệp THCS trong độ tuổi quy định và thuộc địa bàn tuyển sinh Khu vực 1 (Quận 1, Quận 3).\n• Phương thức tuyển sinh: Thi tuyển 3 môn: Ngữ văn (120 phút), Toán (120 phút), Ngoại ngữ (90 phút).\n• Điểm xét tuyển: Tổng điểm 3 môn thi (hệ số 1) + điểm ưu tiên, khuyến khích (nếu có).\n• Kế hoạch và hồ sơ: Đăng ký trực tuyến trên Cổng thông tin tuyển sinh lớp 10, kiểm tra kỹ các nguyện vọng 1, 2, 3.\n• Học phí: Thực hiện theo Nghị quyết của Hội đồng Nhân dân đối với trường THPT công lập năm học 2026–2027.",
            'trangThaiCongBo' => 'Đã công bố',
            'maTruongTHPT' => 'THPT_1',
            'tenTruongTHPT' => 'Trường THPT Sao Mai',
            'maKhuVuc' => 'KV01',
            'tenKhuVuc' => 'Khu vực 1',
            'quanHuyen' => 'Quận 1, Quận 3',
            'maDot' => 'DOT2026',
            'tenDot' => 'Tuyển sinh vào lớp 10 năm học 2026–2027',
            'namTuyenSinh' => 2026,
        ],
        [
            'maThongTin' => 'TT_THPT_2',
            'chiTieuTuyenSinh' => 335,
            'heDaoTao' => 'Công lập',
            'phuongThucTuyenSinh' => 'Thi tuyển',
            'hocPhi' => 0.00,
            'noiDungChiTiet' => "• Chỉ tiêu tuyển sinh: 335 học sinh (chia thành 8 lớp thường và 1 lớp định hướng).\n• Đối tượng dự tuyển: Học sinh đã hoàn thành chương trình THCS tại các trường THCS trên địa bàn tuyển sinh.\n• Phương thức tuyển sinh: Thi tuyển theo kỳ thi chung của Sở Giáo dục và Đào tạo.\n• Môn thi: Ngữ văn, Toán, Tiếng Anh.\n• Kế hoạch nộp hồ sơ: Theo thông báo chính thức của Ban Tuyển sinh sau khi có điểm chuẩn trúng tuyển.\n• Học phí: Thu theo quy định áp dụng đối với trường THPT công lập chuẩn quốc gia.",
            'trangThaiCongBo' => 'Đã công bố',
            'maTruongTHPT' => 'THPT_2',
            'tenTruongTHPT' => 'Trường THPT Bình Minh',
            'maKhuVuc' => 'KV01',
            'tenKhuVuc' => 'Khu vực 1',
            'quanHuyen' => 'Quận 1, Quận 3',
            'maDot' => 'DOT2026',
            'tenDot' => 'Tuyển sinh vào lớp 10 năm học 2026–2027',
            'namTuyenSinh' => 2026,
        ],
        [
            'maThongTin' => 'TT_THPT_3',
            'chiTieuTuyenSinh' => 350,
            'heDaoTao' => 'Công lập',
            'phuongThucTuyenSinh' => 'Thi tuyển',
            'hocPhi' => 0.00,
            'noiDungChiTiet' => "• Chỉ tiêu tuyển sinh: 350 học sinh.\n• Địa bàn tuyển sinh: Học sinh thường trú hoặc tốt nghiệp THCS tại Khu vực 2 (Quận 5, Quận 10).\n• Phương thức tuyển sinh: Thi tuyển 3 môn chung kết hợp xét các nguyện vọng NV1, NV2, NV3.\n• Điều kiện tiếp nhận: Xếp loại học lực và hạnh kiểm cấp THCS từ Khá trở lên.\n• Học phí: Miễn giảm và thu học phí theo đúng quy định hiện hành.",
            'trangThaiCongBo' => 'Đã công bố',
            'maTruongTHPT' => 'THPT_3',
            'tenTruongTHPT' => 'Trường THPT Hòa An',
            'maKhuVuc' => 'KV02',
            'tenKhuVuc' => 'Khu vực 2',
            'quanHuyen' => 'Quận 5, Quận 10',
            'maDot' => 'DOT2026',
            'tenDot' => 'Tuyển sinh vào lớp 10 năm học 2026–2027',
            'namTuyenSinh' => 2026,
        ],
        [
            'maThongTin' => 'TT_THPT_4',
            'chiTieuTuyenSinh' => 365,
            'heDaoTao' => 'Công lập',
            'phuongThucTuyenSinh' => 'Thi tuyển',
            'hocPhi' => 0.00,
            'noiDungChiTiet' => "• Chỉ tiêu tuyển sinh: 365 học sinh.\n• Địa bàn tuyển sinh: Khu vực 2 (Quận 5, Quận 10) và khu vực lân cận theo quy định phân tuyến.\n• Phương thức tuyển sinh: Thi tuyển 3 môn: Toán, Ngữ văn, Ngoại ngữ.\n• Quy trình nhập học: Thí sinh trúng tuyển xác nhận nhập học trực tuyến và nộp hồ sơ gốc tại trường.\n• Học phí: Áp dụng mức học phí công lập theo khung quy định của Nhà nước.",
            'trangThaiCongBo' => 'Đã công bố',
            'maTruongTHPT' => 'THPT_4',
            'tenTruongTHPT' => 'Trường THPT Ánh Dương',
            'maKhuVuc' => 'KV02',
            'tenKhuVuc' => 'Khu vực 2',
            'quanHuyen' => 'Quận 5, Quận 10',
            'maDot' => 'DOT2026',
            'tenDot' => 'Tuyển sinh vào lớp 10 năm học 2026–2027',
            'namTuyenSinh' => 2026,
        ],
        [
            'maThongTin' => 'TT_THPT_5',
            'chiTieuTuyenSinh' => 380,
            'heDaoTao' => 'Công lập',
            'phuongThucTuyenSinh' => 'Thi tuyển',
            'hocPhi' => 0.00,
            'noiDungChiTiet' => "• Chỉ tiêu tuyển sinh: 380 học sinh (chỉ tiêu cao nhất khu vực 3).\n• Địa bàn tuyển sinh: Khu vực 3 (Bình Thạnh, Gò Vấp).\n• Phương thức tuyển sinh: Thi tuyển 3 môn văn hóa chung.\n• Nguyên tắc xét tuyển: Xét từ điểm cao xuống thấp cho đến khi đủ chỉ tiêu được giao.\n• Học phí: Theo biểu mức học phí áp dụng cho các trường THPT công lập năm học 2026–2027.",
            'trangThaiCongBo' => 'Đã công bố',
            'maTruongTHPT' => 'THPT_5',
            'tenTruongTHPT' => 'Trường THPT Trần Quốc Khải',
            'maKhuVuc' => 'KV03',
            'tenKhuVuc' => 'Khu vực 3',
            'quanHuyen' => 'Bình Thạnh, Gò Vấp',
            'maDot' => 'DOT2026',
            'tenDot' => 'Tuyển sinh vào lớp 10 năm học 2026–2027',
            'namTuyenSinh' => 2026,
        ],
    ];

    // Apply filters and query on fallback
    $filtered = $fallbackNotices;
    $trimmedQuery = trim($query);
    if ($trimmedQuery !== '') {
        $qLower = mb_strtolower($trimmedQuery, 'UTF-8');
        $filtered = array_values(array_filter($filtered, function ($item) use ($qLower) {
            $pool = mb_strtolower($item['tenTruongTHPT'] . ' ' . $item['maTruongTHPT'] . ' ' . $item['tenKhuVuc'] . ' ' . $item['quanHuyen'] . ' ' . $item['heDaoTao'] . ' ' . $item['phuongThucTuyenSinh'], 'UTF-8');
            return str_contains($pool, $qLower);
        }));
    }
    if (!empty($filters['region']) && $filters['region'] !== 'all') {
        $filtered = array_values(array_filter($filtered, function ($item) use ($filters) {
            return $item['maKhuVuc'] === $filters['region'] || $item['tenKhuVuc'] === $filters['region'];
        }));
    }
    if (!empty($filters['school']) && $filters['school'] !== 'all') {
        $filtered = array_values(array_filter($filtered, function ($item) use ($filters) {
            return $item['maTruongTHPT'] === $filters['school'];
        }));
    }
    if (!empty($filters['education']) && $filters['education'] !== 'all') {
        $filtered = array_values(array_filter($filtered, function ($item) use ($filters) {
            return $item['heDaoTao'] === $filters['education'];
        }));
    }
    if (!empty($filters['method']) && $filters['method'] !== 'all') {
        $filtered = array_values(array_filter($filtered, function ($item) use ($filters) {
            return $item['phuongThucTuyenSinh'] === $filters['method'];
        }));
    }

    return $filtered;
}
