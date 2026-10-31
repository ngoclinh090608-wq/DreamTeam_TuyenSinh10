<?php
/** Student landing page: no system-wide statistics, samples, or management actions. */
$student = current_user();
?>
<div class="student-dashboard">
 <div class="page-head"><div><div class="eyebrow"><span></span>KHU VỰC HỌC SINH</div><h1>Trang tuyển sinh của bạn</h1><p>Xem thông tin tuyển sinh và truy cập các chức năng dành cho học sinh.</p></div></div>
 <div class="dash-panels student-dashboard-panels">
  <section class="panel student-welcome" aria-labelledby="student-welcome-title">
   <div class="panel-heading"><div><h2 id="student-welcome-title">Xin chào, <?= e($student['hoTen']) ?></h2><p>Chào mừng bạn đến với cổng tuyển sinh lớp 10.</p></div></div>
   <dl class="ui-kv ui-kv-1"><div><dt>Tài khoản</dt><dd><?= e($student['tenDangNhap']) ?></dd></div><div><dt>Vai trò</dt><dd>Học sinh</dd></div></dl>
   <p class="field-note">Chọn một chức năng bên cạnh để xem thông tin tuyển sinh, nguyện vọng, phúc khảo hoặc nhập học.</p>
   <a class="text-link" href="<?= e(url('admission-info/huong-dan.php')) ?>">Hướng dẫn sử dụng cổng tuyển sinh →</a>
  </section>
  <section class="panel" aria-labelledby="student-functions-title">
   <div class="panel-heading"><div><h2 id="student-functions-title">Các chức năng dành cho bạn</h2><p>Truy cập nhanh các mục của tài khoản học sinh.</p></div></div>
   <?php require __DIR__ . '/dashboard_quick_links.php'; ?>
  </section>
 </div>
</div>
