<?php if (!$studentDashboard): ?>
<div class="notice notice-blue" role="alert">Không tải được hồ sơ gắn với tài khoản của bạn. Vui lòng liên hệ cán bộ phụ trách.</div>
<?php else: $profile = $studentDashboard['profile']; ?>
<div class="student-overview">
<section class="panel student-profile"><div class="panel-heading"><div><h2>Thông tin của tôi</h2><p>Hồ sơ học sinh</p></div><button type="button" class="btn btn-outline btn-sm student-print-button" data-student-print>In hồ sơ</button><button type="submit" form="student-profile-save" class="btn btn-primary btn-sm"<?= !$studentDashboard['supplement']['ready'] ? ' disabled' : '' ?>>Lưu</button><span class="status status-success"><?= e($profile['trangThaiDangKyDuThi']) ?></span></div>
<?php require __DIR__.'/student_profile_form.php'; ?></section>
<section class="panel student-wishes"><div class="panel-heading"><div><h2>Nguyện vọng của tôi</h2><p>Trạng thái hồ sơ tuyển sinh</p></div><div class="student-profile-actions"><button type="button" class="btn btn-primary btn-sm" data-student-dialog-open="student-score-dialog">Xem điểm</button><button type="button" class="btn btn-outline btn-sm" data-student-dialog-open="student-exam-dialog">Thông tin thi tuyển</button></div></div>
<?php require __DIR__ . '/student_wish_progress.php'; ?>

</section></div>
<?php require __DIR__.'/student_exam_dialogs.php'; ?>
<?php endif; ?>
