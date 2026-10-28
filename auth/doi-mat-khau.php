<?php
declare(strict_types=1);
/** Đổi mật khẩu (chức năng chung của mọi actor). */
require_once dirname(__DIR__) . '/includes/ui.php';

$user = require_auth();
$loi = null; $chiTiet = []; $loiHeThong = ui_csrf_error();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$loiHeThong) {
    try {
        doiMatKhau($user['maTaiKhoan'], (string)($_POST['matKhauCu'] ?? ''), (string)($_POST['matKhauMoi'] ?? ''), (string)($_POST['nhapLai'] ?? ''));
        flash_set('success', TB_DMK_THANH_CONG);
        ui_redirect('auth/doi-mat-khau.php');
    } catch (BusinessException $e) {
        $loi = $e->getMessage(); $chiTiet = $e->details();
    } catch (Throwable $e) {
        error_log('doi mat khau: ' . $e->getMessage());
        $loiHeThong = 'Không thể đổi mật khẩu lúc này. Vui lòng thử lại sau.';
    }
}

ui_page_start('', 'Đổi mật khẩu', 'Tài khoản', 'Tài khoản ' . $user['tenDangNhap'] . ' · ' . ten_vai_tro($user['vaiTro']), 'Tài khoản');
ui_flashes();
if ($loiHeThong) ui_notice('error', $loiHeThong);
?>
<section class="panel ui-narrow">
  <?php if ($loi) ui_notice('error', $loi, $chiTiet); ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="field"><label for="matKhauCu">Mật khẩu hiện tại <span>*</span></label><input id="matKhauCu" name="matKhauCu" type="password" autocomplete="current-password"></div>
    <div class="field"><label for="matKhauMoi">Mật khẩu mới <span>*</span></label><input id="matKhauMoi" name="matKhauMoi" type="password" autocomplete="new-password" minlength="<?= DN_MAT_KHAU_TOI_THIEU ?>"></div>
    <div class="field"><label for="nhapLai">Nhập lại mật khẩu mới <span>*</span></label><input id="nhapLai" name="nhapLai" type="password" autocomplete="new-password"></div>
    <p class="field-note">Ít nhất <?= DN_MAT_KHAU_TOI_THIEU ?> ký tự và khác mật khẩu hiện tại.</p>
    <div class="ui-actions"><button class="btn btn-primary" type="submit">✓ Đổi mật khẩu</button><a class="btn btn-outline" href="<?= e(url('admin/')) ?>">Hủy bỏ</a></div>
  </form>
</section>
<?php ui_page_end();
