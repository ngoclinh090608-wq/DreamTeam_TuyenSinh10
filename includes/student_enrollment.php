<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/services/student_results_service.php';
$rows = []; $error = null;
try { $rows = student_published_admissions($user); }
catch (BusinessException $e) { $error = $e->getMessage(); }
catch (Throwable $e) { error_log('student enrollment: ' . $e->getMessage()); $error = 'Không thể tải tình trạng nhập học. Vui lòng thử lại.'; }
ui_page_start('enrollment', 'Tình trạng nhập học của tôi', 'Hồ sơ tuyển sinh', 'Thông tin từ danh sách kết quả tuyển sinh đã công bố.', 'UC19');
if ($error) ui_notice('error', $error);
?>
<section class="panel ui-mt">
<?php if (!$error && !$rows): ?><p>Chưa có kết quả tuyển sinh được công bố cho hồ sơ của bạn.</p><?php endif; ?>
<?php foreach ($rows as $row): ?>
<article class="ui-mt"><h2>Nguyện vọng <?= (int)$row['thuTuNguyenVong'] ?> · <?= e(school_display_name($row['tenTruongTHPT'])) ?></h2>
<p><?= e($row['tenDot']) ?> · <?= e($row['trangThaiXetTuyen']) ?></p>
<?php if ($row['trangThaiXetTuyen'] === 'Trúng tuyển'): ?>
<p>Tình trạng nhập học: <strong><?= e($row['trangThaiNhapHoc']) ?></strong></p>
<p>Thời gian xác nhận: <?= ui_fmt_time($row['thoiGianXacNhanNhapHoc']) ?></p>
<?php endif; ?></article>
<?php endforeach; ?>
<a class="btn btn-outline ui-mt" href="<?= e(url('results/')) ?>">Xem kết quả tuyển sinh</a>
</section>
<?php ui_page_end(); ?>
