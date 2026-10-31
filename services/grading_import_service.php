<?php
declare(strict_types=1);
require_once __DIR__ . '/grading_service.php';

/** Read only the three input columns from the first worksheet; never evaluate formulas. */
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
                if (!preg_match('/^([ABC])[1-9][0-9]*$/', $ref, $m)) continue;
                if ($cell->xpath('./*[local-name()="f"]')) throw new GradingException('Không dùng công thức trong cột phòng thi, số phách hoặc điểm.');
                $v = $cell->xpath('./*[local-name()="v"]');
                $value = $v ? (string)$v[0] : '';
                if ((string)$cell['t'] === 's') $value = $strings[(int)$value] ?? '';
                if ((string)$cell['t'] === 'inlineStr') $value = implode('', array_map('strval', $cell->xpath('.//*[local-name()="t"]')));
                $cells[$m[1]] = trim($value);
            }
            if (implode('', $cells) === '') continue;
            $rows[] = ['dong' => (int)$row['r'], 'phong' => $cells['A'] ?? '', 'phach' => $cells['B'] ?? '', 'diem' => $cells['C'] ?? ''];
        }
        $header = array_shift($rows);
        if (!$header || $header['phong'] !== 'Phòng thi' || $header['phach'] !== 'Số phách' || $header['diem'] !== 'Điểm') throw new GradingException('Giữ nguyên ba cột trong file túi bài: Phòng thi, Số phách, Điểm.');
        if (!$rows) throw new GradingException('File Excel chưa có điểm để nhập.');
        return $rows;
    } catch (BusinessException $e) { throw $e; }
    catch (Throwable $e) { throw new GradingException('Không đọc được Excel. Vui lòng dùng file .xlsx hợp lệ.'); }
    finally { unset($zip); @unlink($zipPath); }
}

/** Rooms and bags are resolved through LoBaiThi, without joining candidate identities. */
function grading_ds_phong_nhap_diem(string $ky, string $mon): array {
    $st = db()->prepare("SELECT DISTINCT p.maPhong, CONCAT(p.tenPhong, ' (', p.maPhong, ')') AS tenHienThi
        FROM PhongThi p JOIN LoBaiThi l ON l.maPhong = p.maPhong
        JOIN BaiThi b ON b.maLoBaiThi = l.maLoBaiThi
        WHERE b.maKyThi = ? AND b.maMonThi = ? AND l.maMonThi = b.maMonThi ORDER BY p.maPhong");
    $st->execute([$ky, $mon]);
    return $st->fetchAll();
}

function grading_ds_tui_bai(string $ky, string $mon, string $phong): array {
    $st = db()->prepare('SELECT l.maLoBaiThi, l.maPhong, p.tenPhong, COUNT(*) AS soBai,
        SUM(kq.maKetQuaChamThi IS NULL) AS chuaNhap
        FROM LoBaiThi l JOIN PhongThi p ON p.maPhong = l.maPhong
        JOIN BaiThi b ON b.maLoBaiThi = l.maLoBaiThi AND b.maMonThi = l.maMonThi
        LEFT JOIN KetQuaChamThi kq ON kq.maBaiThi = b.maBaiThi
        WHERE b.maKyThi = ? AND b.maMonThi = ? AND l.maPhong = ?
        GROUP BY l.maLoBaiThi, l.maPhong, p.tenPhong ORDER BY l.maLoBaiThi');
    $st->execute([$ky, $mon, $phong]);
    return $st->fetchAll();
}

function grading_bai_trong_tui(string $ky, string $mon, string $phong, string $tui): array {
    if ($ky === '' || $mon === '' || $phong === '' || $tui === '') {
        throw new GradingException('Vui lòng chọn kỳ thi, môn thi, phòng thi và túi bài thi.');
    }
    $st = db()->prepare('SELECT b.maBaiThi, b.soPhach, b.trangThaiCham, l.maPhong, m.thangDiem,
        kq.maKetQuaChamThi, kq.trangThaiCongBo, kq.diemThi
        FROM BaiThi b JOIN LoBaiThi l ON l.maLoBaiThi = b.maLoBaiThi AND l.maMonThi = b.maMonThi
        JOIN MonThi m ON m.maMonThi = b.maMonThi
        LEFT JOIN KetQuaChamThi kq ON kq.maBaiThi = b.maBaiThi
        WHERE b.maKyThi = ? AND b.maMonThi = ? AND l.maPhong = ? AND l.maLoBaiThi = ?
        ORDER BY b.soPhach, b.maBaiThi');
    $st->execute([$ky, $mon, $phong, $tui]);
    $rows = $st->fetchAll();
    if (!$rows) throw new GradingException('Không tìm thấy túi bài trong kỳ thi, môn thi và phòng thi đã chọn.');
    return $rows;
}

function grading_kiem_tra_import(array $rows, string $ky, string $mon, string $phong, string $tui): array {
    $papers = [];
    foreach (grading_bai_trong_tui($ky, $mon, $phong, $tui) as $bai) {
        if ($bai['soPhach'] !== null && trim($bai['soPhach']) !== '') $papers[$bai['soPhach']] = $bai;
    }
    if (!$rows || count($rows) > 500) throw new GradingException('File phải có từ 1 đến 500 bài thi.');
    $seen = []; $result = [];
    foreach ($rows as $row) {
        $item = $row + ['loi' => ''];
        try {
            if ($row['phong'] !== $phong) throw new GradingException('Phòng thi trong file không trùng phòng thi đã chọn.');
            $bai = $papers[$row['phach']] ?? null;
            if (!$bai) throw new GradingException('Số phách không thuộc túi bài đã chọn hoặc chưa được đánh phách.');
            if (isset($seen[$bai['maBaiThi']])) throw new GradingException('Bài thi bị trùng trong file.');
            $seen[$bai['maBaiThi']] = true;
            kiemTraNhapDiem($bai, $row['diem']);
            $item['ma'] = $bai['maBaiThi'];
        } catch (BusinessException $e) { $item['loi'] = $e->getMessage(); }
        $result[] = $item;
    }
    return $result;
}

function grading_luu_import(array $rows, string $ky, string $mon, string $phong, string $tui, string $nguoi): int {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Lock membership while checking the selected bag again at confirmation time.
        $lock = $pdo->prepare('SELECT b.maBaiThi FROM BaiThi b JOIN LoBaiThi l ON l.maLoBaiThi = b.maLoBaiThi
            WHERE b.maKyThi = ? AND b.maMonThi = ? AND l.maPhong = ? AND l.maLoBaiThi = ?
            ORDER BY b.maBaiThi FOR UPDATE');
        $lock->execute([$ky, $mon, $phong, $tui]);
        $lock->fetchAll();
        $checked = grading_kiem_tra_import($rows, $ky, $mon, $phong, $tui);
        if (array_filter($checked, static fn($r) => $r['loi'] !== '')) {
            throw new GradingException('Dữ liệu túi bài đã thay đổi hoặc file có lỗi. Không lưu điểm nào; vui lòng kiểm tra lại file.');
        }
        usort($checked, static fn($a, $b) => strcmp($a['ma'], $b['ma']));
        foreach ($checked as $row) nhapDiemThi($row['ma'], $row['diem'], $nguoi, $ky, $mon);
        $pdo->commit();
        return count($checked);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

/** Create a real XLSX for the selected bag; only room, anonymous code and blank score leave the server. */
function grading_xuat_tui_excel(array $papers, bool $bangDiem = false): string {
    $pending = $bangDiem ? $papers : array_values(array_filter($papers, 'baiThiCanNhapDiem'));
    if (!$pending) throw new GradingException('Túi bài đã nhập đủ điểm. Không có bài thi cần nhập điểm.');
    if (count($pending) > 500) throw new GradingException('Túi bài vượt giới hạn 500 bài cho một file Excel.');
    foreach ($pending as $bai) {
        if (trim((string)$bai['soPhach']) === '') throw new GradingException('Túi bài có bài chưa đánh phách. Vui lòng hoàn tất đánh phách trước khi tải.');
    }
    $path = sys_get_temp_dir() . '/tui-bai-' . bin2hex(random_bytes(16)) . '.zip';
    try {
        $zip = new PharData($path);
        $zip['[Content_Types].xml'] = '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>';
        $zip['_rels/.rels'] = '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
        $zip['xl/workbook.xml'] = '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Túi bài thi" sheetId="1" r:id="rId1"/></sheets></workbook>';
        $zip['xl/_rels/workbook.xml.rels'] = '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>';
        $data = [['Phòng thi', 'Số phách', 'Điểm']];
        foreach ($pending as $bai) $data[] = [$bai['maPhong'], $bai['soPhach'], $bangDiem && $bai['diemThi'] !== null ? grading_fmt_diem($bai['diemThi']) : ''];
        $xml = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cols><col min="1" max="3" width="24" customWidth="1"/></cols><sheetData>';
        foreach ($data as $i => $values) {
            $n = $i + 1; $xml .= '<row r="' . $n . '">';
            foreach ($values as $j => $value) {
                $xml .= '<c r="' . chr(65 + $j) . $n . '" t="inlineStr"><is><t>' . htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></is></c>';
            }
            $xml .= '</row>';
        }
        $zip['xl/worksheets/sheet1.xml'] = $xml . '</sheetData></worksheet>';
        unset($zip);
        return $path;
    } catch (Throwable $e) { unset($zip); @unlink($path); throw $e; }
}
