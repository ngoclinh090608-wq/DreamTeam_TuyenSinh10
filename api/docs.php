<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/functions.php';
$page_title = 'Tài liệu REST API';
$layout = 'public';
require dirname(__DIR__) . '/includes/header.php';

$getEndpoints = [
    ['GET', '/api/v1/schools', 'Danh sách trường THPT và chỉ tiêu', url('api/v1/schools'), []],
    ['GET', '/api/v1/exams', 'Danh sách kỳ thi', url('api/v1/exams'), []],
    ['GET', '/api/v1/exams/{examId}/sites', 'Điểm thi theo kỳ thi', url('api/v1/exams/{examId}/sites'), ['examId' => 'KT2026']],
    ['GET', '/api/v1/exam-sites/{siteId}/rooms', 'Phòng thi theo điểm thi', url('api/v1/exam-sites/{siteId}/rooms'), ['siteId' => 'DT02']],
];
?>
<main id="main" class="portal-main"><div class="container api-docs" data-api-explorer>
 <nav class="public-breadcrumb" aria-label="Đường dẫn"><a href="<?= e(url()) ?>">Trang chủ</a><span aria-hidden="true">/</span><span aria-current="page">REST API</span></nav>
 <header class="public-page-heading"><span class="section-overline">LẬP TRÌNH HƯỚNG DỊCH VỤ</span><h1>Tài liệu REST API</h1><p>API PHP dùng lại tầng nghiệp vụ của hệ thống và trao đổi dữ liệu JSON qua HTTP.</p></header>
 <div class="learning-notice"><span aria-hidden="true">ⓘ</span><p>Các nút thử bên dưới chỉ gọi endpoint GET và không thay đổi dữ liệu. <a class="text-link" href="<?= e(url('api/openapi.yaml')) ?>">Xem đặc tả OpenAPI 3.0 →</a></p></div>

 <section class="api-doc-section" aria-labelledby="api-read-title">
  <div class="section-heading"><div><span class="section-overline">DANH MỤC CÔNG KHAI</span><h2 id="api-read-title">Thử API đọc dữ liệu</h2></div></div>
  <div class="api-endpoint-list">
   <?php foreach ($getEndpoints as [$method, $path, $description, $endpoint, $params]): ?>
    <article class="panel api-endpoint" data-api-card data-api-endpoint="<?= e($endpoint) ?>">
     <header><span class="api-method api-method-get"><?= e($method) ?></span><code><?= e($path) ?></code></header>
     <p><?= e($description) ?></p>
     <?php foreach ($params as $name => $example): ?>
      <label class="field"><span><?= e($name) ?></span><input data-api-param="<?= e($name) ?>" value="<?= e($example) ?>" maxlength="80"></label>
     <?php endforeach; ?>
     <button class="btn btn-outline btn-sm" type="button" data-api-try>Gửi yêu cầu GET</button>
     <pre class="api-response" data-api-output aria-live="polite">Chưa gửi yêu cầu.</pre>
    </article>
   <?php endforeach; ?>
  </div>
 </section>

 <section class="api-doc-section" aria-labelledby="api-write-title">
  <div class="section-heading"><div><span class="section-overline">NGHIỆP VỤ CÓ BẢO VỆ</span><h2 id="api-write-title">API chuẩn bị phòng thi</h2></div></div>
  <article class="panel api-endpoint">
   <header><span class="api-method api-method-post">POST</span><code>/api/v1/exams/{examId}/room-preparations</code></header>
   <p>Yêu cầu session của Ban Tuyển sinh hoặc Quản trị và header <code>X-CSRF-Token</code>. API gọi transaction UC06–UC07 hiện có, tạo đúng điểm thi được chọn và đủ phòng 30 chỗ.</p>
   <pre class="api-response">{
  "selection": "truong:THPT_4"
}</pre>
   <p class="muted">Endpoint ghi không có nút thử trên trang công khai để tránh thao tác nhầm. Dùng OpenAPI hoặc Postman sau khi đăng nhập để kiểm thử có kiểm soát.</p>
  </article>
 </section>
</div></main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
