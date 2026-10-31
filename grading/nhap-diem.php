<?php
declare(strict_types=1);
/** UC05: lọc túi bài → tải Excel → chấm/điền điểm trong file → import và xác nhận. */
require_once dirname(__DIR__) . '/includes/grading_ui.php';
require_once dirname(__DIR__) . '/services/grading_import_service.php';
$nguoiDung = require_role(['HD_CHAM_THI']);
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$maKyThi = ui_input('kyThi');
$maMonThi = ui_input('monThi');
$maPhong = ui_input('phongThi');
$maTui = ui_input('tuiBai');
$action = $isPost ? ui_input('action') : '';
$loi = ui_csrf_error();
$chiTietLoi = [];
$dsKyThi = $dsMonThi = $dsPhong = $dsTui = $dsBai = [];
$importPreview = null; $importToken = '';
try {
    $dsKyThi = grading_ds_ky_thi();
    $dsMonThi = grading_ds_mon_thi();
    if (!$isPost) {
        if ($maKyThi === '' && $dsKyThi) $maKyThi = (string)$dsKyThi[0]['maKyThi'];
        if ($maMonThi === '' && $dsMonThi) $maMonThi = (string)$dsMonThi[0]['maMonThi'];
    }
    $dsPhong = grading_ds_phong_nhap_diem($maKyThi, $maMonThi);
    $dsTui = grading_ds_tui_bai($maKyThi, $maMonThi, $maPhong);
    if (!$isPost && $maTui === '' && count($dsTui) === 1) $maTui = (string)$dsTui[0]['maLoBaiThi'];
    if ($maTui !== '') $dsBai = grading_bai_trong_tui($maKyThi, $maMonThi, $maPhong, $maTui);
    if (!$isPost && ui_input('download') === '1') {
        if (!$dsBai) throw new GradingException('Vui lòng chọn túi bài thi trước khi tải Excel.');
        $bangDiem = ui_input('loaiFile') === 'bang_diem';
        $file = grading_xuat_tui_excel($dsBai, $bangDiem);
        try {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . ($bangDiem ? 'bang-diem-' : 'tui-bai-') . preg_replace('/[^A-Za-z0-9_-]/', '_', $maTui) . '.xlsx"');
            header('Cache-Control: private, no-store');
            header('Content-Length: ' . filesize($file));
            readfile($file);
        } finally { unlink($file); }
        exit;
    }
    if ($isPost && !$loi) {
        if ($action === 'kiem_tra_excel') {
            unset($_SESSION['uc05_import']);
            $file = $_FILES['excel'] ?? [];
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 2 * 1024 * 1024
                || strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION)) !== 'xlsx') {
                throw new GradingException('Vui lòng chọn file Excel .xlsx, dung lượng tối đa 2 MB.');
            }
            $importPreview = grading_kiem_tra_import(grading_doc_excel($file['tmp_name']), $maKyThi, $maMonThi, $maPhong, $maTui);
            if (!array_filter($importPreview, static fn($r) => $r['loi'] !== '')) {
                $importToken = bin2hex(random_bytes(24));
                $_SESSION['uc05_import'] = ['token' => $importToken, 'rows' => $importPreview, 'ky' => $maKyThi, 'mon' => $maMonThi,
                    'phong' => $maPhong, 'tui' => $maTui, 'expires' => time() + 900];
            }
        } elseif ($action === 'luu_excel') {
            $pending = $_SESSION['uc05_import'] ?? null;
            unset($_SESSION['uc05_import']);
            if (!$pending || !hash_equals($pending['token'], ui_input('importToken')) || $pending['expires'] < time()
                || $pending['ky'] !== $maKyThi || $pending['mon'] !== $maMonThi || $pending['phong'] !== $maPhong || $pending['tui'] !== $maTui) {
                throw new GradingException('Phiên nhập Excel đã hết hạn hoặc khác túi bài. Vui lòng tải file và kiểm tra lại.');
            }
            $n = grading_luu_import($pending['rows'], $maKyThi, $maMonThi, $maPhong, $maTui,
                (string)($nguoiDung['hoTen'] ?? $nguoiDung['tenDangNhap']));
            flash_set('success', "Đã nhập điểm thành công cho {$n} bài thi từ Excel.");
            ui_redirect('grading/nhap-diem.php', ['kyThi' => $maKyThi, 'monThi' => $maMonThi, 'phongThi' => $maPhong, 'tuiBai' => $maTui]);
        } else {
            throw new GradingException('UC05 chỉ nhập điểm bằng file Excel của túi bài thi.');
        }
    }
} catch (BusinessException $e) {
    $loi = $loi ?: $e->getMessage(); $chiTietLoi = $e->details();
} catch (Throwable $e) {
    error_log('UC05 Excel: ' . $e->getMessage());
    $loi = 'Không thể xử lý túi bài thi. Không có điểm nào được lưu. Vui lòng thử lại.';
}
$phongHopLe = in_array($maPhong, array_column($dsPhong, 'maPhong'), true);
$soChuaNhap = count(array_filter($dsBai, 'baiThiCanNhapDiem'));
$scope = ['kyThi' => $maKyThi, 'monThi' => $maMonThi, 'phongThi' => $maPhong, 'tuiBai' => $maTui];
ui_page_start('grading', 'Nhập điểm thi', 'Chấm thi · Import Excel theo túi bài',
    'Lọc kỳ thi, môn thi, phòng thi → tải túi bài → điền điểm trong Excel → import lại để kiểm tra và lưu.', 'UC05');
grading_tabs('grading', 'nhap-diem.php');
ui_flashes();
if ($loi) ui_notice('error', $loi, $chiTietLoi);
?>
<section class="panel">
  <div class="panel-heading"><div><h2>1. Lọc túi bài thi</h2><p>Chọn kỳ thi, môn thi và phòng thi để tìm túi bài cần chấm.</p></div></div>
  <form method="get" class="ui-search">
    <?php ui_select('kyThi', 'Kỳ thi', $dsKyThi, 'maKyThi', 'tenKyThi', $maKyThi, '-- Chọn kỳ thi --'); ?>
    <?php ui_select('monThi', 'Môn thi', $dsMonThi, 'maMonThi', 'tenMonThi', $maMonThi, '-- Chọn môn thi --'); ?>
    <?php ui_select('phongThi', 'Phòng thi', $dsPhong, 'maPhong', 'tenHienThi', $maPhong, '-- Chọn phòng thi --'); ?>
    <div class="field ui-search-btn"><button class="btn btn-primary" type="submit">Lọc túi bài</button></div>
  </form>
</section>
<section class="panel">
  <div class="panel-heading"><div><h2>Danh sách túi bài thi</h2><p>Chọn một túi để tải file và import điểm trở lại đúng túi đó.</p></div></div>
  <?php if (!$dsTui): ?>
    <?php ui_notice('info', $maPhong === '' ? 'Vui lòng chọn phòng thi và bấm Lọc túi bài.' : 'Không có túi bài phù hợp với bộ lọc đã chọn.'); ?>
  <?php else: ?>
  <div class="table-wrap"><table class="data-table">
    <thead><tr><th>Túi bài thi</th><th>Phòng thi</th><th>Tổng số bài</th><th>Chưa nhập điểm</th><th>Thao tác</th></tr></thead><tbody>
    <?php foreach ($dsTui as $tui): ?>
      <tr><td><?= e($tui['maLoBaiThi']) ?></td><td><?= e(catalog_display_name($tui['tenPhong']) . ' (' . $tui['maPhong'] . ')') ?></td>
      <td><?= (int)$tui['soBai'] ?></td><td><?= (int)$tui['chuaNhap'] ?></td>
      <td><a class="btn btn-outline" href="<?= e(url('grading/nhap-diem.php', array_merge($scope, ['tuiBai' => $tui['maLoBaiThi'], 'taiNgay' => '1'])) . ((int)$tui['chuaNhap'] > 0 ? '#nhap-excel' : '#chi-tiet-tui')) ?>"><?= (int)$tui['chuaNhap'] > 0 ? 'Chọn túi bài' : 'Tải danh sách điểm' ?></a></td></tr>
    <?php endforeach; ?>
  </tbody></table></div>
  <?php endif; ?>
</section>
<?php if ($dsBai && $phongHopLe): ?>
<?php
$downloadQuery = $scope + ['download' => '1'];
if (!$soChuaNhap) $downloadQuery['loaiFile'] = 'bang_diem';
$downloadUrl = url('grading/nhap-diem.php', $downloadQuery);
?>
<?php if (!$isPost && ui_input('taiNgay') === '1'): ?>
  <iframe hidden title="Tải Excel của túi đã chọn" src="<?= e($downloadUrl) ?>"></iframe>
<?php endif; ?>
<?php if (!$soChuaNhap): ?>
<section class="panel" id="chi-tiet-tui">
  <div class="panel-heading"><div>
    <h2>Danh sách điểm đã nhập <?= e($maTui) ?></h2>
    <p>Túi đã nhập đủ <?= count($dsBai) ?> bài. File tải về là danh sách có điểm đã lưu; xem điểm bên dưới. Muốn điều chỉnh điểm, dùng UC13.</p>
  </div></div>
  <a class="btn btn-primary" href="<?= e($downloadUrl) ?>">Tải danh sách điểm (.xlsx)</a>
</section>
<?php endif; ?>
<?php if ($soChuaNhap || $importPreview !== null): ?>
<section class="panel" id="nhap-excel">
  <div class="panel-heading"><div><h2>2. Import lại túi bài đã điền điểm</h2>
    <p>Chọn file .xlsx của túi <?= e($maTui) ?> sau khi điền điểm. Tối đa 500 bài / 2 MB, không dùng công thức. Điểm theo thang môn thi, bước 0.25. Hệ thống kiểm tra từng dòng trước khi lưu.</p>
  </div></div>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php foreach ($scope as $name => $value): ?><input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>"><?php endforeach; ?>
    <input type="hidden" name="action" value="kiem_tra_excel">
    <div class="field"><label for="excel">File túi bài đã nhập điểm (.xlsx)</label><input id="excel" name="excel" type="file" accept=".xlsx" required<?= !$soChuaNhap ? ' disabled' : '' ?>></div>
    <div class="ui-actions"><button class="btn btn-primary" type="submit"<?= !$soChuaNhap ? ' disabled' : '' ?>>Import và kiểm tra file</button></div>
  </form>
  <?php if ($importPreview !== null): ?>
    <h3>Kết quả kiểm tra — chưa lưu điểm</h3>
    <div class="table-wrap"><table class="data-table">
      <thead><tr><th>Dòng Excel</th><th>Phòng thi</th><th>Số phách</th><th>Điểm</th><th>Kết quả</th></tr></thead><tbody>
      <?php foreach ($importPreview as $row): ?>
        <tr><td><?= (int)$row['dong'] ?></td><td><?= e($row['phong']) ?></td><td><?= e($row['phach']) ?></td><td><?= e($row['diem']) ?></td><td><?= e($row['loi'] ?: 'Hợp lệ') ?></td></tr>
      <?php endforeach; ?>
    </tbody></table></div>
    <?php if ($importToken !== ''): ?>
      <form method="post">
        <?= csrf_field() ?>
        <?php foreach ($scope as $name => $value): ?><input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>"><?php endforeach; ?>
        <input type="hidden" name="action" value="luu_excel">
        <input type="hidden" name="importToken" value="<?= e($importToken) ?>">
        <div class="ui-actions"><button class="btn btn-primary" type="submit">Xác nhận lưu <?= count($importPreview) ?> điểm</button>
          <a class="btn btn-outline" href="<?= e(url('grading/nhap-diem.php', $scope)) ?>">Hủy</a></div>
      </form>
    <?php else: ?><p role="alert">File có lỗi. Chưa lưu điểm nào; hãy sửa các dòng lỗi và import lại.</p><?php endif; ?>
  <?php endif; ?>
</section>
<?php endif; ?>
<section class="panel" id="ket-qua-diem">
  <div class="panel-heading"><div><h2>Điểm đã nhập của túi <?= e($maTui) ?></h2>
    <p>Đã nhập <?= count($dsBai) - $soChuaNhap ?> / <?= count($dsBai) ?> bài. Sau khi xác nhận import thành công, điểm được hiển thị tại đây.</p>
  </div></div>
  <div class="table-wrap"><table class="data-table">
    <thead><tr><th>Phòng thi</th><th>Số phách</th><th>Điểm</th></tr></thead><tbody>
    <?php foreach ($dsBai as $bai): ?>
      <tr><td><?= e($bai['maPhong']) ?></td><td><?= e($bai['soPhach'] ?? 'Chưa đánh phách') ?></td>
        <td><?= $bai['diemThi'] === null ? 'Chưa nhập' : e(grading_fmt_diem($bai['diemThi'])) ?></td></tr>
    <?php endforeach; ?>
  </tbody></table></div>
</section>
<?php else: ?>
<section class="panel"><h2>Tải file, import và xem điểm</h2><p>Chọn kỳ thi, môn thi và phòng thi rồi bấm Chọn túi bài để tải ngay file Điểm trống và mở chỗ upload. Túi đã nhập đủ điểm có nút Tải danh sách điểm.</p></section>
<?php endif; ?>
<?php ui_page_end(); ?>
