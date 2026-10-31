<div class="page-head"><div><div class="eyebrow"><span></span> HỒ SƠ TUYỂN SINH</div><h1>Trang cá nhân</h1><p>Thông tin hồ sơ và các nguyện vọng đăng ký vào lớp 10.</p></div></div>
<?php if (!$studentDashboard): ?>
<div class="notice notice-blue" role="alert">Không tải được hồ sơ gắn với tài khoản của bạn. Vui lòng liên hệ cán bộ phụ trách.</div>
<?php else: $profile = $studentDashboard['profile']; ?>
<div class="student-overview">
<section class="panel student-profile"><div class="panel-heading"><div><h2>Thông tin của tôi</h2><p>Hồ sơ học sinh</p></div><span class="status status-success"><?= e($profile['trangThaiDangKyDuThi']) ?></span></div>
<dl class="student-profile-fields">
 <div class="student-field-wide"><dt>Họ và tên</dt><dd><?= e($profile['hoTen']) ?></dd></div>
 <div><dt>Mã hồ sơ</dt><dd><?= e($profile['maHoSo']) ?></dd></div>
 <div><dt>Số báo danh</dt><dd><?= e($profile['soBaoDanh'] ?: 'Chưa được cấp') ?></dd></div>
 <div><dt>Ngày sinh</dt><dd><?= e(date('d/m/Y', strtotime($profile['ngaySinh']))) ?></dd></div>
 <div class="student-field-wide"><dt>Trường đang học · THCS</dt><dd><?= e(school_display_name($profile['tenTruongTHCS'])) ?></dd></div>
</dl></section>
<section class="panel student-wishes"><div class="panel-heading"><div><h2>Nguyện vọng của tôi</h2><p>Trường THPT đăng ký dự tuyển</p></div></div>
<div class="student-wish-list"><?php foreach ($studentDashboard['wishes'] as $wish): ?><div class="student-wish-row"><span class="student-wish-order">NV<?= (int)$wish['thuTuNguyenVong'] ?></span><div><small>Nguyện vọng <?= (int)$wish['thuTuNguyenVong'] ?></small><strong><?= e(school_display_name($wish['tenTruongTHPT'])) ?></strong></div></div><?php endforeach; ?></div>
<?php if (!$studentDashboard['wishes']): ?><p>Bạn chưa có nguyện vọng đăng ký.</p><?php endif; ?>
<a class="text-link" href="<?= e(url('aspirations/')) ?>">Xem nguyện vọng của tôi →</a>
</section></div>
<?php endif; ?>
<section class="panel"><div class="panel-heading"><div><h2>Truy cập nhanh</h2><p>Chức năng dành cho tài khoản của bạn.</p></div></div><?php require __DIR__ . '/dashboard_quick_links.php'; ?></section>
