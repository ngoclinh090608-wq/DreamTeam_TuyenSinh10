<div class="page-head"><div><div class="eyebrow"><span></span> HỒ SƠ TUYỂN SINH</div><h1>Trang cá nhân</h1><p>Theo dõi hồ sơ và nguyện vọng của bạn.</p></div></div>
<?php if (!$studentDashboard): ?>
<div class="notice notice-blue" role="alert">Không tải được hồ sơ gắn với tài khoản của bạn. Vui lòng liên hệ cán bộ phụ trách.</div>
<?php else: $profile = $studentDashboard['profile']; ?>
<section class="panel"><div class="panel-heading"><div><h2>Thông tin của tôi</h2><p><?= e($profile['hoTen']) ?></p></div></div>
<dl class="lookup-identity">
 <div><dt>Mã hồ sơ</dt><dd><?= e($profile['maHoSo']) ?></dd></div>
 <div><dt>Số báo danh</dt><dd><?= e($profile['soBaoDanh'] ?: 'Chưa được cấp') ?></dd></div>
 <div><dt>Trường THCS</dt><dd><?= e(school_display_name($profile['tenTruongTHCS'])) ?></dd></div>
 <div><dt>Trạng thái hồ sơ</dt><dd><?= e($profile['trangThaiDangKyDuThi']) ?></dd></div>
</dl></section>
<section class="panel"><div class="panel-heading"><div><h2>Nguyện vọng của tôi</h2><p>Các trường THPT bạn đã đăng ký.</p></div></div>
<ol><?php foreach ($studentDashboard['wishes'] as $wish): ?><li>Nguyện vọng <?= (int)$wish['thuTuNguyenVong'] ?>: <?= e(school_display_name($wish['tenTruongTHPT'])) ?></li><?php endforeach; ?></ol>
<?php if (!$studentDashboard['wishes']): ?><p>Bạn chưa có nguyện vọng đăng ký.</p><?php endif; ?></section>
<?php endif; ?>
<section class="panel"><div class="panel-heading"><div><h2>Truy cập nhanh</h2><p>Chức năng dành cho tài khoản của bạn.</p></div></div><?php require __DIR__ . '/dashboard_quick_links.php'; ?></section>
