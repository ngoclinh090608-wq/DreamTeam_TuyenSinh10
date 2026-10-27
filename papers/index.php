<?php
declare(strict_types=1);
/**
 * UC09 – Tra cứu thông tin lô bài thi (Actor: Bộ phận cắt phách).
 * Luồng: nhập/chọn điều kiện → tra cứu → danh sách lô phù hợp → chọn lô → xem chi tiết để đối chiếu.
 */
require_once dirname(__DIR__) . '/includes/ui.php';
require_once dirname(__DIR__) . '/services/paper_batch_service.php';

$dk = ['maLo' => strtoupper(ui_input('maLo')), 'diemThi' => ui_input('diemThi'), 'phong' => ui_input('phong'), 'monThi' => ui_input('monThi')];
$daTraCuu = isset($_GET['traCuu']);
$maLoChon = ui_input('lo');

$dsDiemThi = $dsPhong = $dsMon = [];
$ketQua = null; $chiTiet = null;
$loi = null; $chiTietLoi = []; $loiChiTiet = null; $loiHeThong = null;
try {
    $dsDiemThi = lo_ds_diem_thi();
    $dsPhong = lo_ds_phong_thi();
    $dsMon = lo_ds_mon_thi();
} catch (Throwable $e) {
    error_log('UC09 danh muc: ' . $e->getMessage());
    $loiHeThong = 'Không thể kết nối cơ sở dữ liệu. Vui lòng kiểm tra MySQL và thử lại.';
}
if (!$loiHeThong && $daTraCuu) {
    try {
        $ketQua = timLoBaiThi($dk);
    } catch (BusinessException $e) {
        $loi = $e->getMessage(); $chiTietLoi = $e->details();
    } catch (Throwable $e) {
        error_log('UC09 tim: ' . $e->getMessage());
        $loi = 'Không thể tra cứu lô bài thi. Vui lòng thử lại.';
    }
}
if (!$loiHeThong && $maLoChon !== '') {
    try {
        $chiTiet = layThongTinLoBaiThi($maLoChon);
    } catch (Throwable $e) {
        if (!$e instanceof BusinessException) error_log('UC09 chi tiet: ' . $e->getMessage());
        $loiChiTiet = LO_KHONG_TRUY_XUAT;
    }
}
$q = array_filter($dk, fn($v) => $v !== '') + ['traCuu' => 1];

ui_page_start('papers', 'Tra cứu thông tin lô bài thi', 'Lô bài thi · Đối chiếu bàn giao', 'Nhập mã lô hoặc chọn điểm thi, phòng thi, môn thi để tìm lô bài thi, rồi xem chi tiết để đối chiếu với lô bài thi thực tế được bàn giao.', 'UC09');
if ($loiHeThong) ui_notice('error', $loiHeThong);
?>
<section class="panel">
  <div class="panel-heading"><div><h2>Thông tin tra cứu</h2><p>Nhập hoặc chọn ít nhất một thông tin.</p></div></div>
  <form method="get" class="ui-search ui-search-four" novalidate>
    <input type="hidden" name="traCuu" value="1">
    <div class="field"><label for="maLo">Mã lô bài thi</label><input id="maLo" name="maLo" value="<?= e($dk['maLo']) ?>" placeholder="Ví dụ: LO_P101_TOAN" maxlength="20" autocomplete="off"></div>
    <?php ui_select('diemThi', 'Điểm thi', $dsDiemThi, 'maDiemThi', 'tenDiemThi', $dk['diemThi'], '-- Tất cả --', false); ?>
    <?php ui_select('phong', 'Phòng thi', $dsPhong, 'maPhong', 'tenHienThi', $dk['phong'], '-- Tất cả --', false); ?>
    <?php ui_select('monThi', 'Môn thi', $dsMon, 'maMonThi', 'tenMonThi', $dk['monThi'], '-- Tất cả --', false); ?>
    <div class="field ui-search-btn"><button class="btn btn-primary" type="submit">⌕ Tra cứu</button></div>
  </form>
</section>

<?php if ($loi) ui_notice($loi === LO_KHONG_TIM_THAY ? 'info' : 'warning', $loi, $chiTietLoi); ?>

<?php if ($ketQua): ?>
<section class="panel table-panel">
  <div class="table-toolbar"><div class="table-title"><h2>Danh sách lô bài thi phù hợp</h2><p>Bấm “Xem” để xem chi tiết một lô.</p></div><span class="count-tag"><?= count($ketQua) ?> lô</span></div>
  <div class="table-scroll"><table class="data-table">
    <thead><tr><th>STT</th><th>Mã lô</th><th>Môn thi</th><th>Phòng thi</th><th>Điểm thi</th><th>Số bài</th><th>Đã đánh phách</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($ketQua as $i => $r): ?>
      <tr<?= $r['maLoBaiThi'] === $maLoChon ? ' class="ui-row-on"' : '' ?>><td class="muted"><?= $i + 1 ?></td><td><span class="code-tag"><?= e($r['maLoBaiThi']) ?></span></td><td><?= e($r['tenMonThi']) ?></td><td><?= e($r['tenPhong']) ?></td><td><?= e($r['tenDiemThi']) ?></td><td><strong><?= (int)$r['soBai'] ?></strong></td><td><?= (int)$r['soBaiCoPhach'] ?>/<?= (int)$r['soBai'] ?></td>
        <td><a class="btn btn-outline btn-sm" href="?<?= e(http_build_query($q + ['lo' => $r['maLoBaiThi']])) ?>#chi-tiet">Xem</a></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<?php endif; ?>

<?php if ($loiChiTiet): ?><div id="chi-tiet"><?php ui_notice('error', $loiChiTiet, ['Vui lòng chọn lô bài thi khác hoặc thử lại sau.']); ?></div><?php endif; ?>

<?php if ($chiTiet): ?>
<section class="panel" id="chi-tiet">
  <div class="panel-heading"><div><h2>Thông tin lô bài thi <span class="code-tag"><?= e($chiTiet['maLoBaiThi']) ?></span></h2><p><?= e($chiTiet['thongTinLoBaiThi']) ?></p></div></div>
  <dl class="ui-kv">
    <div><dt>Môn thi</dt><dd><?= e($chiTiet['tenMonThi']) ?></dd></div>
    <div><dt>Phòng thi</dt><dd><?= e($chiTiet['tenPhong']) ?> (<?= e($chiTiet['maPhong']) ?>)</dd></div>
    <div><dt>Điểm thi</dt><dd><?= e($chiTiet['tenDiemThi']) ?></dd></div>
    <div><dt>Số bài thi trong lô</dt><dd class="ui-score"><?= (int)$chiTiet['soBai'] ?> <small>bài</small></dd></div>
    <div><dt>Đã đánh phách</dt><dd><?= (int)$chiTiet['soBaiCoPhach'] ?>/<?= (int)$chiTiet['soBai'] ?> bài</dd></div>
    <div><dt>Sức chứa phòng</dt><dd><?= (int)$chiTiet['sucChua'] ?> thí sinh</dd></div>
  </dl>
</section>
<section class="panel table-panel">
  <div class="table-toolbar"><div class="table-title"><h2>Danh sách bài thi trong lô</h2><p>Dùng để đối chiếu với số bài thực tế được bàn giao.</p></div></div>
  <div class="table-scroll"><table class="data-table">
    <thead><tr><th>STT</th><th>Mã bài thi</th><th>Số báo danh</th><th>Số phách</th><th>Trạng thái chấm</th></tr></thead>
    <tbody>
    <?php if (!$chiTiet['baiThi']): ?><tr><td colspan="5" class="muted center">Lô này chưa có bài thi.</td></tr><?php endif; ?>
    <?php foreach ($chiTiet['baiThi'] as $i => $b): ?>
      <tr><td class="muted"><?= $i + 1 ?></td><td><?= e($b['maBaiThi']) ?></td><td><?= e($b['soBaoDanh'] ?? '—') ?></td><td><?= $b['soPhach'] ? '<span class="code-tag">' . e($b['soPhach']) . '</span>' : '<span class="muted">Chưa đánh phách</span>' ?></td><td><?= ui_status_badge($b['trangThaiCham']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<?php endif; ?>
<?php ui_page_end();
