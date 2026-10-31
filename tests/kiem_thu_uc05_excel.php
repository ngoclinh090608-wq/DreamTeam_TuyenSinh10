<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/services/grading_import_service.php';

function check(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS: $message\n";
}
function rejects(callable $fn, string $message): void {
    try { $fn(); } catch (BusinessException $e) { check(true, $message); return; }
    throw new RuntimeException($message);
}

$tmp = tempnam(sys_get_temp_dir(), 'uc05-');
$zipPath = $tmp . '.xlsx';
try {
    copy(dirname(__DIR__) . '/assets/templates/mau-nhap-diem.xlsx', $zipPath);
    rejects(fn() => grading_doc_excel($zipPath), 'Empty template cannot save scores');
    $zip = new PharData($zipPath);
    $sheet = $zip['xl/worksheets/sheet1.xml']->getContent();
    $row = '<row r="2"><c r="A2" t="inlineStr"><is><t>P0030</t></is></c><c r="B2"><v>8.75</v></c></row>';
    $zip['xl/worksheets/sheet1.xml'] = str_replace('</sheetData>', $row . '</sheetData>', $sheet);
    unset($zip);
    $rows = grading_doc_excel($zipPath);
    check(count($rows) === 1 && $rows[0]['ma'] === 'P0030' && $rows[0]['diem'] === '8.75', 'Read real XLSX inline text and numeric score');
    $zip = new PharData($zipPath);
    $zip['xl/sharedStrings.xml'] = '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><si><t>MaPhach</t></si><si><t>DiemThi</t></si><si><t>P0030</t></si></sst>';
    $zip['xl/worksheets/sheet1.xml'] = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c></row><row r="2"><c r="A2" t="s"><v>2</v></c><c r="B2"><v>8.75</v></c></row></sheetData></worksheet>';
    unset($zip);
    check(grading_doc_excel($zipPath) === $rows, 'Read shared strings written by Excel');
    $valid = grading_kiem_tra_import($rows, 'KT2026', 'TOAN');
    check($valid[0]['loi'] === '' && $valid[0]['ma'] === 'BT046', 'Resolve phach in selected exam and subject');
    $invalid = grading_kiem_tra_import([['dong' => 2, 'ma' => 'P0030', 'diem' => '8.3']], 'KT2026', 'TOAN');
    check($invalid[0]['loi'] !== '', 'Reject score outside quarter-point increments');
    $duplicate = grading_kiem_tra_import([$rows[0], $rows[0]], 'KT2026', 'TOAN');
    check($duplicate[1]['loi'] !== '', 'Reject duplicate exam paper');
    $wrong = grading_kiem_tra_import($rows, 'KT2026', 'VAN');
    check($wrong[0]['loi'] !== '', 'Reject paper outside selected subject');
    $pdo = db();
    $before = $pdo->query("SELECT COUNT(*) FROM KetQuaChamThi WHERE maBaiThi='BT046'")->fetchColumn();
    rejects(fn() => grading_luu_import([$valid[0], $valid[0]], 'KT2026', 'TOAN', 'Test UC05'), 'Atomic rollback when a later row fails');
    check($pdo->query("SELECT COUNT(*) FROM KetQuaChamThi WHERE maBaiThi='BT046'")->fetchColumn() === $before, 'Rollback leaves no partial score');
    check($pdo->query("SELECT trangThaiCham FROM BaiThi WHERE maBaiThi='BT046'")->fetchColumn() === 'Chưa chấm', 'Rollback restores paper status');
    $zip = new PharData($zipPath);
    $zip['xl/worksheets/sheet1.xml'] = str_replace('<v>8.75</v>', '<f>1+1</f><v>2</v>', str_replace('</sheetData>', $row . '</sheetData>', $sheet));
    unset($zip);
    rejects(fn() => grading_doc_excel($zipPath), 'Reject formulas instead of using cached scores');
    echo "All UC05 Excel service tests passed.\n";
} finally {
    @unlink($tmp); @unlink($zipPath);
}
