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
  <meta name="theme-color" content="#1262af">
  <meta name="description" content="Giao diện minh họa hệ thống quản lý tuyển sinh lớp 10 của Dream Team">
  <title><?= e($page_title) ?> | Dream Team</title>
  <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/images/logo.svg')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
  <script defer src="<?= e(url('assets/js/app.js')) ?>"></script>
</head>
<body class="<?= $layout === 'dashboard' ? 'dash-body' : 'public-body' ?>">
<a class="skip-link" href="#main">Bỏ qua điều hướng</a>
<?php if ($layout === 'dashboard'): ?>
<div class="dash-shell">
  <?php require __DIR__ . '/sidebar.php'; ?>
  <div class="dash-main">
    <header class="dash-topbar">
      <div class="dash-topbar-left"><button class="hamburger" aria-label="Mở menu" data-menu-toggle>☰</button><span class="breadcrumb">Hệ thống <span>/</span> <?= e($page_title) ?></span></div>
      <div class="dash-topbar-right"><a class="avatar" href="<?= e(url('auth/login.php')) ?>" title="Trở về đăng nhập">DT</a></div>
    </header>
    <main id="main" class="dash-content">
<?php else: ?>
<header class="site-header">
  <div class="container topnav">
    <a class="brand" href="<?= e(url()) ?>"><img src="<?= e(url('assets/images/logo.svg')) ?>" alt="Dream Team" class="brand-mark"><span class="brand-text"><strong>TUYỂN SINH 10</strong><small>DREAM TEAM · TP. HỒ CHÍ MINH</small></span></a>
    <nav class="public-nav" id="public-nav" aria-label="Điều hướng chính">
      <a href="<?= e(url('index.php')) ?>#timeline">Lịch tuyển sinh</a>
      <a href="<?= e(url('index.php')) ?>#targets">Chỉ tiêu</a>
      <a href="<?= e(url('index.php')) ?>#schools">Trường học</a>
      <a href="<?= e(url('index.php')) ?>#guides">Hướng dẫn</a>
    </nav>
    <div class="nav-actions"><a class="btn btn-soft btn-sm" href="<?= e(url('results/')) ?>">Tra cứu</a><a class="btn btn-primary btn-sm" href="<?= e(url('auth/login.php')) ?>">Đăng nhập <span aria-hidden="true">↗</span></a></div>
    <button class="hamburger mobile-hamburger" aria-label="Mở menu" aria-controls="public-nav" aria-expanded="false" data-public-menu>☰</button>
  </div>
</header>
<?php endif; ?>
