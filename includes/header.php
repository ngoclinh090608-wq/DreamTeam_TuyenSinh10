<?php
require_once __DIR__ . '/functions.php';
$page_title = $page_title ?? 'Trang chủ';
$layout = $layout ?? 'public';
$active = $active ?? '';
$cfg = app_config();
?><!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#3F7CA5">
  <meta name="description" content="Cổng thông tin tuyển sinh vào lớp 10: thông tin trường học, chỉ tiêu, nguyện vọng và kết quả tuyển sinh.">
  <title><?= e($page_title) ?> | Cổng thông tin tuyển sinh vào lớp 10</title>
  <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/images/logo.svg')) ?>">
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
  <link rel="stylesheet" href="<?= e(url('assets/css/portal.css')) ?>">
  <script defer src="<?= e(url('assets/js/app.js')) ?>"></script>
</head>
<body class="<?= $layout === 'dashboard' ? 'dash-body' : 'public-body' ?>">
<a class="skip-link" href="#main">Bỏ qua điều hướng</a>
<?php if ($layout === 'dashboard'): ?>
<div class="dash-shell">
  <?php require __DIR__ . '/sidebar.php'; ?>
  <div class="dash-main">
    <header class="dash-topbar">
      <div class="dash-topbar-left"><button class="hamburger" aria-label="Mở menu quản trị" aria-controls="sidebar" aria-expanded="false" data-menu-toggle>☰</button><span class="breadcrumb">Hệ thống <span>/</span> <?= e($page_title) ?></span></div>
      <div class="dash-topbar-right"><?php $__u = function_exists('current_user') ? current_user() : null; if ($__u): ?>
        <span class="topbar-user"><strong><?= e($__u['hoTen']) ?></strong><small><?= e(ten_vai_tro($__u['vaiTro'])) ?></small></span>
        <a class="btn btn-outline btn-sm" href="<?= e(url('auth/doi-mat-khau.php')) ?>">Đổi mật khẩu</a>
        <form method="post" action="<?= e(url('auth/logout.php')) ?>" class="topbar-logout"><?= csrf_field() ?><button class="btn btn-soft btn-sm" type="submit">Đăng xuất</button></form>
      <?php else: ?><a class="avatar" href="<?= e(url('auth/login.php')) ?>" title="Đăng nhập">10</a><?php endif; ?></div>
    </header>
    <main id="main" class="dash-content">
<?php else: ?>
<header class="site-header">
  <div class="masthead">
    <div class="container masthead-inner">
      <a class="brand" href="<?= e(url()) ?>">
        <img src="<?= e(url('assets/images/logo.svg')) ?>" alt="" class="brand-mark" width="60" height="60">
        <span class="brand-text"><strong>CỔNG THÔNG TIN TUYỂN SINH VÀO LỚP 10</strong><small>Thông tin rõ ràng · Đồng hành cùng học sinh và phụ huynh</small></span>
      </a>
      <div class="masthead-note"><span>Năm học</span><strong>2026 – 2027</strong><small>Tuyển sinh lớp 10</small></div>
    </div>
  </div>
  <div class="nav-band">
    <div class="container nav-inner">
      <button class="hamburger mobile-hamburger" type="button" aria-label="Mở menu" aria-controls="public-nav" aria-expanded="false" data-public-menu><span aria-hidden="true">☰</span> <span>Menu tuyển sinh</span></button>
      <nav class="public-nav" id="public-nav" aria-label="Điều hướng chính">
        <?php $public_active = $public_active ?? ($page_title === 'Trang chủ' ? 'home' : ($page_title === 'Đăng nhập' ? 'login' : ''));
        foreach ([
          ['home', '', 'Trang chủ'],
          ['info', 'admission-info/huong-dan.php', 'Thông tin tuyển sinh'],
          ['schools', 'schools/danh-sach.php', 'Danh sách trường'],
          ['aspirations', 'aspirations/', 'Đăng ký nguyện vọng'],
          ['scores', 'results/?view=diem-thi', 'Tra cứu điểm thi'],
          ['results', 'results/?view=tuyen-sinh', 'Kết quả tuyển sinh'],
          ['login', 'auth/login.php', 'Đăng nhập'],
        ] as [$nav_key, $nav_path, $nav_label]): ?>
          <a href="<?= e(url($nav_path)) ?>"<?= $public_active === $nav_key ? ' class="is-current" aria-current="page"' : '' ?>><?= e($nav_label) ?></a>
        <?php endforeach; ?>
      </nav>
    </div>
  </div>
</header>
<?php endif; ?>
