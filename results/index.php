<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/functions.php';
$lookup_view = ($_GET['view'] ?? '') === 'diem-thi' ? 'diem-thi' : 'tuyen-sinh';
$page_title = $lookup_view === 'diem-thi' ? 'Thông tin điểm thi' : 'Kết quả tuyển sinh';
$public_active = $lookup_view === 'diem-thi' ? 'scores' : 'results';
$layout = 'public';
require dirname(__DIR__) . '/includes/header.php';
?>
<main id="main" class="result-main">
 <div class="container result-wrap">
  <div class="result-icon">⌕</div><div class="eyebrow center"><span></span> THÔNG TIN KẾT QUẢ TUYỂN SINH</div>
  <h1><?= e($page_title) ?> <span>vào lớp 10</span></h1>
  <section class="result-card">
   <div class="result-card-title"><div><h2>Tra cứu trực tuyến chưa mở</h2><p>Vui lòng theo dõi thông báo về thời gian và hướng dẫn tra cứu.</p></div></div>
   <p>Liên hệ cán bộ tuyển sinh tại trường nếu cần hỗ trợ thông tin hồ sơ. Việc nhập và công bố điểm được thực hiện trong khu vực quản lý theo quyền tài khoản.</p>
   <a class="btn btn-primary" href="<?= e(url('auth/login.php')) ?>">Đăng nhập hệ thống →</a>
   <a class="text-link" href="<?= e(url('admission-info/huong-dan.php')) ?>">Hướng dẫn sử dụng</a>
  </section>
  <div class="result-tips"><div><span>♙</span><div><strong>Bảo vệ tài khoản</strong><p>Không chia sẻ mật khẩu hoặc thông tin đăng nhập cho người khác.</p></div></div></div>
 </div>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
