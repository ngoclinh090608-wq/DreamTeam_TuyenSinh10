<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/services/grading_import_service.php';
$count = 0;
function check(bool $ok, string $message): void {
    global $count;
    if (!$ok) throw new RuntimeException($message);
    $count++; echo "PASS: $message\n";
}
function rejects(callable $fn, string $message): void {
    try { $fn(); } catch (BusinessException $e) { check(true, $message); return; }
    throw new RuntimeException($message);
}
function edit_sheet(string $path, callable $edit): void {
    $zip = new PharData($path);
    $zip['xl/worksheets/sheet1.xml'] = $edit($zip['xl/worksheets/sheet1.xml']->getContent());
}
function has_errors(array $rows): bool { return (bool)array_filter($rows, static fn($r) => $r['loi'] !== ''); }
$ky = 'KT2026'; $mon = 'TOAN'; $phong = 'P202'; $tui = 'LO_P202_TOAN';
$path = '';
$pdo = db();
try {
    check(in_array($phong, array_column(grading_ds_phong_nhap_diem($ky, $mon), 'maPhong')), 'Room filter follows exam and subject');
    check(in_array($tui, array_column(grading_ds_tui_bai($ky, $mon, $phong), 'maLoBaiThi')), 'Bag list follows selected room');
    rejects(fn() => grading_bai_trong_tui($ky, $mon, 'P101', $tui), 'Reject a bag in another room');
    $papers = grading_bai_trong_tui($ky, $mon, $phong, $tui);
    $path = grading_xuat_tui_excel($papers);
    $rows = grading_doc_excel($path);
    check(count($rows) === 3 && $rows[0]['phong'] === $phong, 'Export contains the three ungraded papers and room');
    check(array_column($rows, 'phach') === ['P0008', 'P0010', 'P0030'], 'Export contains correct anonymous codes');
    check(array_column($rows, 'diem') === ['', '', ''], 'Score column is blank for graders to fill');
    check(has_errors(grading_kiem_tra_import($rows, $ky, $mon, $phong, $tui)), 'Blank scores cannot be saved');
    edit_sheet($path, static fn($xml) => preg_replace('~(<c r="C[2-9][0-9]*" t="inlineStr"><is><t>)(</t>)~', '${1}8.75$2', $xml));
    $rows = grading_doc_excel($path);
    check(array_column($rows, 'diem') === ['8.75', '8.75', '8.75'], 'Read scores filled in the exported bag');
    check(!has_errors(grading_kiem_tra_import($rows, $ky, $mon, $phong, $tui)), 'Valid file resolves to the selected bag');
    $wrongRoom = $rows; $wrongRoom[0]['phong'] = 'P101';
    check(has_errors(grading_kiem_tra_import($wrongRoom, $ky, $mon, $phong, $tui)), 'Reject edited room column');
    $wrongCode = $rows; $wrongCode[0]['phach'] = 'BT049';
    check(has_errors(grading_kiem_tra_import($wrongCode, $ky, $mon, $phong, $tui)), 'Only anonymous code is accepted, not paper identifier');
    $wrongBag = grading_kiem_tra_import($rows, $ky, $mon, 'P101', 'LO_P101_TOAN');
    check(has_errors($wrongBag), 'Reject papers from another bag');
    check(has_errors(grading_kiem_tra_import($rows, $ky, 'NN', $phong, 'LO_P202_NN')), 'Reject file in another subject');
    $bad = $rows; $bad[0]['diem'] = '8.3';
    check(has_errors(grading_kiem_tra_import($bad, $ky, $mon, $phong, $tui)), 'Reject score outside quarter increments');
    foreach (['11', 'abc', '-1'] as $value) {
        $bad[0]['diem'] = $value;
        check(has_errors(grading_kiem_tra_import($bad, $ky, $mon, $phong, $tui)), 'Reject invalid score ' . $value);
    }
    check(has_errors(grading_kiem_tra_import([$rows[0], $rows[0]], $ky, $mon, $phong, $tui)), 'Reject duplicate paper');
    rejects(fn() => grading_luu_import($wrongRoom, $ky, $mon, $phong, $tui, 'Test UC05'), 'Confirmation validates room again');
    // Force the second INSERT to fail, after the first paper has been saved inside the transaction.
    $pdo->exec("CREATE TRIGGER uc05_excel_test_fail BEFORE INSERT ON KetQuaChamThi FOR EACH ROW BEGIN IF NEW.maBaiThi = 'BT049' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'UC05 test rollback'; END IF; END");
    try {
        rejects(fn() => grading_luu_import($rows, $ky, $mon, $phong, $tui, 'Test UC05'), 'Later database failure rolls back the bag');
    } finally { $pdo->exec('DROP TRIGGER IF EXISTS uc05_excel_test_fail'); }
    check((int)$pdo->query("SELECT COUNT(*) FROM KetQuaChamThi WHERE maBaiThi IN ('BT046','BT049','BT052')")->fetchColumn() === 0, 'No partial scores remain after rollback');
    check((int)$pdo->query("SELECT COUNT(*) FROM BaiThi WHERE maBaiThi IN ('BT046','BT049','BT052') AND trangThaiCham='Chưa chấm'")->fetchColumn() === 3, 'Paper statuses roll back too');
    rejects(fn() => grading_xuat_tui_excel(grading_bai_trong_tui($ky, 'NN', $phong, 'LO_P202_NN')), 'Fully graded bag has no new score file');
    $withoutCode = $papers; $withoutCode[0]['soPhach'] = null;
    rejects(fn() => grading_xuat_tui_excel($withoutCode), 'Do not export papers before anonymous codes are ready');
    edit_sheet($path, static fn($xml) => str_replace('<c r="C2" t="inlineStr">', '<c r="C2" t="inlineStr"><f>1+1</f>', $xml));
    rejects(fn() => grading_doc_excel($path), 'Reject formulas');
    echo "UC05 Excel: $count checks passed.\n";
} finally {
    if ($path !== '') @unlink($path);
}
