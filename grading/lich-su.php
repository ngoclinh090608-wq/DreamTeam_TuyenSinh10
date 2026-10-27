<?php
declare(strict_types=1);
/** UC12 – Xem lịch sử chỉnh sửa điểm (Actor: Hội đồng chấm thi). */
require_once dirname(__DIR__) . '/includes/grading_ui.php';

$maKyThi = ui_input('kyThi');
$maMonThi = ui_input('monThi');
$soPhach = grading_chuan_hoa_phach(ui_input('soPhach'));
$daTraCuu = isset($_GET['traCuu']);

$dsKyThi = $dsMonThi = [];
$loi = null; $loiHeThong = null; $ketQua = null; $lichSu = [];
try {
    $dsKyThi = grading_ds_ky_thi();
    $dsMonThi = grading_ds_mon_thi();
    if ($daTraCuu) {
        $ketQua = timBaiThi($maKyThi, $maMonThi, $soPhach);
        $lichSu = layLichSuChinhSua($ketQua['maKetQuaChamThi']);
    }
} catch (GradingException $e) {
    $loi = $e->getMessage();
} catch (Throwable $e) {
    error_log('UC12: ' . $e->getMessage());
    $loiHeThong = 'Không thể truy xuất dữ liệu kết quả chấm thi.';
}

ui_page_start('grading', 'Xem lịch sử chỉnh sửa điểm', 'Chấm thi · Lịch sử chỉnh sửa', 'Chọn kỳ thi, môn thi và nhập mã phách để xem kết quả chấm hiện tại và các lần chỉnh sửa điểm.', 'UC12');
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

<?php if ($loi) ui_notice($loi === 'Vui lòng chọn đầy đủ điều kiện tra cứu.' ? 'warning' : 'error', $loi); ?>

<?php if ($ketQua): ?>
<section class="panel">
  <div class="panel-heading"><div><h2>Kết quả chấm thi hiện tại</h2><p>Mã phách <span class="code-tag"><?= e($ketQua['soPhach']) ?></span></p></div><?= ui_status_badge($ketQua['trangThaiCongBo']) ?></div>
  <dl class="ui-kv">
    <div><dt>Kỳ thi</dt><dd><?= e($ketQua['tenKyThi']) ?></dd></div>
    <div><dt>Môn thi</dt><dd><?= e($ketQua['tenMonThi']) ?></dd></div>
    <div><dt>Điểm hiện tại</dt><dd class="ui-score"><?= e(grading_fmt_diem($ketQua['diemThi'])) ?> <small>/ <?= e(grading_fmt_diem($ketQua['thangDiem'])) ?></small></dd></div>
    <div><dt>Người nhập</dt><dd><?= e($ketQua['nguoiNhap']) ?></dd></div>
    <div><dt>Thời điểm nhập</dt><dd><?= e(ui_fmt_time($ketQua['thoiDiemNhap'])) ?></dd></div>
    <div><dt>Trạng thái công bố</dt><dd><?= e($ketQua['trangThaiCongBo']) ?></dd></div>
  </dl>
</section>

<section class="panel table-panel">
  <div class="table-toolbar"><div class="table-title"><h2>Lịch sử chỉnh sửa điểm</h2><p>Bảng LichSuChinhSuaDiem · mới nhất trước</p></div><span class="count-tag"><?= count($lichSu) ?> lần chỉnh sửa</span></div>
  <?php if (!$lichSu): ?>
    <div class="ui-pad"><?php ui_notice('info', 'Kết quả chấm thi này chưa có lịch sử chỉnh sửa điểm.'); ?></div>
  <?php else: ?>
  <div class="table-scroll"><table class="data-table">
    <thead><tr><th>STT</th><th>Thời gian chỉnh sửa</th><th>Điểm trước</th><th>Điểm sau</th><th>Người thực hiện</th><th>Lý do chỉnh sửa</th></tr></thead>
    <tbody>
    <?php foreach ($lichSu as $i => $ls): ?>
      <tr><td class="muted"><?= $i + 1 ?></td><td><?= e(ui_fmt_time($ls['thoiGianChinhSua'])) ?></td><td><?= e(grading_fmt_diem($ls['diemTruocChinhSua'])) ?></td><td><strong><?= e(grading_fmt_diem($ls['diemSauChinhSua'])) ?></strong></td><td><?= e($ls['nguoiThucHien']) ?></td><td><?= e($ls['lyDoChinhSua']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
  <div class="table-bottom"><span>Muốn sửa điểm bài này?</span><a class="btn btn-outline btn-sm" href="<?= e(url('grading/cap-nhat.php') . '?soPhach=' . rawurlencode($ketQua['soPhach'])) ?>">Cập nhật kết quả chấm →</a></div>
</section>
<?php endif; ?>
<?php ui_page_end();
