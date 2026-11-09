<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/business_exception.php';

/** One atomic batch; existing numbers are immutable. The unique index is the final guard. */
function candidate_issue_numbers(array $actor, array $ids): array {
    if (($actor['vaiTro'] ?? '') !== 'BAN_TUYEN_SINH') throw new BusinessException('Chỉ Ban tuyển sinh được cấp số báo danh.');
    if (!$ids || count($ids) > 200) throw new BusinessException('Vui lòng chọn từ 1 đến 200 thí sinh.');
    foreach ($ids as $id) if (!is_string($id) || !preg_match('/\A[A-Za-z0-9_-]{1,20}\z/D', $id)) throw new BusinessException('Mã thí sinh không hợp lệ.');
    $ids = array_values(array_unique($ids)); sort($ids);
    $pdo = db(); $pdo->beginTransaction();
    try {
        // Lock in a consistent order, including existing SBDs, so two batches cannot allocate the same next number.
        $rows = $pdo->query('SELECT maThiSinh,soBaoDanh,dieuKienDuThi,trangThaiDangKyDuThi FROM ThiSinh ORDER BY maThiSinh FOR UPDATE')->fetchAll();
        $byId = array_column($rows, null, 'maThiSinh'); $max = 0;
        foreach ($rows as $row) if (preg_match('/\ASBD([0-9]{1,16})\z/iD', (string)$row['soBaoDanh'], $m)) $max = max($max, (int)$m[1]);
        foreach ($ids as $id) {
            $row = $byId[$id] ?? null;
            if (!$row || (int)$row['dieuKienDuThi'] !== 1 || $row['trangThaiDangKyDuThi'] !== 'Đã duyệt') throw new BusinessException('Thí sinh chưa được duyệt hoặc chưa đủ điều kiện dự thi: ' . $id);
            if ($row['soBaoDanh'] !== null && $row['soBaoDanh'] !== '') throw new BusinessException('Thí sinh đã có số báo danh: ' . $id);
        }
        $update = $pdo->prepare("UPDATE ThiSinh SET soBaoDanh=? WHERE maThiSinh=? AND (soBaoDanh IS NULL OR soBaoDanh='')");
        $issued = [];
        foreach ($ids as $id) {
            if ($max >= 9999999999999999) throw new BusinessException('Đã hết dải số báo danh.');
            $sbd = 'SBD' . str_pad((string)++$max, 8, '0', STR_PAD_LEFT);
            $update->execute([$sbd, $id]);
            if ($update->rowCount() !== 1) throw new BusinessException('Hồ sơ đã thay đổi. Vui lòng tải lại.');
            $issued[$id] = $sbd;
        }
        $pdo->commit(); return $issued;
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
