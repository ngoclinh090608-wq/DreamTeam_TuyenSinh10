<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/functions.php';
$page_title = 'Thông tin tuyển sinh'; $layout = 'public'; $public_active = 'info';
require dirname(__DIR__) . '/includes/header.php';
?>
<main id="main" class="portal-main"><div class="container">
 <nav class="public-breadcrumb" aria-label="Đường dẫn"><a href="<?= e(url()) ?>">Trang chủ</a><span aria-hidden="true">/</span><span aria-current="page">Thông tin tuyển sinh</span></nav>
 <header class="public-page-heading"><span class="section-overline">NĂM HỌC 2026–2027</span><h1>Thông tin tuyển sinh vào lớp 10</h1><p>Quy trình tham khảo và hướng dẫn sử dụng cổng thông tin.</p></header>
 <div class="learning-notice"><span aria-hidden="true">ⓘ</span><p>Đây là sản phẩm học tập. Lịch tuyển sinh và quy định thực tế cần đối chiếu với thông báo chính thức tại địa phương.</p></div>
 <?php require dirname(__DIR__) . '/includes/portal_guide.php'; ?>
 <div class="help-line"><p>Tham khảo trường và chỉ tiêu trong hệ thống</p><a class="text-link" href="<?= e(url('schools/danh-sach.php')) ?>">Xem danh sách trường →</a></div>
</div></main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
