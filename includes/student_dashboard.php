<?php if (!$studentDashboard): ?>
<div class="notice notice-blue" role="alert">Không tải được hồ sơ gắn với tài khoản của bạn. Vui lòng liên hệ cán bộ phụ trách.</div>
<?php else: $profile = $studentDashboard['profile']; ?>
<div class="student-overview">
<section class="panel student-profile"><div class="panel-heading"><div><h2>Thông tin của tôi</h2><p>Hồ sơ học sinh</p></div><button type="button" class="btn btn-outline btn-sm student-print-button" data-student-print>In hồ sơ</button><button type="submit" form="student-profile-save" class="btn btn-primary btn-sm"<?= !$studentDashboard['supplement']['ready'] ? ' disabled' : '' ?>>Lưu</button><span class="status status-success"><?= e($profile['trangThaiDangKyDuThi']) ?></span></div>
<?php require __DIR__.'/student_profile_form.php'; ?></section>
<section class="panel student-wishes"><div class="panel-heading"><div><h2>Nguyện vọng của tôi</h2><p>Trạng thái hồ sơ tuyển sinh</p></div><div class="student-profile-actions"><button type="button" class="btn btn-primary btn-sm" data-student-scores>Xem điểm</button><a class="btn btn-outline btn-sm" href="<?= e(url('admission-info/')) ?>">Thông tin tuyển sinh</a></div></div>
<?php require __DIR__ . '/student_wish_progress.php'; ?>
<details id="student-scores" class="student-score-panel"><summary>Xem điểm thi đã công bố</summary>
<?php if (!$studentDashboard['scores']): ?><p>Điểm thi của bạn chưa được công bố trên hệ thống.</p><?php else: ?>
<div class="table-scroll"><table class="data-table"><thead><tr><th>Kỳ thi</th><th>Môn thi</th><th>Điểm thi</th></tr></thead><tbody><?php foreach ($studentDashboard['scores'] as $score): ?><tr><td><?= e(catalog_display_name($score['tenKyThi'])) ?></td><td><?= e($score['tenMonThi']) ?></td><td><?= e(number_format((float)$score['diemThi'], 2)) ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php endif; ?></details>
</section></div>
<?php endif; ?>
