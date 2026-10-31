<?php
$studentShellUser = current_user();
$studentNavigation = [
    ['admin', 'admin/', 'Hồ sơ tuyển sinh', '♙'],
    ['aspirations', 'aspirations/', 'Nguyện vọng', '✧'],
    ['admission-info', 'admission-info/', 'Thông tin tuyển sinh', '▤'],
    ['appeals', 'appeals/', 'Phúc khảo', '↺'],
    ['enrollment', 'enrollment/', 'Nhập học', '☑'],
];
?>
<header class="student-site-header">
 <div class="student-header-inner">
  <a class="student-brand" href="<?= e(url('admin/')) ?>"><img src="<?= e(url('assets/images/logo.svg')) ?>" alt="" width="38" height="38"><span>CỔNG TUYỂN SINH LỚP 10</span></a>
  <div class="student-account"><span class="student-avatar" aria-hidden="true">♙</span><span><strong><?= e($studentShellUser['hoTen']) ?></strong><small>Học sinh</small></span><details class="student-account-menu"><summary aria-label="Tùy chọn tài khoản">Tài khoản ▾</summary><div><a href="<?= e(url('auth/doi-mat-khau.php')) ?>">Đổi mật khẩu</a><form method="post" action="<?= e(url('auth/logout.php')) ?>"><?= csrf_field() ?><button type="submit">Đăng xuất</button></form></div></details></div>
 </div>
</header>
<section class="student-portal-welcome" aria-label="Cổng tuyển sinh học sinh"><h1>Chào mừng đến với <span>Cổng tuyển sinh</span></h1><p>Theo dõi hồ sơ cá nhân, tìm hiểu thông tin trường học và các nguyện vọng đăng ký vào lớp 10.</p></section>
<nav class="student-portal-nav" aria-label="Chức năng học sinh">
 <?php foreach ($studentNavigation as [$studentNavKey, $studentNavPath, $studentNavLabel, $studentNavIcon]): if (!can_access_module($studentShellUser, $studentNavKey)) continue; ?>
 <a href="<?= e(url($studentNavPath)) ?>"<?= $active === $studentNavKey ? ' class="is-active" aria-current="page"' : '' ?>><span aria-hidden="true"><?= e($studentNavIcon) ?></span><?= e($studentNavLabel) ?></a>
 <?php endforeach; ?>
 <a href="<?= e(url('admission-info/huong-dan.php')) ?>"><span aria-hidden="true">?</span>Hướng dẫn</a>
</nav>
<main id="main" class="student-portal-content">
