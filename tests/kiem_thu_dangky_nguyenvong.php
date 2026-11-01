<?php
declare(strict_types=1);

/**
 * Kiểm thử toàn diện chức năng ĐĂNG KÝ TUYỂN SINH LỚP 10 & QUẢN LÝ NGUYỆN VỌNG.
 * Bao gồm:
 * - Đăng ký hợp lệ lần đầu.
 * - Chỉnh sửa nguyện vọng đã tồn tại (UPDATE chứ không tạo mới).
 * - Lưu nhiều lần không nhân bản bản ghi.
 * - Validation: trùng trường, ngắt quãng, thiếu NV1, trường không tồn tại.
 * - Kiểm tra điều kiện hồ sơ (chưa đủ điều kiện, hồ sơ bị từ chối).
 * - Kiểm tra thời hạn đăng ký (chưa mở, hết hạn, đang mở).
 * - Kiểm tra bảo mật: phân quyền role, cách ly dữ liệu giữa các học sinh (ownership).
 * - Kiểm tra CSRF và Transaction Rollback khi có lỗi.
 * - Kiểm tra giao diện HTML, các select box, dialog xác nhận và in phiếu.
 */

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/services/aspiration_service.php';

app_session_start();

$p = db();
$p->beginTransaction();

try {
    // -------------------------------------------------------------
    // SETUP FIXTURES TRONG TRANSACTION
    // -------------------------------------------------------------
    // Tạo đợt tuyển sinh và quy định test
    $p->exec("INSERT INTO DotTuyenSinh (maDot, tenDot, namTuyenSinh)
              VALUES ('DOT_TEST', 'Đợt Tuyển Sinh Kiểm Thử 2026', 2026)
              ON DUPLICATE KEY UPDATE tenDot = VALUES(tenDot)");

    $nowTime = date('Y-m-d H:i:s');
    $startTime = date('Y-m-d H:i:s', time() - 3600);
    $endTime = date('Y-m-d H:i:s', time() + 3600);
    $pastTime = date('Y-m-d H:i:s', time() - 7200);
    $futureTime = date('Y-m-d H:i:s', time() + 7200);

    // Quy định đang mở
    $p->exec("INSERT INTO QuyDinhNguyenVong (maQuyDinh, namTuyenSinh, soLuongNguyenVongToiDa, thoiGianBatDau, thoiGianKetThuc, ghiChu)
              VALUES ('QD_OPEN', 2026, 3, '{$startTime}', '{$endTime}', 'Quy định mở kiểm thử')
              ON DUPLICATE KEY UPDATE thoiGianBatDau = VALUES(thoiGianBatDau), thoiGianKetThuc = VALUES(thoiGianKetThuc)");

    // Thí sinh A: Đủ điều kiện, chưa có nguyện vọng nào
    $p->exec("INSERT INTO ThiSinh (maThiSinh, maHoSo, hoTen, ngaySinh, soBaoDanh, dieuKienDuThi, trangThaiDangKyDuThi, maTruongTHCS)
              VALUES ('TS_TEST_A', 'HS_TEST_A', 'Học Sinh Kiểm Thử A', '2011-01-01', 'SBD_TEST_A', 1, 'Đã duyệt', 'THCS_A')");
    $p->exec("INSERT INTO TaiKhoan (maTaiKhoan, tenDangNhap, matKhau, hoTen, vaiTro, maCanBo, maThiSinh, trangThai)
              VALUES ('TK_TEST_A', 'hocsinha', '\$2y\$10\$m.1I1MF8uLV9orbw6fwRhOJ81f58KqrKIIF/xpGklJ2loInjp9hpy', 'Học Sinh Kiểm Thử A', 'HOC_SINH', NULL, 'TS_TEST_A', 'Hoạt động')");
    $userA = [
        'maTaiKhoan' => 'TK_TEST_A',
        'tenDangNhap' => 'hocsinha',
        'hoTen' => 'Học Sinh Kiểm Thử A',
        'vaiTro' => 'HOC_SINH',
        'maCanBo' => null,
        'maThiSinh' => 'TS_TEST_A',
        'trangThai' => 'Hoạt động',
    ];

    // Thí sinh B: Đủ điều kiện
    $p->exec("INSERT INTO ThiSinh (maThiSinh, maHoSo, hoTen, ngaySinh, soBaoDanh, dieuKienDuThi, trangThaiDangKyDuThi, maTruongTHCS)
              VALUES ('TS_TEST_B', 'HS_TEST_B', 'Học Sinh Kiểm Thử B', '2011-02-02', 'SBD_TEST_B', 1, 'Đã duyệt', 'THCS_B')");
    $userB = [
        'maTaiKhoan' => 'TK_TEST_B',
        'tenDangNhap' => 'hocsinhb',
        'hoTen' => 'Học Sinh Kiểm Thử B',
        'vaiTro' => 'HOC_SINH',
        'maCanBo' => null,
        'maThiSinh' => 'TS_TEST_B',
        'trangThai' => 'Hoạt động',
    ];

    // Thí sinh C: Chưa đủ điều kiện dự thi (dieuKienDuThi = 0)
    $p->exec("INSERT INTO ThiSinh (maThiSinh, maHoSo, hoTen, ngaySinh, soBaoDanh, dieuKienDuThi, trangThaiDangKyDuThi, maTruongTHCS)
              VALUES ('TS_TEST_C', 'HS_TEST_C', 'Học Sinh Kiểm Thử C', '2011-03-03', NULL, 0, 'Chưa duyệt', 'THCS_A')");
    $userC = [
        'maTaiKhoan' => 'TK_TEST_C',
        'tenDangNhap' => 'hocsinhc',
        'hoTen' => 'Học Sinh Kiểm Thử C',
        'vaiTro' => 'HOC_SINH',
        'maCanBo' => null,
        'maThiSinh' => 'TS_TEST_C',
        'trangThai' => 'Hoạt động',
    ];

    // Thí sinh D: Hồ sơ bị từ chối
    $p->exec("INSERT INTO ThiSinh (maThiSinh, maHoSo, hoTen, ngaySinh, soBaoDanh, dieuKienDuThi, trangThaiDangKyDuThi, maTruongTHCS)
              VALUES ('TS_TEST_D', 'HS_TEST_D', 'Học Sinh Kiểm Thử D', '2011-04-04', NULL, 1, 'Từ chối', 'THCS_A')");
    $userD = [
        'maTaiKhoan' => 'TK_TEST_D',
        'tenDangNhap' => 'hocsinhd',
        'hoTen' => 'Học Sinh Kiểm Thử D',
        'vaiTro' => 'HOC_SINH',
        'maCanBo' => null,
        'maThiSinh' => 'TS_TEST_D',
        'trangThai' => 'Hoạt động',
    ];

    // Cán bộ (không phải vai trò học sinh)
    $officerUser = [
        'maTaiKhoan' => 'TK_OFFICER',
        'tenDangNhap' => 'officer',
        'hoTen' => 'Cán bộ',
        'vaiTro' => 'HD_TUYEN_SINH',
        'maCanBo' => 'CB01',
        'maThiSinh' => null,
        'trangThai' => 'Hoạt động',
    ];

    // -------------------------------------------------------------
    // TEST 1: Đăng ký nguyện vọng hợp lệ lần đầu (NV1, NV2, NV3)
    // -------------------------------------------------------------
    $res1 = luuNguyenVong($userA, [
        1 => 'THPT_1',
        2 => 'THPT_2',
        3 => 'THPT_3',
    ], true);

    if (!$res1['success']) throw new RuntimeException('Test 1 failed: Registration returned unsuccessful');
    $wishesA = layDanhSachNguyenVongThiSinh('TS_TEST_A');
    if (count($wishesA) !== 3) throw new RuntimeException('Test 1 failed: Expected exactly 3 wishes for TS_TEST_A');
    if ($wishesA[1]['maTruongTHPT'] !== 'THPT_1' || $wishesA[2]['maTruongTHPT'] !== 'THPT_2' || $wishesA[3]['maTruongTHPT'] !== 'THPT_3') {
        throw new RuntimeException('Test 1 failed: Wish school mapping incorrect');
    }
    $origNv1Id = $wishesA[1]['maNguyenVong'];
    $origNv2Id = $wishesA[2]['maNguyenVong'];
    $origNv3Id = $wishesA[3]['maNguyenVong'];
    echo "PASS 1: Valid initial admission wish registration (NV1, NV2, NV3)\n";

    // -------------------------------------------------------------
    // TEST 2: Chỉnh sửa nguyện vọng đã tồn tại (UPDATE bản ghi, giữ PK)
    // -------------------------------------------------------------
    $res2 = luuNguyenVong($userA, [
        1 => 'THPT_1',
        2 => 'THPT_4', // Đổi NV2 sang THPT_4
        3 => 'THPT_5', // Đổi NV3 sang THPT_5
    ], true);

    $wishesA_updated = layDanhSachNguyenVongThiSinh('TS_TEST_A');
    if (count($wishesA_updated) !== 3) throw new RuntimeException('Test 2 failed: Count changed during update');
    if ($wishesA_updated[2]['maTruongTHPT'] !== 'THPT_4' || $wishesA_updated[3]['maTruongTHPT'] !== 'THPT_5') {
        throw new RuntimeException('Test 2 failed: Updated school not reflected');
    }
    // Đảm bảo không tạo ID mới, mà UPDATE bản ghi cũ để giữ toàn vẹn khóa ngoại
    if ($wishesA_updated[1]['maNguyenVong'] !== $origNv1Id ||
        $wishesA_updated[2]['maNguyenVong'] !== $origNv2Id ||
        $wishesA_updated[3]['maNguyenVong'] !== $origNv3Id) {
        throw new RuntimeException('Test 2 failed: Wish primary keys must be preserved on update');
    }
    echo "PASS 2: Existing wishes updated properly without deleting/recreating records\n";

    // -------------------------------------------------------------
    // TEST 3: Lưu nhiều lần không tạo bản ghi trùng
    // -------------------------------------------------------------
    for ($i = 0; $i < 3; $i++) {
        luuNguyenVong($userA, [
            1 => 'THPT_1',
            2 => 'THPT_4',
            3 => 'THPT_5',
        ], true);
    }
    $countRows = (int)$p->query("SELECT COUNT(*) FROM NguyenVong WHERE maThiSinh = 'TS_TEST_A'")->fetchColumn();
    if ($countRows !== 3) throw new RuntimeException("Test 3 failed: Multiple saves created duplicate records (count: {$countRows})");
    echo "PASS 3: Multiple saves are idempotent and do not create duplicate rows\n";

    // -------------------------------------------------------------
    // TEST 4: Hai nguyện vọng trùng trường THPT (Validation error)
    // -------------------------------------------------------------
    $caughtDuplicateSchool = false;
    try {
        luuNguyenVong($userA, [
            1 => 'THPT_1',
            2 => 'THPT_1', // Trùng với NV1
            3 => 'THPT_3',
        ], true);
    } catch (BusinessException $e) {
        if ($e->getMessage() === TB_NV_TRUNG_TRUONG) {
            $caughtDuplicateSchool = true;
        }
    }
    if (!$caughtDuplicateSchool) throw new RuntimeException('Test 4 failed: Duplicate school between wishes was not rejected');
    echo "PASS 4: Duplicate school detection between wishes rejected\n";

    // -------------------------------------------------------------
    // TEST 5: Thứ tự ngắt quãng hoặc thiếu NV1
    // -------------------------------------------------------------
    $caughtMissingNv1 = false;
    try {
        luuNguyenVong($userA, [
            1 => '',       // Thiếu NV1
            2 => 'THPT_2',
            3 => 'THPT_3',
        ], true);
    } catch (BusinessException $e) {
        if ($e->getMessage() === TB_NV_THIEU_NV1) {
            $caughtMissingNv1 = true;
        }
    }
    if (!$caughtMissingNv1) throw new RuntimeException('Test 5 failed: Missing NV1 was not rejected');

    $caughtGap = false;
    try {
        luuNguyenVong($userA, [
            1 => 'THPT_1',
            2 => '',       // Bỏ trống NV2
            3 => 'THPT_3', // Nhưng lại chọn NV3
        ], true);
    } catch (BusinessException $e) {
        if ($e->getMessage() === TB_NV_NGAT_QUANG) {
            $caughtGap = true;
        }
    }
    if (!$caughtGap) throw new RuntimeException('Test 5 failed: Discontinuous wish gap was not rejected');
    echo "PASS 5: Missing NV1 and wish gap sequence rejected\n";

    // -------------------------------------------------------------
    // TEST 6: Mã trường THPT không tồn tại
    // -------------------------------------------------------------
    $caughtInvalidSchool = false;
    try {
        luuNguyenVong($userA, [
            1 => 'THPT_NON_EXISTING',
            2 => 'THPT_2',
            3 => 'THPT_3',
        ], true);
    } catch (BusinessException $e) {
        if (str_contains($e->getMessage(), TB_NV_TRUONG_KHONG_HOP_LE)) {
            $caughtInvalidSchool = true;
        }
    }
    if (!$caughtInvalidSchool) throw new RuntimeException('Test 6 failed: Non-existing school code was not rejected');
    echo "PASS 6: Non-existing high school code rejected\n";

    // -------------------------------------------------------------
    // TEST 7: Hồ sơ chưa đủ điều kiện hoặc bị từ chối
    // -------------------------------------------------------------
    $caughtNotQualified = false;
    try {
        luuNguyenVong($userC, [
            1 => 'THPT_1',
            2 => 'THPT_2',
        ], true);
    } catch (BusinessException $e) {
        if ($e->getMessage() === TB_NV_CHUA_DU_DIEU_KIEN) {
            $caughtNotQualified = true;
        }
    }
    if (!$caughtNotQualified) throw new RuntimeException('Test 7 failed: Ineligible candidate was not rejected');

    $caughtRejectedProfile = false;
    try {
        luuNguyenVong($userD, [
            1 => 'THPT_1',
            2 => 'THPT_2',
        ], true);
    } catch (BusinessException $e) {
        if ($e->getMessage() === TB_NV_HO_SO_TU_CHOI) {
            $caughtRejectedProfile = true;
        }
    }
    if (!$caughtRejectedProfile) throw new RuntimeException('Test 7 failed: Rejected registration status was not blocked');
    echo "PASS 7: Ineligible candidate and rejected profile blocked\n";

    // -------------------------------------------------------------
    // TEST 8: Đăng ký ngoài thời gian cho phép (Hết hạn hoặc Chưa mở)
    // -------------------------------------------------------------
    // Tạo quy định đã hết hạn trong quá khứ (kết thúc > bắt đầu nhưng đều trong quá khứ)
    $pastStart = date('Y-m-d H:i:s', time() - 7200);
    $pastEnd = date('Y-m-d H:i:s', time() - 3600);
    $p->exec("UPDATE QuyDinhNguyenVong SET thoiGianBatDau = '{$pastStart}', thoiGianKetThuc = '{$pastEnd}' WHERE maQuyDinh = 'QD_OPEN'");
    $caughtExpired = false;
    try {
        // Chạy với boQuaThoiHan = false để kiểm tra thời hạn thực tế
        luuNguyenVong($userA, [
            1 => 'THPT_1',
            2 => 'THPT_2',
        ], false);
    } catch (BusinessException $e) {
        if (str_contains($e->getMessage(), TB_NV_HET_HAN)) {
            $caughtExpired = true;
        }
    }
    if (!$caughtExpired) throw new RuntimeException('Test 8 failed: Expired registration deadline was not enforced');

    // Tạo quy định chưa mở trong tương lai (kết thúc > bắt đầu nhưng đều trong tương lai)
    $futureStart = date('Y-m-d H:i:s', time() + 3600);
    $futureEnd = date('Y-m-d H:i:s', time() + 7200);
    $p->exec("UPDATE QuyDinhNguyenVong SET thoiGianBatDau = '{$futureStart}', thoiGianKetThuc = '{$futureEnd}' WHERE maQuyDinh = 'QD_OPEN'");
    $caughtUpcoming = false;
    try {
        luuNguyenVong($userA, [
            1 => 'THPT_1',
            2 => 'THPT_2',
        ], false);
    } catch (BusinessException $e) {
        if (str_contains($e->getMessage(), TB_NV_CHUA_MO)) {
            $caughtUpcoming = true;
        }
    }
    if (!$caughtUpcoming) throw new RuntimeException('Test 8 failed: Upcoming unopened registration window was not enforced');

    // Khôi phục lại thời gian mở cho các test tiếp theo
    $p->exec("UPDATE QuyDinhNguyenVong SET thoiGianBatDau = '{$startTime}', thoiGianKetThuc = '{$endTime}' WHERE maQuyDinh = 'QD_OPEN'");
    echo "PASS 8: Registration time boundaries (expired and upcoming) strictly enforced\n";

    // -------------------------------------------------------------
    // TEST 9: Học sinh chỉ xem và sửa nguyện vọng của chính mình (Owner isolation)
    // -------------------------------------------------------------
    // Đăng ký nguyện vọng cho Thí sinh B
    luuNguyenVong($userB, [
        1 => 'THPT_3',
        2 => 'THPT_4',
    ], true);
    $wishesB_before = layDanhSachNguyenVongThiSinh('TS_TEST_B');

    // Thí sinh A cố gắng sửa nguyện vọng nhưng hệ thống dùng $userA['maThiSinh']
    // Dù client có gửi gì thì $userA chỉ ghi vào TS_TEST_A
    luuNguyenVong($userA, [
        1 => 'THPT_1',
        2 => 'THPT_2',
    ], true);

    $wishesB_after = layDanhSachNguyenVongThiSinh('TS_TEST_B');
    if ($wishesB_before !== $wishesB_after) {
        throw new RuntimeException('Test 9 failed: Student A altered Student B wishes');
    }
    echo "PASS 9: Candidate ownership isolated; students cannot modify other students' aspirations\n";

    // -------------------------------------------------------------
    // TEST 10: Người chưa đăng nhập hoặc sai role truy cập chức năng
    // -------------------------------------------------------------
    $caughtOfficer = false;
    try {
        luuNguyenVong($officerUser, [1 => 'THPT_1'], true);
    } catch (BusinessException $e) {
        if ($e->getMessage() === TB_NV_KHONG_CO_QUYEN) {
            $caughtOfficer = true;
        }
    }
    if (!$caughtOfficer) throw new RuntimeException('Test 10 failed: Non-student role was not denied');

    $caughtGuest = false;
    try {
        luuNguyenVong([], [1 => 'THPT_1'], true);
    } catch (BusinessException $e) {
        if ($e->getMessage() === TB_NV_KHONG_CO_QUYEN) {
            $caughtGuest = true;
        }
    }
    if (!$caughtGuest) throw new RuntimeException('Test 10 failed: Unauthenticated user was not denied');
    echo "PASS 10: Authentication and student role requirement enforced\n";

    // -------------------------------------------------------------
    // TEST 11: CSRF & Transaction Rollback
    // -------------------------------------------------------------
    // CSRF test
    unset($_SESSION['csrf_token'], $_POST['csrf_token']);
    if (csrf_verify()) throw new RuntimeException('Test 11 failed: Empty CSRF token must not verify');
    $_POST['csrf_token'] = 'invalid_fake_token';
    if (csrf_verify()) throw new RuntimeException('Test 11 failed: Invalid CSRF token must not verify');
    $_SESSION['csrf_token'] = 'valid_secret_token';
    $_POST['csrf_token'] = 'valid_secret_token';
    if (!csrf_verify()) throw new RuntimeException('Test 11 failed: Matching CSRF token must verify');

    // Rollback integrity: Khi có lỗi ở một nguyện vọng, transaction phải rollback toàn bộ (nguyên tử)
    $p->exec("INSERT INTO ThiSinh (maThiSinh, maHoSo, hoTen, ngaySinh, soBaoDanh, dieuKienDuThi, trangThaiDangKyDuThi, maTruongTHCS)
              VALUES ('TS_TEST_RB', 'HS_TEST_RB', 'Thí Sinh Rollback', '2011-05-05', NULL, 1, 'Đã duyệt', 'THCS_A')");
    $userRb = [
        'maTaiKhoan' => 'TK_TEST_RB',
        'tenDangNhap' => 'hocsinhrb',
        'hoTen' => 'Thí Sinh Rollback',
        'vaiTro' => 'HOC_SINH',
        'maCanBo' => null,
        'maThiSinh' => 'TS_TEST_RB',
        'trangThai' => 'Hoạt động',
    ];

    $caughtRollbackError = false;
    try {
        // NV1 hợp lệ nhưng NV2 không hợp lệ -> ném exception
        luuNguyenVong($userRb, [
            1 => 'THPT_1',
            2 => 'THPT_INVALID_FOR_ROLLBACK',
        ], true);
    } catch (BusinessException $e) {
        $caughtRollbackError = true;
    }
    if (!$caughtRollbackError) throw new RuntimeException('Test 11 failed: Error was not triggered');
    // Kiểm tra tính nguyên tử: NV1 không được phép tồn tại một mình trong CSDL
    $countRb = (int)$p->query("SELECT COUNT(*) FROM NguyenVong WHERE maThiSinh = 'TS_TEST_RB'")->fetchColumn();
    if ($countRb !== 0) throw new RuntimeException("Test 11 failed: Transaction rollback failed, partial record saved (count: {$countRb})");
    echo "PASS 11: CSRF verification and transactional rollback integrity confirmed\n";

    // -------------------------------------------------------------
    // TEST 12: Kiểm tra render giao diện HTML và UI controls
    // -------------------------------------------------------------
    $_SESSION['user'] = $userA;
    $_SESSION['last_seen'] = time();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

    ob_start();
    require dirname(__DIR__) . '/aspirations/hoc_sinh_dang_ky.php';
    $renderedHtml = ob_get_clean();

    foreach ([
        'Đăng ký tuyển sinh lớp 10 & Quản lý nguyện vọng',
        'Thông tin thí sinh',
        'Thời hạn &amp; Quy định',
        'wish-select-1',
        'wish-select-2',
        'wish-select-3',
        'student-confirm-dialog',
        'student-print-button',
        'print-aspiration-ticket',
        'csrf_token',
    ] as $mustContain) {
        if (!str_contains($renderedHtml, $mustContain)) {
            throw new RuntimeException("Test 12 failed: HTML missing required element '{$mustContain}'");
        }
    }

    // Đảm bảo không lộ thanh menu admin quản trị
    foreach (['class="sidebar"', 'Mở menu quản trị', 'metric-grid'] as $mustNotContain) {
        if (str_contains($renderedHtml, $mustNotContain)) {
            throw new RuntimeException("Test 12 failed: Admin management UI leaked into student view: '{$mustNotContain}'");
        }
    }
    echo "PASS 12: Student HTML view rendered with all UI components, CSRF, modals, and print template\n";

} finally {
    // Luôn luôn rollback để giữ dữ liệu ban đầu hoàn toàn sạch sẽ
    $p->rollBack();
}

echo "\n>>> ALL 12 ADMISSION WISH REGISTRATION TESTS PASSED SUCCESSFULLY! <<<\n";
