<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/services/aspiration_service.php';

function uc_nv_assert(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

echo "=== BẮT ĐẦU KIỂM THỬ USE CASE: QUẢN LÝ NGUYỆN VỌNG XÉT TUYỂN LỚP 10 ===\n\n";

$pdo = db();
$testStudentId = 'TS019';
$linkedStudentId = 'TS002';
$studentStmt = $pdo->prepare('SELECT dieuKienDuThi, trangThaiDangKyDuThi FROM thisinh WHERE maThiSinh = ?');
$studentStmt->execute([$testStudentId]);
$originalStudent = $studentStmt->fetch();
uc_nv_assert($originalStudent !== false, 'Test student fixture must exist');

$wishesStmt = $pdo->prepare('SELECT maNguyenVong FROM nguyenvong WHERE maThiSinh = ?');
$wishesStmt->execute([$testStudentId]);
uc_nv_assert($wishesStmt->fetch() === false, 'Test student fixture must not have existing wishes');

$gradeStmt = $pdo->prepare('SELECT diemTrungBinh, hocLuc, hanhKiem FROM hoctapthcs WHERE maThiSinh = ? AND lop = 9');
$gradeStmt->execute([$testStudentId]);
$originalGrade = $gradeStmt->fetch();

try {
    echo "[1] Kiểm tra tiền điều kiện và thời gian đăng ký:\n";
    $pdo->prepare("UPDATE thisinh SET dieuKienDuThi = 1, trangThaiDangKyDuThi = 'Đã duyệt' WHERE maThiSinh = ?")
        ->execute([$testStudentId]);
    $gradeUpsert = $pdo->prepare('
        INSERT INTO hoctapthcs (maThiSinh, lop, diemTrungBinh, hocLuc, hanhKiem)
        VALUES (?, 9, 8.50, "Tốt", "Khá")
        ON DUPLICATE KEY UPDATE diemTrungBinh = VALUES(diemTrungBinh), hocLuc = VALUES(hocLuc), hanhKiem = VALUES(hanhKiem)
    ');
    $gradeUpsert->execute([$testStudentId]);

    $candidate = aspiration_get_candidate($testStudentId);
    uc_nv_assert($candidate !== null, 'Candidate must exist');
    uc_nv_assert($candidate['registered_exam_ok'] === true, 'Student must be registered and approved');
    uc_nv_assert($candidate['eligible_specialized'] === true, 'Student fixture must qualify for specialized classes');
    echo "  - Học sinh: {$candidate['hoTen']} ({$candidate['maThiSinh']})\n";
    echo "  - Đủ điều kiện dự thi và đăng ký lớp chuyên.\n";

    $regulation = aspiration_get_regulation();
    uc_nv_assert($regulation !== null, 'Regulation must exist');
    $openRegulation = $regulation;
    $openRegulation['is_open'] = true;
    echo "  - Quy chế: {$regulation['thoiGianBatDau']} đến {$regulation['thoiGianKetThuc']}.\n";

    $closedRegulation = $openRegulation;
    $closedRegulation['is_open'] = false;
    try {
        aspiration_validate($testStudentId, [
            ['thuTuNguyenVong' => 1, 'maTruongTHPT' => 'THPT_1', 'lopChuyen' => 'Thường'],
        ], $closedRegulation, $candidate);
        uc_nv_assert(false, 'Registration must be rejected when the period is closed');
    } catch (BusinessException $e) {
        echo "  [OK] Chặn đăng ký khi quy chế đã đóng.\n";
    }

    echo "\n[2] Kiểm tra các trường hợp dữ liệu không hợp lệ:\n";
    $invalidCases = [
        [
            'wishes' => [
                ['thuTuNguyenVong' => 1, 'maTruongTHPT' => 'THPT_1', 'lopChuyen' => 'Thường'],
                ['thuTuNguyenVong' => 2, 'maTruongTHPT' => 'THPT_1', 'lopChuyen' => 'Thường'],
            ],
            'message' => 'Phải chặn trùng trường hệ thường',
        ],
        [
            'wishes' => [
                ['thuTuNguyenVong' => 1, 'maTruongTHPT' => 'THPT_1', 'lopChuyen' => 'Thường'],
                ['thuTuNguyenVong' => 2, 'maTruongTHPT' => 'THPT_2', 'lopChuyen' => 'Thường'],
                ['thuTuNguyenVong' => 3, 'maTruongTHPT' => 'THPT_3', 'lopChuyen' => 'Thường'],
                ['thuTuNguyenVong' => 4, 'maTruongTHPT' => 'THPT_4', 'lopChuyen' => 'Thường'],
            ],
            'message' => 'Phải chặn vượt số lượng nguyện vọng',
        ],
        [
            'wishes' => [
                ['thuTuNguyenVong' => 1, 'maTruongTHPT' => 'THPT_3', 'lopChuyen' => 'Chuyên Toán'],
            ],
            'message' => 'Phải chặn lớp chuyên trường không có chỉ tiêu',
        ],
        [
            'wishes' => [],
            'message' => 'Phải yêu cầu ít nhất một nguyện vọng',
        ],
    ];
    foreach ($invalidCases as $case) {
        try {
            aspiration_validate($testStudentId, $case['wishes'], $openRegulation, $candidate);
            uc_nv_assert(false, $case['message']);
        } catch (BusinessException $e) {
            echo "  [OK] {$e->getMessage()}\n";
        }
    }

    echo "\n[3] Kiểm tra lưu, chỉnh sửa, thêm và xóa nguyện vọng:\n";
    $newWishes = [
        ['thuTuNguyenVong' => 1, 'maTruongTHPT' => 'THPT_1', 'lopChuyen' => 'Chuyên Toán'],
        ['thuTuNguyenVong' => 2, 'maTruongTHPT' => 'THPT_2', 'lopChuyen' => 'Thường'],
        ['thuTuNguyenVong' => 3, 'maTruongTHPT' => 'THPT_3', 'lopChuyen' => 'Thường'],
    ];
    aspiration_save(
        $testStudentId,
        aspiration_validate($testStudentId, $newWishes, $openRegulation, $candidate),
        $openRegulation['maQuyDinh']
    );

    $savedWishes = aspiration_get_wishes($testStudentId);
    uc_nv_assert(count($savedWishes) === 3, 'Saved count must be 3');
    uc_nv_assert($savedWishes[0]['maTruongTHPT'] === 'THPT_1' && $savedWishes[0]['lopChuyen'] === 'Chuyên Toán', 'NV1 must match');
    uc_nv_assert($savedWishes[1]['maTruongTHPT'] === 'THPT_2', 'NV2 must match');
    uc_nv_assert($savedWishes[2]['maTruongTHPT'] === 'THPT_3', 'NV3 must match');
    echo "  [OK] Lưu và cập nhật thành công 3 nguyện vọng, gồm lớp chuyên.\n";

    $reducedWishes = array_slice($newWishes, 0, 2);
    aspiration_save(
        $testStudentId,
        aspiration_validate($testStudentId, $reducedWishes, $openRegulation, $candidate),
        $openRegulation['maQuyDinh']
    );
    $savedReduced = aspiration_get_wishes($testStudentId);
    uc_nv_assert(count($savedReduced) === 2, 'Remaining count must be 2');
    uc_nv_assert((int)$savedReduced[0]['thuTuNguyenVong'] === 1, 'Order 1');
    uc_nv_assert((int)$savedReduced[1]['thuTuNguyenVong'] === 2, 'Order 2');
    echo "  [OK] Xóa một nguyện vọng và dồn lại thứ tự.\n";

    echo "\n[4] Kiểm tra không xóa nguyện vọng đã liên kết kết quả xét tuyển:\n";
    $linkedCandidate = aspiration_get_candidate($linkedStudentId);
    uc_nv_assert($linkedCandidate !== null, 'Linked candidate must exist');
    $linkedWishes = aspiration_get_wishes($linkedStudentId);
    uc_nv_assert(count($linkedWishes) >= 2, 'Linked candidate fixture must have at least two wishes');
    $shortLinkedWishes = [];
    foreach (array_slice($linkedWishes, 0, count($linkedWishes) - 1) as $index => $wish) {
        $shortLinkedWishes[] = [
            'thuTuNguyenVong' => $index + 1,
            'maTruongTHPT' => $wish['maTruongTHPT'],
            'lopChuyen' => $wish['lopChuyen'],
        ];
    }
    try {
        aspiration_save(
            $linkedStudentId,
            aspiration_validate($linkedStudentId, $shortLinkedWishes, $openRegulation, $linkedCandidate),
            $openRegulation['maQuyDinh']
        );
        uc_nv_assert(false, 'Must not delete a wish linked to an admission result');
    } catch (BusinessException $e) {
        uc_nv_assert(
            count(aspiration_get_wishes($linkedStudentId)) === count($linkedWishes),
            'Rejected deletion must leave linked wishes unchanged'
        );
        echo "  [OK] Từ chối xóa nguyện vọng có kết quả xét tuyển liên kết.\n";
    }
} finally {
    $pdo->prepare('DELETE FROM nguyenvong WHERE maThiSinh = ?')->execute([$testStudentId]);
    if ($originalGrade === false) {
        $pdo->prepare('DELETE FROM hoctapthcs WHERE maThiSinh = ? AND lop = 9')->execute([$testStudentId]);
    } else {
        $pdo->prepare('UPDATE hoctapthcs SET diemTrungBinh = ?, hocLuc = ?, hanhKiem = ? WHERE maThiSinh = ? AND lop = 9')
            ->execute([
                $originalGrade['diemTrungBinh'],
                $originalGrade['hocLuc'],
                $originalGrade['hanhKiem'],
                $testStudentId,
            ]);
    }
    $pdo->prepare('UPDATE thisinh SET dieuKienDuThi = ?, trangThaiDangKyDuThi = ? WHERE maThiSinh = ?')
        ->execute([
            $originalStudent['dieuKienDuThi'],
            $originalStudent['trangThaiDangKyDuThi'],
            $testStudentId,
        ]);
}

echo "\n=== TẤT CẢ CÁC BƯỚC KIỂM THỬ ĐÃ VƯỢT QUA ===\n";
