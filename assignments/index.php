<?php
declare(strict_types=1);
/**
 * UC11 – Phân công coi thi (Actor: Hội đồng coi thi).
 * Luồng: chọn điểm thi → phòng thi → môn thi → chọn cán bộ + vai trò → kiểm tra → xác nhận → lưu.
 */
require_once dirname(__DIR__) . '/includes/ui.php';
require_once dirname(__DIR__) . '/services/assignment_service.php';
$nguoiDung = require_role(['HD_COI_THI']);   // UC11 – Hội đồng coi thi

$maDiemThi = ui_input('diemThi');
$maPhong = ui_input('phong');
$maMonThi = ui_input('monThi');
$action = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['action'] ?? '') : '';
$sel = ['diemThi' => $maDiemThi, 'phong' => $maPhong, 'monThi' => $maMonThi];

// Cán bộ được tick và vai trò tương ứng: [maCanBo => vaiTro]
$chon = [];
$vaiTroForm = is_array($_POST['vaiTro'] ?? null) ? $_POST['vaiTro'] : [];
foreach ((is_array($_POST['chon'] ?? null) ? $_POST['chon'] : []) as $maCb) {
    if (is_string($maCb)) $chon[$maCb] = is_string($vaiTroForm[$maCb] ?? null) ? $vaiTroForm[$maCb] : '';
}

$loi = null; $chiTietLoi = []; $loiHeThong = ui_csrf_error(); $xacNhan = null;
if ($loiHeThong) $action = '';

try {
    if ($action === 'kiem_tra') {
        $xacNhan = kiemTraTruocXacNhan($maDiemThi, $maPhong, $maMonThi, $chon);
    } elseif ($action === 'luu') {
        $daThem = luuThongTinPhanCong($maDiemThi, $maPhong, $maMonThi, $chon);
        flash_set('success', 'Phân công coi thi thành công: ' . implode(', ', array_map(fn($c) => "{$c['hoTen']} – {$c['vaiTro']}", $daThem)) . '.');
        ui_redirect('assignments/', $sel);
    } elseif ($action === 'huy') {
        $pc = huyPhanCongCoiThi(ui_input('maPhanCong'));
        flash_set('success', "Đã hủy phân công {$pc['hoTen']} – {$pc['vaiTro']} tại phòng {$pc['maPhong']}.");
        if (!$pc['tinhTrang']['du']) flash_set('warning', "Phòng {$pc['maPhong']} hiện chưa đủ 2 giám thị cho môn này, cần phân công bổ sung.");
        ui_redirect('assignments/', $sel);
    }
} catch (BusinessException $e) {
    $loi = $e->getMessage(); $chiTietLoi = $e->details();
}

$dsDiemThi = $dsPhong = $dsMon = $hienCo = $canBo = $tongQuan = [];
$phong = null;
try {
    $dsDiemThi = layDanhSachDiemThi();
    $dsMon = pc_ds_mon();
    if ($maDiemThi !== '') {
        $dsPhong = layDanhSachPhongThi($maDiemThi);
        $tongQuan = tongQuanPhanCong($maDiemThi);
        if ($maPhong !== '') $phong = pc_phong(db(), $maDiemThi, $maPhong);
    }
    if ($phong && $maMonThi !== '') {
        $hienCo = layPhanCongPhong($maPhong, $maMonThi);
        $canBo = layDanhSachCanBoGiamThi($phong['maKyThi'], $maPhong, $maMonThi);
    }
} catch (Throwable $e) {
    error_log('UC11: ' . $e->getMessage());
    $loiHeThong = 'Không thể truy xuất dữ liệu phân công coi thi. Vui lòng kiểm tra MySQL và thử lại.';
}
$tenMon = '';
foreach ($dsMon as $m) if ($m['maMonThi'] === $maMonThi) $tenMon = $m['tenMonThi'];
$tinhTrang = pc_tinh_trang($hienCo);
$daChonDu = $phong && $tenMon !== '';
$dsVaiTro = array_map(fn($v) => ['v' => $v], PC_VAI_TRO);

ui_page_start('assignments', 'Phân công coi thi', 'Tổ chức thi · Coi thi', 'Chọn điểm thi, phòng thi, môn thi rồi chọn ít nhất 2 cán bộ/giám thị (Giám thị 1 và Giám thị 2) để phân công coi thi.', 'UC11');
ui_flashes();
if (isset($_GET['huyBo'])) ui_notice('info', 'Đã hủy thao tác. Thông tin phân công trước đó không thay đổi.');
if ($loiHeThong) ui_notice('error', $loiHeThong);
?>
<section class="panel">
  <div class="panel-heading"><div><h2>Bước 1 · Chọn điểm thi, phòng thi, môn thi</h2><p>Chọn điểm thi rồi bấm “Tiếp tục” để hiện danh sách phòng thi thuộc điểm thi đó.</p></div></div>
  <form method="get" class="ui-search" novalidate>
    <?php ui_select('diemThi', 'Điểm thi', $dsDiemThi, 'maDiemThi', 'tenDiemThi', $maDiemThi, '-- Chọn điểm thi --'); ?>
    <?php if ($maDiemThi !== ''): ?>
      <?php ui_select('phong', 'Phòng thi', $dsPhong, 'maPhong', 'tenHienThi', $maPhong, '-- Chọn phòng thi --'); ?>
      <?php ui_select('monThi', 'Môn thi', $dsMon, 'maMonThi', 'tenMonThi', $maMonThi, '-- Chọn môn thi --'); ?>
    <?php else: ?>
      <div class="field"><label>Phòng thi</label><select disabled><option>Chọn điểm thi trước</option></select></div>
      <div class="field"><label>Môn thi</label><select disabled><option>Chọn điểm thi trước</option></select></div>
    <?php endif; ?>
    <div class="field ui-search-btn"><button class="btn btn-primary" type="submit">Tiếp tục →</button></div>
  </form>
  <?php if ($maDiemThi !== '' && $maPhong !== '' && !$phong && !$loiHeThong) ui_notice('warning', 'Phòng thi đã chọn không thuộc điểm thi đã chọn. Vui lòng chọn lại phòng thi.'); ?>
</section>

<?php if ($daChonDu): ?>
<div class="ui-stack">
<section class="panel">
  <div class="panel-heading"><div><h2>Phân công hiện có</h2><p><?= e($phong['tenPhong']) ?> · môn <?= e($tenMon) ?></p></div><?= ui_status_badge($tinhTrang['du'] ? 'Đủ giám thị' : 'Chưa đủ 2 giám thị') ?></div>
  <?php if (!$hienCo): ?><p class="muted">Chưa có cán bộ nào được phân công.</p><?php else: ?>
  <ul class="ui-list">
    <?php foreach ($hienCo as $p): ?>
      <li><span><strong><?= e($p['vaiTro']) ?>:</strong> <?= e($p['hoTen']) ?> <span class="muted">(<?= e($p['maCanBo']) ?>)</span></span>
        <form method="post" class="ui-inline" onsubmit="return confirm('Hủy phân công <?= e($p['hoTen']) ?> – <?= e($p['vaiTro']) ?>?');">
          <?= csrf_field() ?><input type="hidden" name="maPhanCong" value="<?= e($p['maPhanCong']) ?>">
          <?php foreach ($sel as $k => $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach; ?>
          <button class="btn btn-outline btn-sm ui-btn-cancel" type="submit" name="action" value="huy">Hủy</button>
        </form></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</section>

<section class="panel">
<?php if ($xacNhan): ?>
  <div class="panel-heading"><div><h2>Bước 3 · Xác nhận phân công</h2><p>Kiểm tra lại trước khi lưu.</p></div></div>
  <p class="ui-confirm">Phân công coi thi <strong><?= e($xacNhan['phong']['tenPhong']) ?></strong> – môn <strong><?= e($xacNhan['mon']['tenMonThi']) ?></strong>:</p>
  <ul class="ui-list">
    <?php foreach ($xacNhan['canBo'] as $c): ?><li><span><strong><?= e($c['vaiTro']) ?>:</strong> <?= e($c['hoTen']) ?> <span class="muted">(<?= e($c['maCanBo']) ?>)</span></span></li><?php endforeach; ?>
  </ul>
  <form method="post" class="ui-actions">
    <?= csrf_field() ?>
    <?php foreach ($sel as $k => $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach; ?>
    <?php foreach ($chon as $maCb => $vt): ?><input type="hidden" name="chon[]" value="<?= e($maCb) ?>"><input type="hidden" name="vaiTro[<?= e($maCb) ?>]" value="<?= e($vt) ?>"><?php endforeach; ?>
    <button class="btn btn-primary" type="submit" name="action" value="luu">✓ Xác nhận phân công</button>
    <a class="btn btn-outline ui-btn-cancel" href="?<?= e(http_build_query($sel + ['huyBo' => 1])) ?>">Hủy bỏ</a>
  </form>
<?php else: ?>
  <div class="panel-heading"><div><h2>Bước 2 · Chọn cán bộ/giám thị</h2><p>Tick cán bộ và chọn vai trò. Phòng cần đủ Giám thị 1 và Giám thị 2.</p></div></div>
  <?php if ($loi) ui_notice('error', $loi, $chiTietLoi); ?>
  <?php if (!$canBo): ?><p class="muted">Không có cán bộ nào thuộc Hội đồng coi thi của kỳ thi này.</p><?php else: ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <?php foreach ($sel as $k => $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach; ?>
    <div class="table-scroll"><table class="data-table ui-pick">
      <thead><tr><th>Chọn</th><th>Cán bộ</th><th>Vai trò</th><th>Tình trạng môn <?= e($tenMon) ?></th></tr></thead>
      <tbody>
      <?php foreach ($canBo as $c): $ma = $c['maCanBo']; $tick = array_key_exists($ma, $chon); ?>
        <tr<?= $c['coThe'] ? '' : ' class="ui-row-off"' ?>>
          <td><input type="checkbox" name="chon[]" value="<?= e($ma) ?>" id="cb_<?= e($ma) ?>"<?= $tick ? ' checked' : '' ?> aria-label="Chọn <?= e($c['hoTen']) ?>"></td>
          <td><label for="cb_<?= e($ma) ?>"><strong><?= e($c['hoTen']) ?></strong> <span class="muted">(<?= e($ma) ?>)</span></label></td>
          <td><select name="vaiTro[<?= e($ma) ?>]" aria-label="Vai trò của <?= e($c['hoTen']) ?>"><option value="">-- Vai trò --</option>
            <?php foreach (PC_VAI_TRO as $vt): ?><option<?= ($chon[$ma] ?? '') === $vt ? ' selected' : '' ?>><?= e($vt) ?></option><?php endforeach; ?></select></td>
          <td><?php if ($c['daOPhongNay']): ?><span class="status status-success">Đã ở phòng này</span>
              <?php elseif ($c['phongKhac'] !== null): ?><span class="status status-pending">Đang coi phòng <?= e($c['phongKhac']) ?></span>
              <?php else: ?><span class="status status-success">Có thể phân công</span><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <div class="ui-actions ui-mt">
      <button class="btn btn-primary" type="submit" name="action" value="kiem_tra">Tiếp tục → Xác nhận</button>
      <a class="btn btn-outline ui-btn-cancel" href="?<?= e(http_build_query($sel + ['huyBo' => 1])) ?>">Hủy bỏ</a>
    </div>
  </form>
  <?php endif; ?>
<?php endif; ?>
</section>
</div>
<?php elseif ($loi): ?>
  <?php ui_notice('error', $loi, $chiTietLoi); ?>
<?php endif; ?>

<?php if ($tongQuan): ?>
<section class="panel table-panel">
  <div class="table-toolbar"><div class="table-title"><h2>Tình trạng phân công tại điểm thi</h2><p>Mỗi phòng, mỗi môn cần đủ Giám thị 1 và Giám thị 2.</p></div></div>
  <div class="table-scroll"><table class="data-table">
    <thead><tr><th>Phòng</th><th>Môn thi</th><th>Cán bộ coi thi</th><th>Tình trạng</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($tongQuan as $t): $on = $t['maPhong'] === $maPhong && $t['maMonThi'] === $maMonThi; ?>
      <tr<?= $on ? ' class="ui-row-on"' : '' ?>><td><span class="code-tag"><?= e($t['maPhong']) ?></span></td><td><?= e($t['tenMonThi']) ?></td><td><?= e($t['danhSach'] ?? '—') ?></td>
        <td><?= ui_status_badge($t['du'] ? 'Đủ giám thị' : 'Chưa đủ 2 giám thị') ?></td>
        <td><a class="btn btn-outline btn-sm" href="?<?= e(http_build_query(['diemThi' => $maDiemThi, 'phong' => $t['maPhong'], 'monThi' => $t['maMonThi']])) ?>">Chọn</a></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<?php endif; ?>
<?php ui_page_end();
