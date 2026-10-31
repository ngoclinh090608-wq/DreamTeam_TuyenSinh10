<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/functions.php';
$page_title = 'Danh sách trường THPT'; $layout = 'public'; $public_active = 'schools';
require dirname(__DIR__) . '/includes/header.php';
?>
<main id="main" class="portal-main"><div class="container">
 <nav class="public-breadcrumb" aria-label="Đường dẫn"><a href="<?= e(url()) ?>">Trang chủ</a><span aria-hidden="true">/</span><span aria-current="page">Danh sách trường</span></nav>
 <header class="public-page-heading"><span class="section-overline">TRƯỜNG HỌC & CHỈ TIÊU</span><h1>Danh sách trường THPT</h1><p>Tìm thông tin theo tên trường, mã trường hoặc khu vực.</p></header>
 <div class="learning-notice"><span aria-hidden="true">ⓘ</span><p>Danh sách phục vụ sản phẩm học tập, không phải thông tin tuyển sinh chính thức.</p></div>
 <section class="portal-section" aria-label="Tra cứu trường THPT"><?php require dirname(__DIR__) . '/includes/portal_schools.php'; ?></section>
 <div class="help-line"><p>Bạn cần tìm hiểu quy trình tuyển sinh?</p><a class="text-link" href="<?= e(url('admission-info/huong-dan.php')) ?>">Xem hướng dẫn →</a></div>
</div></main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
