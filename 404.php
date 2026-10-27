<?php http_response_code(404); $page_title='Không tìm thấy'; $layout='public'; require __DIR__.'/includes/header.php'; ?>
<main id="main" class="error-404 container"><div class="error-digits">404</div><h1>Không tìm thấy trang</h1><p>Đường dẫn không tồn tại hoặc trang chưa được tạo trong bộ giao diện mẫu.</p><a class="btn btn-primary" href="<?= e(url()) ?>">← Về trang chủ</a></main>
<?php require __DIR__.'/includes/footer.php'; ?>
