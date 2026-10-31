<?php
declare(strict_types=1);
/** Read-only, bounded queries for the candidate and aspiration directories. */
require_once dirname(__DIR__) . '/config/database.php';

function candidate_directory_page(string $query, string $school, int $page, int $pageSize, ?string $studentId = null): array {
    $pdo = db();
    $where = []; $params = [];
    if ($studentId !== null) { $where[] = 't.maThiSinh = :owner'; $params[':owner'] = $studentId; }
    if ($school !== '') { $where[] = 't.maTruongTHCS = :school'; $params[':school'] = $school; }
    if ($query !== '') {
        $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $query) . '%';
        $parts = [];
        foreach (['t.maThiSinh','t.maHoSo','t.hoTen','t.soBaoDanh','c.tenTruongTHCS'] as $i => $column) {
            $key = ':query' . $i; $parts[] = "$column LIKE $key ESCAPE '!'"; $params[$key] = $like;
        }
        $where[] = '(' . implode(' OR ', $parts) . ')';
    }
    $from = ' FROM ThiSinh t JOIN TruongTHCS c ON c.maTruongTHCS=t.maTruongTHCS';
    $filter = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $count = $pdo->prepare('SELECT COUNT(*)' . $from . $filter); $count->execute($params);
    $filtered = (int)$count->fetchColumn();
    $pageSize = in_array($pageSize, [25,50,100], true) ? $pageSize : 25;
    $pages = max(1, (int)ceil($filtered / $pageSize)); $page = max(1, min($page, $pages));
    $offset = ($page - 1) * $pageSize;
    $st = $pdo->prepare('SELECT t.maThiSinh,t.maHoSo,t.hoTen,t.ngaySinh,t.soBaoDanh,t.trangThaiDangKyDuThi,t.maTruongTHCS,c.tenTruongTHCS' . $from . $filter . ' ORDER BY t.maThiSinh LIMIT :limit OFFSET :offset');
    foreach ($params as $key => $value) $st->bindValue($key, $value, PDO::PARAM_STR);
    $st->bindValue(':limit', $pageSize, PDO::PARAM_INT); $st->bindValue(':offset', $offset, PDO::PARAM_INT); $st->execute();
    $rows = $st->fetchAll();
    $scope = $studentId === null ? '' : ' AND t.maThiSinh = :owner';
    $summary = $pdo->prepare('SELECT c.maTruongTHCS,c.tenTruongTHCS,COUNT(t.maThiSinh) AS soHocSinh FROM TruongTHCS c LEFT JOIN ThiSinh t ON t.maTruongTHCS=c.maTruongTHCS' . $scope . ' GROUP BY c.maTruongTHCS,c.tenTruongTHCS HAVING COUNT(t.maThiSinh) > 0 ORDER BY c.maTruongTHCS');
    $summary->execute($studentId === null ? [] : [':owner' => $studentId]);
    $schools = $summary->fetchAll();
    return compact('rows','schools','filtered','page','pages','pageSize','offset');
}

function candidate_directory_wishes(array $studentIds): array {
    if (!$studentIds) return [];
    // IDs come exclusively from the already-authorized, paginated directory query.
    $marks = implode(',', array_fill(0, count($studentIds), '?'));
    $st = db()->prepare("SELECT n.maThiSinh,n.thuTuNguyenVong,p.tenTruongTHPT FROM NguyenVong n JOIN TruongTHPT p ON p.maTruongTHPT=n.maTruongTHPT WHERE n.maThiSinh IN ($marks) ORDER BY n.maThiSinh,n.thuTuNguyenVong");
    $st->execute(array_values($studentIds)); $result = [];
    foreach ($st as $row) $result[$row['maThiSinh']][(int)$row['thuTuNguyenVong']] = $row['tenTruongTHPT'];
    return $result;
}
