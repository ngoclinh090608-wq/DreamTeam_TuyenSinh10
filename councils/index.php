<?php
declare(strict_types=1);
/**
 * UC10 – Quản lý Hội đồng thi (Actor: Ban Tuyển sinh Sở GD&ĐT).
 * Màn hình: danh sách → xem chi tiết → cập nhật (theo basic flow); thêm mới và xóa dùng cùng khuôn.
 */
require_once dirname(__DIR__) . '/includes/ui.php';
require_once dirname(__DIR__) . '/services/council_service.php';
$nguoiDung = require_role(['BAN_TUYEN_SINH']);   // UC10 – Ban Tuyển sinh Sở GD&ĐT

$ma = ui_input('ma');
$cheDo = isset($_GET['them']) ? 'them' : ($ma === '' ? 'ds' : (isset($_GET['sua']) ? 'sua' : (isset($_GET['xoa']) ? 'xoa' : 'xem')));
$action = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['action'] ?? '') : '';
$form = [
    'tenHoiDongThi' => ui_input('tenHoiDongThi'), 'loaiHoiDong' => ui_input('loaiHoiDong'),
    'maKyThi' => ui_input('maKyThi'), 'thongTinHoiDong' => ui_input('thongTinHoiDong'),
];
$loi = null; $chiTietLoi = []; $loiHeThong = ui_csrf_error();

if ($action !== '' && !$loiHeThong) {
    try {
        if ($action === 'cap_nhat') {
            luuThongTinHoiDongThi($ma, $form);
            flash_set('success', HD_CAP_NHAT_THANH_CONG);
            ui_redirect('councils/', ['ma' => $ma]);
        } elseif ($action === 'them') {
            $maMoi = themHoiDongThi($form);
            flash_set('success', "Thêm Hội đồng thi {$maMoi} thành công.");
            ui_redirect('councils/', ['ma' => $maMoi]);
        } elseif ($action === 'xoa') {
            xoaHoiDongThi($ma);
            flash_set('success', "Xóa Hội đồng thi {$ma} thành công.");
            ui_redirect('councils/');
        }
    } catch (BusinessException $e) {
        $loi = $e->getMessage(); $chiTietLoi = $e->details();
    }
}

$ds = []; $hd = null; $dsKyThi = [];
try {
    $dsKyThi = hd_ds_ky_thi();
    if ($cheDo === 'ds') $ds = layDanhSachHoiDongThi();
    elseif ($cheDo !== 'them') $hd = layThongTinHoiDongThi($ma);
} catch (BusinessException $e) {
    $loi = $loi ?? $e->getMessage(); $cheDo = 'ds'; $ds = layDanhSachHoiDongThi();
} catch (Throwable $e) {
    error_log('UC10: ' . $e->getMessage());
    $loiHeThong = 'Không thể truy xuất thông tin Hội đồng thi. Vui lòng kiểm tra MySQL và thử lại.';
}
// Form sửa: lần đầu lấy dữ liệu hiện có; sau khi gửi lỗi thì giữ dữ liệu người dùng vừa nhập.
if ($hd && $cheDo === 'sua' && $action === '') {
    $form = ['tenHoiDongThi' => $hd['tenHoiDongThi'], 'loaiHoiDong' => $hd['loaiHoiDong'], 'maKyThi' => $hd['maKyThi'], 'thongTinHoiDong' => (string)$hd['thongTinHoiDong']];
}
$dsLoai = array_map(fn($l) => ['v' => $l], HD_LOAI);

/** Form thêm / sửa dùng chung. */
function hd_form(array $form, array $dsKyThi, array $dsLoai, string $action, string $nutLuu, string $linkHuy): void { ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="ui-form-grid">
      <div class="field"><label for="tenHoiDongThi">Tên Hội đồng thi <span>*</span></label><input id="tenHoiDongThi" name="tenHoiDongThi" value="<?= e($form['tenHoiDongThi']) ?>" maxlength="100" placeholder="Ví dụ: Hội đồng coi thi"></div>
      <?php ui_select('loaiHoiDong', 'Loại hội đồng', $dsLoai, 'v', 'v', $form['loaiHoiDong'], '-- Chọn loại hội đồng --'); ?>
      <?php ui_select('maKyThi', 'Kỳ thi', $dsKyThi, 'maKyThi', 'tenKyThi', $form['maKyThi'], '-- Chọn kỳ thi --'); ?>
    </div>
    <div class="field"><label for="thongTinHoiDong">Thông tin hội đồng</label><textarea id="thongTinHoiDong" name="thongTinHoiDong" rows="3" maxlength="255" placeholder="Nhiệm vụ, thành phần, ghi chú…"><?= e($form['thongTinHoiDong']) ?></textarea></div>
    <div class="ui-actions">
      <button class="btn btn-primary" type="submit" name="action" value="<?= e($action) ?>">✓ <?= e($nutLuu) ?></button>
      <a class="btn btn-outline ui-btn-cancel" href="<?= e($linkHuy) ?>">Hủy bỏ</a>
    </div>
  </form>
<?php }

ui_page_start('councils', 'Quản lý Hội đồng thi', 'Hội đồng thi · Thành phần', 'Xem danh sách, xem chi tiết và cập nhật thông tin các Hội đồng thi theo kỳ thi.', 'UC10');
ui_flashes();
if (isset($_GET['huy'])) ui_notice('info', 'Đã hủy thao tác. Thông tin Hội đồng thi được giữ nguyên.');
if ($loiHeThong) ui_notice('error', $loiHeThong);
if ($loi && $cheDo === 'ds') ui_notice('error', $loi, $chiTietLoi);
?>

<?php if ($cheDo === 'ds'): ?>
<section class="panel table-panel">
  <div class="table-toolbar">
    <div class="table-title"><h2>Danh sách Hội đồng thi</h2><p>Bấm “Xem” để xem hoặc cập nhật một hội đồng.</p></div>
    <div class="toolbar-right"><a class="btn btn-primary" href="?them=1">+ Thêm Hội đồng thi</a></div>
  </div>
  <div class="table-scroll"><table class="data-table">
    <thead><tr><th>Mã</th><th>Tên Hội đồng thi</th><th>Loại</th><th>Kỳ thi</th><th>Số cán bộ</th><th></th></tr></thead>
    <tbody>
    <?php if (!$ds): ?><tr><td colspan="6" class="muted center">Chưa có Hội đồng thi nào.</td></tr><?php endif; ?>
    <?php foreach ($ds as $r): ?>
      <tr><td><span class="code-tag"><?= e($r['maHoiDongThi']) ?></span></td><td><strong><?= e($r['tenHoiDongThi']) ?></strong><?php if ($r['thongTinHoiDong']): ?><br><span class="muted"><?= e($r['thongTinHoiDong']) ?></span><?php endif; ?></td><td><?= e($r['loaiHoiDong']) ?></td><td><?= e($r['tenKyThi']) ?></td><td><?= (int)$r['soCanBo'] ?></td>
        <td><a class="btn btn-outline btn-sm" href="?ma=<?= e(rawurlencode($r['maHoiDongThi'])) ?>">Xem</a></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>

<?php elseif ($cheDo === 'them'): ?>
<section class="panel">
  <div class="panel-heading"><div><h2>Thêm Hội đồng thi</h2><p>Mã hội đồng được hệ thống tự sinh.</p></div></div>
  <?php if ($loi) ui_notice('error', $loi, $chiTietLoi); ?>
  <?php hd_form($form, $dsKyThi, $dsLoai, 'them', 'Xác nhận thêm', '?huy=1'); ?>
</section>

<?php elseif ($hd): ?>
<p class="ui-back"><a href="<?= e(url('councils/')) ?>">← Danh sách Hội đồng thi</a></p>
<div class="dash-panels ui-two">
<section class="panel">
  <div class="panel-heading"><div><h2><?= e($hd['tenHoiDongThi']) ?></h2><p>Mã <span class="code-tag"><?= e($hd['maHoiDongThi']) ?></span></p></div></div>
  <dl class="ui-kv ui-kv-1">
    <div><dt>Loại hội đồng</dt><dd><?= e($hd['loaiHoiDong']) ?></dd></div>
    <div><dt>Kỳ thi</dt><dd><?= e($hd['tenKyThi']) ?></dd></div>
    <div class="ui-kv-wide"><dt>Thông tin hội đồng</dt><dd><?= e($hd['thongTinHoiDong'] ?: '—') ?></dd></div>
  </dl>
  <?php if ($cheDo === 'xem'): ?>
  <div class="ui-actions">
    <a class="btn btn-primary" href="?ma=<?= e(rawurlencode($hd['maHoiDongThi'])) ?>&sua=1">✎ Cập nhật thông tin</a>
    <a class="btn btn-outline ui-btn-cancel" href="?ma=<?= e(rawurlencode($hd['maHoiDongThi'])) ?>&xoa=1">Xóa hội đồng</a>
  </div>
  <?php endif; ?>
</section>

<section class="panel">
<?php if ($cheDo === 'sua'): ?>
  <div class="panel-heading"><div><h2>Cập nhật thông tin</h2><p>Điều chỉnh rồi bấm Xác nhận cập nhật.</p></div></div>
  <?php if ($loi) ui_notice('error', $loi, $chiTietLoi); ?>
  <?php hd_form($form, $dsKyThi, $dsLoai, 'cap_nhat', 'Xác nhận cập nhật', '?ma=' . rawurlencode($hd['maHoiDongThi']) . '&huy=1'); ?>
<?php elseif ($cheDo === 'xoa'): ?>
  <div class="panel-heading"><div><h2>Xác nhận xóa</h2><p>Thao tác này không thể hoàn tác.</p></div></div>
  <?php if ($loi) ui_notice('error', $loi, $chiTietLoi); ?>
  <p class="ui-confirm">Bạn có chắc chắn muốn xóa <strong><?= e($hd['tenHoiDongThi']) ?></strong> (<?= e($hd['maHoiDongThi']) ?>)?</p>
  <form method="post" class="ui-actions">
    <?= csrf_field() ?>
    <button class="btn btn-primary ui-btn-danger" type="submit" name="action" value="xoa">Xác nhận xóa</button>
    <a class="btn btn-outline" href="?ma=<?= e(rawurlencode($hd['maHoiDongThi'])) ?>&huy=1">Hủy bỏ</a>
  </form>
<?php else: ?>
  <div class="panel-heading"><div><h2>Cán bộ thuộc hội đồng</h2><p>Bảng CanBo</p></div><span class="count-tag"><?= count($hd['canBo']) ?> cán bộ</span></div>
  <?php if (!$hd['canBo']): ?><p class="muted">Chưa có cán bộ nào thuộc hội đồng này.</p><?php else: ?>
  <ul class="ui-list">
    <?php foreach ($hd['canBo'] as $c): ?><li><span class="code-tag"><?= e($c['maCanBo']) ?></span> <strong><?= e($c['hoTen']) ?></strong> <span class="muted">· <?= e($c['chucVu'] ?? '') ?></span></li><?php endforeach; ?>
  </ul>
  <?php endif; ?>
<?php endif; ?>
</section>
</div>
<?php endif; ?>
<?php ui_page_end();
