<?php
declare(strict_types=1);
/** UC14 – Thống kê kết quả chấm thi (Actor: Hội đồng chấm thi). Chỉ đọc dữ liệu. */
require_once dirname(__DIR__) . '/includes/grading_ui.php';

$maKyThi = ui_input('kyThi');
$maMonThi = ui_input('monThi');
$daThongKe = isset($_GET['thongKe']);

$dsKyThi = $dsMonThi = []; $tk = null; $loi = null; $loiHeThong = null;
try {
    $dsKyThi = grading_ds_ky_thi();
    $dsMonThi = grading_ds_mon_thi();
    if ($daThongKe) $tk = thongKeKetQuaChamThi($maKyThi, $maMonThi);
} catch (GradingException $e) {
    $loi = $e->getMessage();
} catch (Throwable $e) {
    error_log('UC14: ' . $e->getMessage());
    $loiHeThong = 'Không thể thực hiện thống kê kết quả chấm thi tại thời điểm hiện tại.';
}
$thang = null; $tenMon = '';
foreach ($dsMonThi as $m) if ($m['maMonThi'] === $maMonThi) { $thang = $m['thangDiem']; $tenMon = $m['tenMonThi']; }

ui_page_start('grading', 'Thống kê kết quả chấm thi', 'Chấm thi · Thống kê', 'Chọn kỳ thi và môn thi để xem tổng số bài đã chấm, điểm trung bình, điểm cao nhất và thấp nhất.', 'UC14');
grading_tabs('grading', 'thong-ke.php');
if ($loiHeThong) ui_notice('error', $loiHeThong);
?>
<section class="panel">
  <div class="panel-heading"><div><h2>Phạm vi thống kê</h2><p>Có thể đổi kỳ thi hoặc môn thi rồi bấm Thống kê lại.</p></div></div>
  <form method="get" class="ui-search ui-search-two" novalidate>
    <input type="hidden" name="thongKe" value="1">
    <?php ui_select('kyThi', 'Kỳ thi', $dsKyThi, 'maKyThi', 'tenKyThi', $maKyThi, '-- Chọn kỳ thi --'); ?>
    <?php ui_select('monThi', 'Môn thi', $dsMonThi, 'maMonThi', 'tenMonThi', $maMonThi, '-- Chọn môn thi --'); ?>
    <div class="field ui-search-btn"><button class="btn btn-primary" type="submit">▥ Thống kê</button></div>
  </form>
</section>

<?php if ($loi) ui_notice(str_starts_with($loi, 'Vui lòng') ? 'warning' : 'info', $loi); ?>

<?php if ($tk): ?>
<h2 class="ui-section-title">Kết quả thống kê môn <?= e($tenMon) ?></h2>
<div class="metric-grid ui-metrics">
  <div class="metric-card sky"><div class="metric-value"><?= (int)$tk['soBaiDaCham'] ?></div><div class="metric-label">Tổng số bài đã chấm</div><div class="metric-hint">trên <?= (int)$tk['tongBai'] ?> bài thi<?= (int)$tk['tongBai'] > (int)$tk['soBaiDaCham'] ? ' · còn ' . ((int)$tk['tongBai'] - (int)$tk['soBaiDaCham']) . ' bài chưa chấm' : '' ?></div></div>
  <div class="metric-card purple"><div class="metric-value"><?= e(grading_fmt_diem($tk['diemTrungBinh'])) ?></div><div class="metric-label">Điểm trung bình</div><div class="metric-hint">thang điểm <?= e(grading_fmt_diem($thang)) ?></div></div>
  <div class="metric-card mint"><div class="metric-value"><?= e(grading_fmt_diem($tk['diemCaoNhat'])) ?></div><div class="metric-label">Điểm cao nhất</div></div>
  <div class="metric-card orange"><div class="metric-value"><?= e(grading_fmt_diem($tk['diemThapNhat'])) ?></div><div class="metric-label">Điểm thấp nhất</div></div>
</div>
<?php endif; ?>
<?php ui_page_end();
