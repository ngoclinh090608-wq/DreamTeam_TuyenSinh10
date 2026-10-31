<?php
declare(strict_types=1);
/**
 * UC15 – Công bố điểm thi (Actor: Ban Tuyển sinh Sở GD&ĐT).
 * Luồng (báo cáo giữa kỳ): chọn Kỳ thi, Môn thi → Tra cứu → danh sách kết quả + nút “Công bố điểm thi”
 * → kiểm tra điều kiện → hộp xác nhận (Xác nhận / Hủy bỏ) → cập nhật “Đã công bố”.
 */
require_once dirname(__DIR__) . '/includes/grading_ui.php';
$nguoiDung = require_role(['BAN_TUYEN_SINH']);   // UC15 – Ban Tuyển sinh Sở GD&ĐT

$maKyThi = ui_input('kyThi');
$maMonThi = ui_input('monThi');
$daChon = isset($_GET['xem']) || $_SERVER['REQUEST_METHOD'] === 'POST';
$buocXacNhan = ($_GET['buoc'] ?? '') === 'xac-nhan';

$loi = null; $loiChiTiet = []; $loiHeThong = ui_csrf_error();

if (!$loiHeThong && ($_POST['action'] ?? '') === 'cong_bo') {
    try {
        $n = capNhatTrangThaiCongBo($maKyThi, $maMonThi);
        flash_set('success', TB_CONG_BO_THANH_CONG);
        flash_set('info', "{$n} bài thi đã chuyển sang trạng thái “Đã công bố”, thí sinh có thể tra cứu điểm thi.");
        ui_redirect('publication/diem-thi.php', ['kyThi' => $maKyThi, 'monThi' => $maMonThi, 'xem' => 1]);
    } catch (GradingException $e) {
        $loi = $e->getMessage(); $loiChiTiet = $e->details();
    }
}

$dsKyThi = $dsMonThi = $tongQuan = []; $pv = null;
try {
    $dsKyThi = grading_ds_ky_thi();
    $dsMonThi = grading_ds_mon_thi();
    $tongQuan = layTongQuanCongBo();
    if ($daChon) $pv = layKetQuaTheoPhamVi($maKyThi, $maMonThi);
} catch (GradingException $e) {
    $loi = $loi ?? $e->getMessage();
} catch (Throwable $e) {
    error_log('UC15: ' . $e->getMessage());
    $loiHeThong = $loiHeThong ?? 'Không thể truy xuất dữ liệu kết quả chấm thi. Vui lòng thử lại sau.';
}
$tenKy = $tenMon = '';
foreach ($dsKyThi as $k) if ($k['maKyThi'] === $maKyThi) $tenKy = $k['tenKyThi'];
foreach ($dsMonThi as $m) if ($m['maMonThi'] === $maMonThi) $tenMon = $m['tenMonThi'];
$q = ['kyThi' => $maKyThi, 'monThi' => $maMonThi, 'xem' => 1];
// Bước 6: chỉ kiểm tra điều kiện khi người dùng đã chọn “Công bố điểm thi”.
if ($pv && $buocXacNhan && $pv['loiCongBo'] !== null && !$loi) {
    [$loi, $goiY] = $pv['loiCongBo'];
    $loiChiTiet = $goiY ? [$goiY] : [];
}

ui_page_start('publication', 'Công bố điểm thi', 'Công bố · Điểm thi', 'Chọn kỳ thi và môn thi, tra cứu danh sách kết quả rồi công bố để thí sinh tra cứu điểm. Điểm thi không thay đổi khi công bố.', 'UC15');
grading_tabs('publication', 'diem-thi.php');
ui_flashes();
if (isset($_GET['huy'])) ui_notice('info', 'Đã hủy công bố. Trạng thái kết quả chấm thi được giữ nguyên.');
if ($loiHeThong) ui_notice('error', $loiHeThong);
?>
<section class="panel">
  <div class="panel-heading"><div><h2>Chọn phạm vi công bố</h2><p>Các trường có dấu * là bắt buộc.</p></div></div>
  <form method="get" class="ui-search ui-search-two" novalidate>
    <input type="hidden" name="xem" value="1">
    <?php ui_select('kyThi', 'Kỳ thi', $dsKyThi, 'maKyThi', 'tenKyThi', $maKyThi, '-- Chọn kỳ thi --'); ?>
    <?php ui_select('monThi', 'Môn thi', $dsMonThi, 'maMonThi', 'tenMonThi', $maMonThi, '-- Chọn môn thi --'); ?>
    <div class="field ui-search-btn"><button class="btn btn-primary" type="submit">⌕ Tra cứu</button></div>
  </form>
</section>

<?php if ($loi) ui_notice(in_array($loi, [TB_CHON_KY_MON, TB_CHUA_DU_DIEU_KIEN], true) ? 'warning' : ($loi === TB_DA_CONG_BO ? 'info' : 'error'), $loi, $loiChiTiet); ?>

<?php if ($pv): $tk = $pv['thongKe']; ?>
  <?php if ($buocXacNhan && $pv['loiCongBo'] === null): ?>
  <section class="panel ui-confirm-panel" id="xac-nhan">
    <div class="panel-heading"><div><h2>Xác nhận công bố điểm thi</h2><p>Sau khi công bố, thí sinh có thể tra cứu điểm thi.</p></div></div>
    <p class="ui-confirm">Bạn có chắc chắn muốn công bố điểm thi môn <strong><?= e($tenMon) ?></strong> – <?= e($tenKy) ?>?<br><?= $tk['chuaCongBo'] ?> bài thi sẽ chuyển sang trạng thái “Đã công bố”.</p>
    <form method="post" action="?<?= e(http_build_query($q)) ?>" class="ui-actions">
      <?= csrf_field() ?>
      <input type="hidden" name="kyThi" value="<?= e($maKyThi) ?>">
      <input type="hidden" name="monThi" value="<?= e($maMonThi) ?>">
      <button class="btn btn-primary" type="submit" name="action" value="cong_bo">✓ Xác nhận công bố</button>
      <a class="btn btn-outline ui-btn-cancel" href="?<?= e(http_build_query($q + ['huy' => 1])) ?>">Hủy bỏ</a>
    </form>
  </section>
  <?php endif; ?>

  <section class="panel table-panel">
    <div class="table-toolbar">
      <div class="table-title"><h2>Danh sách kết quả chấm thi · <?= e($tenMon) ?></h2><p><?= e($tenKy) ?> · <?= $tk['tong'] ?> bài, đã chấm <?= $tk['daCham'] ?>, đã công bố <?= $tk['daCongBo'] ?></p></div>
      <div class="toolbar-right">
        <?php if (!($buocXacNhan && $pv['loiCongBo'] === null)): ?>
          <a class="btn btn-primary" href="?<?= e(http_build_query($q + ['buoc' => 'xac-nhan'])) ?>#xac-nhan">◉ Công bố điểm thi</a>
        <?php endif; ?>
      </div>
    </div>
    <div class="table-scroll"><table class="data-table">
      <thead><tr><th>STT</th><th>Mã phách</th><th>Mã thí sinh</th><th>Điểm</th><th>Trạng thái chấm</th><th>Trạng thái công bố</th></tr></thead>
      <tbody>
      <?php if (!$pv['rows']): ?><tr><td colspan="6" class="muted center">Chưa có bài thi.</td></tr><?php endif; ?>
      <?php foreach ($pv['rows'] as $i => $r): ?>
        <tr><td class="muted"><?= $i + 1 ?></td><td><span class="code-tag"><?= e($r['soPhach'] ?? '—') ?></span></td><td><?= e($r['maThiSinh']) ?></td><td><strong><?= e(grading_fmt_diem($r['diemThi'])) ?></strong></td><td><?= ui_status_badge($r['trangThaiCham']) ?></td><td><?= $r['maKetQuaChamThi'] === null ? '<span class="muted">—</span>' : ui_status_badge($r['trangThaiCongBo']) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </section>
<?php endif; ?>

<section class="panel table-panel">
  <div class="table-toolbar"><div class="table-title"><h2>Tình trạng công bố theo môn</h2><p>Bấm “Chọn” để tra cứu nhanh một môn.</p></div></div>
  <div class="table-scroll"><table class="data-table">
    <thead><tr><th>Kỳ thi</th><th>Môn thi</th><th>Đã chấm</th><th>Đã công bố</th><th>Trạng thái công bố</th><th></th></tr></thead>
    <tbody>
    <?php if (!$tongQuan): ?><tr><td colspan="6" class="muted center">Chưa có bài thi nào.</td></tr><?php endif; ?>
    <?php foreach ($tongQuan as $t): $chon = $t['maKyThi'] === $maKyThi && $t['maMonThi'] === $maMonThi && $daChon; ?>
      <tr<?= $chon ? ' class="ui-row-on"' : '' ?>><td><?= e($t['tenKyThi']) ?></td><td><strong><?= e($t['tenMonThi']) ?></strong></td>
        <td><?= $t['daCham'] ?>/<?= $t['tong'] ?><?= $t['chuaCham'] ? ' <span class="status status-pending">còn ' . $t['chuaCham'] . ' chưa chấm</span>' : '' ?></td>
        <td><?= $t['daCongBo'] ?>/<?= $t['daCongBo'] + $t['chuaCongBo'] ?></td>
        <td><?= ui_status_badge($t['trangThai']) ?></td>
        <td><a class="btn btn-outline btn-sm" href="?<?= e(http_build_query(['kyThi' => $t['maKyThi'], 'monThi' => $t['maMonThi'], 'xem' => 1])) ?>">Chọn</a></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<?php ui_page_end();
