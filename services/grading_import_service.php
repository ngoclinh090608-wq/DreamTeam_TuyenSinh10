<?php
declare(strict_types=1);
require_once __DIR__ . '/grading_service.php';

/** Read only the two input columns from the first worksheet; never evaluate formulas. */
function grading_doc_excel(string $path): array {
    $zipPath = sys_get_temp_dir() . '/uc05-' . bin2hex(random_bytes(16)) . '.zip';
    if (!copy($path, $zipPath)) throw new GradingException('Không thể đọc file Excel.');
    try {
        $zip = new PharData($zipPath);
        $read = static function (string $name) use ($zip): SimpleXMLElement {
            if (!isset($zip[$name]) || $zip[$name]->getSize() > 2 * 1024 * 1024) {
                throw new GradingException('File Excel thiếu dữ liệu hoặc vượt giới hạn cho phép.');
            }
            $xml = $zip[$name]->getContent();
            if (stripos($xml, '<!DOCTYPE') !== false || stripos($xml, '<!ENTITY') !== false) {
                throw new GradingException('File Excel chứa XML không được hỗ trợ.');
            }
            $old = libxml_use_internal_errors(true);
            try { $doc = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET); }
            finally { libxml_clear_errors(); libxml_use_internal_errors($old); }
            if ($doc === false) throw new GradingException('Cấu trúc Excel không hợp lệ.');
            return $doc;
        };
        $strings = [];
        if (isset($zip['xl/sharedStrings.xml'])) {
            foreach ($read('xl/sharedStrings.xml')->xpath('//*[local-name()="si"]') as $si) {
                $strings[] = implode('', array_map('strval', $si->xpath('.//*[local-name()="t"]')));
            }
        }
        $workbook = $read('xl/workbook.xml');
        $sheets = $workbook->xpath('//*[local-name()="sheet"]');
        if (!$sheets) throw new GradingException('File Excel không có trang tính.');
        $rid = (string)$sheets[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
        $target = '';
        foreach ($read('xl/_rels/workbook.xml.rels')->xpath('//*[local-name()="Relationship"]') as $rel) {
            if ((string)$rel['Id'] === $rid && (string)$rel['TargetMode'] !== 'External') $target = (string)$rel['Target'];
        }
        $sheetPath = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/' . $target;
        if (!preg_match('~^xl/worksheets/[A-Za-z0-9_.-]+\.xml$~', $sheetPath)) throw new GradingException('Trang tính Excel không được hỗ trợ.');
        $rows = [];
        foreach ($read($sheetPath)->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') as $row) {
            if (count($rows) >= 501) throw new GradingException('Mỗi lần chỉ được nhập tối đa 500 bài thi.');
            $cells = [];
            foreach ($row->xpath('./*[local-name()="c"]') as $cell) {
                $ref = (string)$cell['r'];
                if (!preg_match('/^([AB])[1-9][0-9]*$/', $ref, $m)) continue;
                if ($cell->xpath('./*[local-name()="f"]')) throw new GradingException('Không dùng công thức trong cột mã phách hoặc điểm.');
                $v = $cell->xpath('./*[local-name()="v"]');
                $value = $v ? (string)$v[0] : '';
                if ((string)$cell['t'] === 's') $value = $strings[(int)$value] ?? '';
                if ((string)$cell['t'] === 'inlineStr') $value = implode('', array_map('strval', $cell->xpath('.//*[local-name()="t"]')));
                $cells[$m[1]] = trim($value);
            }
            if (($cells['A'] ?? '') === '' && ($cells['B'] ?? '') === '') continue;
            $rows[] = ['dong' => (int)$row['r'], 'ma' => $cells['A'] ?? '', 'diem' => $cells['B'] ?? ''];
        }
        $header = array_shift($rows);
        if (!$header || $header['ma'] !== 'MaPhach' || $header['diem'] !== 'DiemThi') throw new GradingException('Dòng đầu phải có hai cột A: MaPhach và B: DiemThi.');
        if (!$rows) throw new GradingException('File Excel chưa có điểm để nhập.');
        return $rows;
    } catch (BusinessException $e) { throw $e; }
    catch (Throwable $e) { throw new GradingException('Không đọc được Excel. Vui lòng dùng file .xlsx hợp lệ.'); }
    finally { unset($zip); @unlink($zipPath); }
}

function grading_kiem_tra_import(array $rows, string $ky, string $mon): array {
    if ($ky === '' || $mon === '') throw new GradingException(TB_CHON_KY_MON);
    $seen = []; $result = [];
    foreach ($rows as $row) {
        $item = $row + ['loi' => ''];
        try {
            $bai = layThongTinBaiThiNhapDiem($row['ma'], $ky, $mon);
            if (isset($seen[$bai['maBaiThi']])) throw new GradingException('Bài thi bị trùng trong file.');
            $seen[$bai['maBaiThi']] = true;
            kiemTraNhapDiem($bai, $row['diem']);
            $item['ma'] = $bai['maBaiThi'];
            $item['phach'] = $bai['soPhach'];
        } catch (BusinessException $e) { $item['loi'] = $e->getMessage(); }
        $result[] = $item;
    }
    return $result;
}

function grading_luu_import(array $rows, string $ky, string $mon, string $nguoi): int {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        usort($rows, static fn($a, $b) => strcmp($a['ma'], $b['ma']));
        foreach ($rows as $row) nhapDiemThi($row['ma'], $row['diem'], $nguoi, $ky, $mon);
        $pdo->commit();
        return count($rows);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
