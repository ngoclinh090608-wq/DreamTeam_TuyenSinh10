<?php
declare(strict_types=1);
require_once __DIR__ . '/student_results_service.php';

/** Public lookup verifies SBD and date of birth, and exposes published scores only. */
function public_published_scores(string $number, string $birth): array {
    $number = trim($number);
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $birth);
    if (!preg_match('/\A[A-Za-z0-9_-]{1,20}\z/D', $number) || !$date || $date->format('Y-m-d') !== $birth) {
        throw new BusinessException('Vui lòng nhập số báo danh và ngày sinh hợp lệ.');
    }
    $q = db()->prepare('SELECT maThiSinh FROM ThiSinh WHERE soBaoDanh=? AND ngaySinh=? LIMIT 1');
    $q->execute([$number, $birth]);
    $owner = $q->fetchColumn();
    if ($owner === false) return [];
    $scores = student_published_scores(['vaiTro'=>'HOC_SINH', 'maThiSinh'=>$owner]);
    return array_map(static fn(array $row): array => [
        'tenKyThi'=>$row['tenKyThi'], 'tenMonThi'=>$row['tenMonThi'], 'diemThi'=>$row['diemThi']
    ], $scores);
}
