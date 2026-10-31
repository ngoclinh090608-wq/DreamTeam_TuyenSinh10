<div class="page-head"><div><div class="eyebrow"><span></span> TUYỂN SINH LỚP 10</div><h1>Văn bản và thông báo tuyển sinh</h1><p>Tìm hiểu chỉ tiêu, phương thức tuyển sinh và thông tin đã công bố của các trường THPT.</p></div></div>
<form method="get" class="panel student-admission-search"><label for="admission-search">Tìm trường hoặc khu vực</label><div><input id="admission-search" name="q" maxlength="120" value="<?= e($admissionSearch) ?>" placeholder="Nhập tên trường THPT hoặc khu vực"><button type="submit" class="btn btn-primary">Tìm kiếm</button><a class="btn btn-outline" href="<?= e(url('admission-info/')) ?>">Tất cả</a></div></form>
<?php if ($admissionNotices === null): ?>
<div class="notice notice-blue" role="alert">Không tải được thông tin tuyển sinh. Vui lòng thử lại sau.</div>
<?php elseif (!$admissionNotices): ?>
<div class="panel"><p><?= $admissionSearch !== '' ? 'Không tìm thấy trường phù hợp với từ khóa.' : 'Chưa có thông tin tuyển sinh được công bố.' ?></p></div>
<?php else: ?>
<div class="student-admission-grid">
<?php foreach ($admissionNotices as $notice): ?>
<article class="panel student-admission-card">
 <div class="panel-heading"><div><p><?= e($notice['tenKhuVuc']) ?> · Năm học <?= (int)$notice['namTuyenSinh'] ?>–<?= (int)$notice['namTuyenSinh'] + 1 ?></p><h2><?= e(school_display_name($notice['tenTruongTHPT'])) ?></h2></div></div>
 <div class="student-quota"><strong><?= (int)$notice['chiTieuTuyenSinh'] ?></strong><span>chỉ tiêu tuyển sinh</span></div>
 <dl class="student-admission-details">
  <div><dt>Đợt tuyển sinh</dt><dd><?= e($notice['tenDot']) ?></dd></div>
  <div><dt>Hệ đào tạo</dt><dd><?= e($notice['heDaoTao']) ?></dd></div>
  <div><dt>Phương thức tuyển sinh</dt><dd><?= e($notice['phuongThucTuyenSinh']) ?></dd></div>
  <div><dt>Học phí theo thông tin công bố</dt><dd><?= e(number_format((float)$notice['hocPhi'], 0, ',', '.')) ?> đồng</dd></div>
 </dl>
 <?php if (trim((string)$notice['noiDungChiTiet']) !== ''): ?><details><summary>Nội dung tuyển sinh</summary><p><?= nl2br(e($notice['noiDungChiTiet'])) ?></p></details><?php endif; ?>
</article>
<?php endforeach; ?></div>
<?php endif; ?>
