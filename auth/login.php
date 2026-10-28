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
 <div class="login-illustration"><div class="login-illustration-content"><span class="login-toplabel">✦ HÀNH TRÌNH GIÁO DỤC</span><h1>Kiến tạo <em>tương lai</em> cùng bạn.</h1><p>Một nơi kết nối thông tin tuyển sinh, hồ sơ và hành trình hướng tới lớp 10.</p><div class="login-steps"><div><span>✓</span> Thông tin tuyển sinh</div><div><span>✓</span> Đăng ký nguyện vọng</div><div><span>✓</span> Tra cứu kết quả</div></div></div><div class="login-ill-bottom">DREAM TEAM <span>2026 – 2027</span></div></div>
 <div class="login-workspace"><div class="login-box">
  <div class="eyebrow"><span></span> CHÀO MỪNG BẠN</div><h2>Đăng nhập hệ thống</h2><p>Chọn loại tài khoản để đăng nhập.</p>
  <?php foreach ($thongBao as $tb): ?><div class="ui-notice ui-notice-<?= e($tb['type']) ?>" role="status"><span class="ui-notice-icon" aria-hidden="true">ⓘ</span><div><strong><?= e($tb['message']) ?></strong></div></div><?php endforeach; ?>
  <?php if ($loi): ?><div class="ui-notice ui-notice-error" role="alert"><span class="ui-notice-icon" aria-hidden="true">!</span><div><strong><?= e($loi) ?></strong></div></div><?php endif; ?>
  <div class="login-tabs" role="tablist" aria-label="Loại tài khoản">
    <button class="login-tab<?= $tab === 'student' ? ' active' : '' ?>" role="tab" id="student-tab" aria-controls="student-panel" aria-selected="<?= $tab === 'student' ? 'true' : 'false' ?>" data-login-tab="student">Học sinh</button>
    <button class="login-tab<?= $tab === 'admin' ? ' active' : '' ?>" role="tab" id="admin-tab" aria-controls="admin-panel" aria-selected="<?= $tab === 'admin' ? 'true' : 'false' ?>" data-login-tab="admin">Quản trị / Cán bộ</button>
  </div>
  <section id="student-panel" class="login-panel" role="tabpanel" aria-labelledby="student-tab"<?= $tab === 'student' ? '' : ' hidden' ?>>
    <?php form_dang_nhap('student', 'Số báo danh', 'Ví dụ: SBD0001', 'Đăng nhập học sinh', $tab === 'student' ? $tenDangNhap : '', $next); ?>
  </section>
  <section id="admin-panel" class="login-panel" role="tabpanel" aria-labelledby="admin-tab"<?= $tab === 'admin' ? '' : ' hidden' ?>>
    <?php form_dang_nhap('admin', 'Tên đăng nhập', 'Ví dụ: hdchamthi', 'Đăng nhập cán bộ', $tab === 'admin' ? $tenDangNhap : '', $next); ?>
  </section>
  <details class="login-demo-notice login-demo-accounts">
    <summary><span>ⓘ</span> Tài khoản demo — tất cả dùng mật khẩu <code>dreamteam2026</code></summary>
    <table>
      <tr><td><code>quantri</code></td><td>Quản trị viên hệ thống</td></tr>
      <tr><td><code>bantuyensinh</code></td><td>Ban Tuyển sinh Sở (UC10, UC15)</td></tr>
      <tr><td><code>hdtuyensinh</code></td><td>Hội đồng tuyển sinh</td></tr>
      <tr><td><code>hdcoithi</code></td><td>Hội đồng coi thi (UC11)</td></tr>
      <tr><td><code>hdchamthi</code></td><td>Hội đồng chấm thi (UC12–UC14)</td></tr>
      <tr><td><code>catphach</code></td><td>Bộ phận cắt phách (UC9)</td></tr>
      <tr><td><code>hdphuckhao</code></td><td>Hội đồng phúc khảo</td></tr>
      <tr><td><code>hdrade</code></td><td>Hội đồng ra đề thi</td></tr>
      <tr><td><code>gvcn</code></td><td>Giáo viên chủ nhiệm lớp 9</td></tr>
      <tr><td><code>SBD0001</code>, <code>SBD0002</code></td><td>Học sinh</td></tr>
    </table>
  </details>
 </div></div>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
