<?php
declare(strict_types=1);
/**
 * UC12 – Xem lịch sử chỉnh sửa điểm (Actor: Hội đồng chấm thi).
 * Luồng (báo cáo giữa kỳ): chọn Kỳ thi, Môn thi, nhập Mã phách → tra cứu → hiện kết quả chấm
 * → chọn “Xem lịch sử chỉnh sửa” → hiện lịch sử.
 */
require_once dirname(__DIR__) . '/includes/grading_ui.php';

$maKyThi = ui_input('kyThi');
$maMonThi = ui_input('monThi');
$soPhach = grading_chuan_hoa_phach(ui_input('soPhach'));
$daTraCuu = isset($_GET['traCuu']);
$xemLichSu = isset($_GET['xemLichSu']);

$dsKyThi = $dsMonThi = [];
$loi = null; $loiHeThong = null; $ketQua = null; $lichSu = null;
try {
    $dsKyThi = grading_ds_ky_thi();
    $dsMonThi = grading_ds_mon_thi();
    if ($daTraCuu) {
        $ketQua = timBaiThi($maKyThi, $maMonThi, $soPhach);
        if ($xemLichSu) $lichSu = layLichSuChinhSua($ketQua['maKetQuaChamThi']);
    }
} catch (GradingException $e) {
    $loi = $e->getMessage();
} catch (Throwable $e) {
    error_log('UC12: ' . $e->getMessage());
    $loiHeThong = TB_LOI_TRUY_XUAT_LICH_SU;
}
$q = ['traCuu' => 1, 'kyThi' => $maKyThi, 'monThi' => $maMonThi, 'soPhach' => $soPhach];

ui_page_start('grading', 'Xem lịch sử chỉnh sửa điểm', 'Chấm thi · Lịch sử chỉnh sửa', 'Chọn kỳ thi, môn thi và nhập mã phách để xem kết quả chấm hiện tại, sau đó chọn “Xem lịch sử chỉnh sửa”.', 'UC12');
grading_tabs('grading', 'lich-su.php');
ui_flashes();
if ($loiHeThong) ui_notice('error', $loiHeThong);
?>
<section class="panel">
  <div class="panel-heading"><div><h2>Tiêu chí tra cứu</h2><p>Các trường có dấu * là bắt buộc.</p></div></div>
  <form method="get" class="ui-search" novalidate>
    <input type="hidden" name="traCuu" value="1">
    <?php ui_select('kyThi', 'Kỳ thi', $dsKyThi, 'maKyThi', 'tenKyThi', $maKyThi, '-- Chọn kỳ thi --'); ?>
    <?php ui_select('monThi', 'Môn thi', $dsMonThi, 'maMonThi', 'tenMonThi', $maMonThi, '-- Chọn môn thi --'); ?>
    <div class="field"><label for="soPhach">Mã phách <span>*</span></label><input id="soPhach" name="soPhach" value="<?= e($soPhach) ?>" placeholder="Ví dụ: P0052" maxlength="20" autocomplete="off"></div>
    <div class="field ui-search-btn"><button class="btn btn-primary" type="submit">⌕ Tra cứu</button></div>
  </form>
</section>

<?php if ($loi) ui_notice($loi === TB_TRA_CUU_THIEU ? 'warning' : 'error', $loi); ?>

<?php if ($ketQua): ?>
<section class="panel">
  <div class="panel-heading"><div><h2>Kết quả chấm thi</h2><p><?= e($ketQua['tenKyThi']) ?></p></div></div>
  <dl class="ui-kv">
    <div><dt>Mã phách</dt><dd><span class="code-tag"><?= e($ketQua['soPhach']) ?></span></dd></div>
    <div><dt>Môn thi</dt><dd><?= e($ketQua['tenMonThi']) ?></dd></div>
    <div><dt>Điểm thi hiện tại</dt><dd class="ui-score"><?= e(grading_fmt_diem($ketQua['diemThi'])) ?> <small>/ <?= e(grading_fmt_diem($ketQua['thangDiem'])) ?></small></dd></div>
    <div><dt>Trạng thái công bố</dt><dd><?= ui_status_badge($ketQua['trangThaiCongBo']) ?></dd></div>
    <div><dt>Người nhập</dt><dd><?= e($ketQua['nguoiNhap']) ?></dd></div>
    <div><dt>Thời điểm nhập</dt><dd><?= e(ui_fmt_time($ketQua['thoiDiemNhap'])) ?></dd></div>
  </dl>
  <div class="ui-actions">
    <?php if ($lichSu === null): ?>
      <a class="btn btn-primary" href="?<?= e(http_build_query($q + ['xemLichSu' => 1])) ?>#lich-su">↺ Xem lịch sử chỉnh sửa</a>
    <?php endif; ?>
    <a class="btn btn-outline" href="<?= e(url('grading/cap-nhat.php') . '?soPhach=' . rawurlencode($ketQua['soPhach'])) ?>">Cập nhật kết quả chấm →</a>
  </div>
</section>

<?php if ($lichSu !== null): ?>
<section class="panel table-panel" id="lich-su">
  <div class="table-toolbar"><div class="table-title"><h2>Lịch sử chỉnh sửa điểm</h2><p>Mới nhất trước</p></div><span class="count-tag"><?= count($lichSu) ?> lần chỉnh sửa</span></div>
  <?php if (!$lichSu): ?>
    <div class="ui-pad"><?php ui_notice('info', TB_CHUA_CO_LICH_SU); ?></div>
  <?php else: ?>
  <div class="table-scroll"><table class="data-table">
    <thead><tr><th>STT</th><th>Điểm trước chỉnh sửa</th><th>Điểm sau chỉnh sửa</th><th>Thời gian chỉnh sửa</th><th>Người chỉnh sửa</th><th>Lý do chỉnh sửa</th></tr></thead>
    <tbody>
    <?php foreach ($lichSu as $i => $ls): ?>
      <tr><td class="muted"><?= $i + 1 ?></td><td><?= e(grading_fmt_diem($ls['diemTruocChinhSua'])) ?></td><td><strong><?= e(grading_fmt_diem($ls['diemSauChinhSua'])) ?></strong></td><td><?= e(ui_fmt_time($ls['thoiGianChinhSua'])) ?></td><td><?= e($ls['nguoiThucHien']) ?></td><td><?= e($ls['lyDoChinhSua']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>
<?php endif; ?>
<?php endif; ?>
<?php ui_page_end();
