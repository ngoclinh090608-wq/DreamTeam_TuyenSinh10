<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/ui.php';
require_once dirname(__DIR__) . '/services/student_results_service.php';
$user = require_role(['HOC_SINH']);
$scores = $results = []; $error = null; $publicationState = 'unpublished'; $detail = null;
try {
    $scores = student_published_scores($user); $results = student_published_admissions($user);
    if (!$results) $publicationState = student_admission_publication_state($user);
    if (ui_input('ketQua') !== '') {
        foreach ($results as $row) if ($row['maKetQua'] === ui_input('ketQua')) $detail = $row;
        if (!$detail) $error = 'Không tìm thấy kết quả xét tuyển.';
    }
}
catch (BusinessException $e) { $error = $e->getMessage(); }
catch (Throwable $e) { error_log('results: ' . $e->getMessage()); $error = 'Không thể tải kết quả. Vui lòng thử lại.'; }
ui_page_start('results', 'Kết quả của tôi', 'Tra cứu tuyển sinh', 'Chỉ hiển thị điểm và kết quả đã công bố gắn với tài khoản của bạn.', 'UC04');
if ($error) ui_notice('error', $error);
?>
<section class="panel ui-mt"><h2>Điểm thi đã công bố</h2>
<?php if (!$error && !$scores): ?><p>Điểm thi của bạn chưa được công bố.</p><?php endif; ?>
<div class="table-scroll"><table class="data-table"><thead><tr><th>Kỳ thi</th><th>Môn thi</th><th>Điểm</th></tr></thead><tbody>
<?php foreach ($scores as $row): ?><tr><td><?= e(catalog_display_name($row['tenKyThi'])) ?></td><td><?= e($row['tenMonThi']) ?></td><td><?= e(number_format((float)$row['diemThi'], 2)) ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<section class="panel ui-mt"><h2>Kết quả tuyển sinh</h2>
<?php if (!$error && !$results): ?><p><?= $publicationState === 'missing' ? 'Không tìm thấy kết quả xét tuyển.' : 'Kết quả xét tuyển chưa được công bố.' ?></p><?php endif; ?>
<div class="table-scroll"><table class="data-table"><thead><tr><th>Đợt tuyển sinh</th><th>Nguyện vọng</th><th>Trường THPT</th><th>Điểm xét tuyển</th><th>Kết quả</th><th>Chi tiết</th></tr></thead><tbody>
<?php foreach ($results as $row): ?><tr><td><?= e($row['tenDot']) ?></td><td><?= (int)$row['thuTuNguyenVong'] ?></td><td><?= e(school_display_name($row['tenTruongTHPT'])) ?></td><td><?= $row['diemXetTuyen'] === null ? '—' : e(number_format((float)$row['diemXetTuyen'], 2)) ?></td><td><?= e($row['trangThaiXetTuyen']) ?></td><td><a href="<?= e(url('results/', ['ketQua' => $row['maKetQua']])) ?>">Xem chi tiết NV<?= (int)$row['thuTuNguyenVong'] ?></a></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php if ($detail): ?><article class="ui-mt"><h3>Chi tiết nguyện vọng <?= (int)$detail['thuTuNguyenVong'] ?></h3><p><?= e($detail['tenDot']) ?> · <?= (int)$detail['namTuyenSinh'] ?></p><p><?= e($detail['tenTruongTHPT']) ?> · <?= e($detail['trangThaiXetTuyen']) ?></p><p>Điểm xét tuyển: <?= $detail['diemXetTuyen'] === null ? '—' : e(number_format((float)$detail['diemXetTuyen'], 2)) ?></p><a class="btn btn-outline" href="<?= e(url('results/')) ?>">Quay lại danh sách kết quả</a></article><?php endif; ?>
<div class="ui-actions ui-mt"><a class="btn btn-outline" href="<?= e(url('enrollment/')) ?>">Tình trạng nhập học</a><a class="btn btn-outline" href="<?= e(url('appeals/')) ?>">Yêu cầu phúc khảo</a></div></section>
<?php ui_page_end(); ?>
