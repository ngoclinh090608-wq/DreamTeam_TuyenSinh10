<?php
declare(strict_types=1);
/** Đăng nhập hệ thống (mọi actor). Hai tab chỉ khác nhãn: học sinh dùng số báo danh, cán bộ dùng tên đăng nhập. */
require_once dirname(__DIR__) . '/includes/auth.php';

$next = (string)($_POST['next'] ?? $_GET['next'] ?? '');
if (current_user() !== null) { header('Location: ' . duong_dan_quay_lai($next), true, 302); exit; }

$loi = null;
$tab = ($_POST['tab'] ?? $_GET['tab'] ?? 'admin') === 'student' ? 'student' : 'admin';
$tenDangNhap = trim((string)($_POST['tenDangNhap'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $loi = 'Phiên làm việc đã hết hạn hoặc yêu cầu không hợp lệ. Vui lòng thử lại.';
    } else {
        try {
            dang_nhap_phien(dangNhap($tenDangNhap, (string)($_POST['matKhau'] ?? '')));
            header('Location: ' . duong_dan_quay_lai($next), true, 303);
            exit;
        } catch (BusinessException $e) {
            $loi = $e->getMessage();
        } catch (Throwable $e) {
            error_log('login: ' . $e->getMessage());
            $loi = TB_DN_LOI;
        }
    }
}
$thongBao = flash_take();

$page_title = 'Đăng nhập'; $layout = 'public';
require dirname(__DIR__) . '/includes/header.php';

/** Một form đăng nhập (dùng cho cả hai tab). */
function form_dang_nhap(string $tab, string $nhan, string $goiY, string $nut, string $giaTri, string $next): void { ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="tab" value="<?= e($tab) ?>">
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <div class="field"><label for="<?= e($tab) ?>-user"><?= e($nhan) ?> <span>*</span></label><div class="input-with-icon"><span>♙</span><input id="<?= e($tab) ?>-user" name="tenDangNhap" value="<?= e($giaTri) ?>" autocomplete="username" placeholder="<?= e($goiY) ?>" maxlength="50" required></div></div>
    <div class="field"><label for="<?= e($tab) ?>-password">Mật khẩu <span>*</span></label><div class="input-with-icon"><span>⚿</span><input id="<?= e($tab) ?>-password" name="matKhau" type="password" autocomplete="current-password" placeholder="Nhập mật khẩu" required><button class="peek-pass" type="button" aria-label="Hiện hoặc ẩn mật khẩu" data-toggle-password="<?= e($tab) ?>-password">◉</button></div></div>
    <button class="btn btn-primary w-full btn-lg" type="submit"><?= e($nut) ?> <span>→</span></button>
  </form>
<?php }
?>
<main id="main" class="login-main">
 <div class="login-illustration"><div class="login-illustration-content"><span class="login-toplabel">CỔNG TUYỂN SINH LỚP 10</span><h1>Đồng hành trên<br>hành trình vào lớp 10.</h1><p>Đăng nhập để truy cập các chức năng phù hợp với tài khoản của bạn.</p><ol class="login-steps"><li>Thông tin tuyển sinh và nguyện vọng</li><li>Tổ chức thi và quản lý kết quả</li><li>Theo dõi tiến trình tuyển sinh</li></ol><a class="login-help-link" href="<?= e(url('admission-info/huong-dan.php')) ?>">Xem hướng dẫn sử dụng →</a></div><div class="login-ill-bottom">CỔNG TUYỂN SINH LỚP 10 <span>2026 – 2027</span></div></div>
 <div class="login-workspace"><div class="login-box">
  <div class="eyebrow"><span></span> CHÀO MỪNG BẠN</div><h2>Đăng nhập hệ thống</h2><p>Chọn loại tài khoản để đăng nhập.</p>
  <?php foreach ($thongBao as $tb): ?><div class="ui-notice ui-notice-<?= e($tb['type']) ?>" role="status"><span class="ui-notice-icon" aria-hidden="true">ⓘ</span><div><strong><?= e($tb['message']) ?></strong></div></div><?php endforeach; ?>
  <?php if ($loi): ?><div class="ui-notice ui-notice-error" role="alert"><span class="ui-notice-icon" aria-hidden="true">!</span><div><strong><?= e($loi) ?></strong></div></div><?php endif; ?>
  <div class="login-tabs" role="tablist" aria-label="Loại tài khoản">
    <button class="login-tab<?= $tab === 'student' ? ' active' : '' ?>" role="tab" id="student-tab" aria-controls="student-panel" aria-selected="<?= $tab === 'student' ? 'true' : 'false' ?>" data-login-tab="student">Học sinh</button>
    <button class="login-tab<?= $tab === 'admin' ? ' active' : '' ?>" role="tab" id="admin-tab" aria-controls="admin-panel" aria-selected="<?= $tab === 'admin' ? 'true' : 'false' ?>" data-login-tab="admin">Quản trị / Cán bộ</button>
  </div>
  <section id="student-panel" class="login-panel" role="tabpanel" aria-labelledby="student-tab"<?= $tab === 'student' ? '' : ' hidden' ?>>
    <?php form_dang_nhap('student', 'Số báo danh', 'Nhập số báo danh', 'Đăng nhập học sinh', $tab === 'student' ? $tenDangNhap : '', $next); ?>
  </section>
  <section id="admin-panel" class="login-panel" role="tabpanel" aria-labelledby="admin-tab"<?= $tab === 'admin' ? '' : ' hidden' ?>>
    <?php form_dang_nhap('admin', 'Tên đăng nhập', 'Nhập tên đăng nhập', 'Đăng nhập cán bộ', $tab === 'admin' ? $tenDangNhap : '', $next); ?>
  </section>
  <p class="field-note">Sử dụng tài khoản được cấp. Nếu quên mật khẩu, vui lòng liên hệ cán bộ phụ trách hoặc quản trị hệ thống.</p>
 </div></div>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
