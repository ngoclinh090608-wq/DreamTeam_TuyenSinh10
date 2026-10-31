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
<details class="student-score-panel"><summary>Xem điểm thi đã công bố</summary>
<?php if (!$studentDashboard['scores']): ?><p>Điểm thi của bạn chưa được công bố trên hệ thống.</p><?php else: ?>
<div class="table-scroll"><table class="data-table"><thead><tr><th>Kỳ thi</th><th>Môn thi</th><th>Điểm thi</th></tr></thead><tbody><?php foreach ($studentDashboard['scores'] as $score): ?><tr><td><?= e(catalog_display_name($score['tenKyThi'])) ?></td><td><?= e($score['tenMonThi']) ?></td><td><?= e(number_format((float)$score['diemThi'], 2)) ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php endif; ?></details>
<div class="student-result-status"><h3>Trạng thái hồ sơ tuyển sinh</h3><p>Hồ sơ: <strong><?= e($profile['trangThaiDangKyDuThi']) ?></strong></p><p>Số báo danh: <strong><?= e($profile['soBaoDanh'] ?: 'Chưa được cấp') ?></strong></p>
<?php if (!$studentDashboard['results']): ?><p>Chưa có kết quả xét tuyển được công bố cho hồ sơ của bạn.</p><?php else: foreach ($studentDashboard['results'] as $result): ?><p>Nguyện vọng <?= (int)$result['thuTuNguyenVong'] ?>: <strong><?= e($result['trangThaiXetTuyen']) ?></strong><?= $result['tenTruongTHPT'] ? ' · '.e(school_display_name($result['tenTruongTHPT'])) : '' ?></p><?php endforeach; endif; ?></div>
</section></div>
<?php endif; ?>
