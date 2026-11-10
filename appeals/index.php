<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/ui.php';
require_once dirname(__DIR__) . '/services/appeal_service.php';
$user = require_role(['HOC_SINH', 'HD_PHUC_KHAO']);
$isStudent = has_role($user, ['HOC_SINH']);
$pageNumber = max(1, min(100000, (int)ui_input('page')));
$error = null; $requests = $papers = []; $available = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = ui_csrf_error();
    if (!$error) {
        try {
            if ($isStudent && ui_input('action') === 'submit') {
                appeal_submit($user, ui_input('paper'), ui_input('reason'));
                flash_set('success', 'Gửi yêu cầu phúc khảo thành công.');
            } elseif (!$isStudent && ui_input('action') === 'complete') {
                appeal_complete($user, ui_input('request'), ui_input('score'), ui_input('conclusion'), ui_input('evidence'), ui_input('minutes'));
                flash_set('success', 'Đã lưu kết quả, minh chứng, biên bản và cập nhật điểm phúc khảo.');
            } else throw new BusinessException('Thao tác không hợp lệ với vai trò của bạn.');
            ui_redirect('appeals/');
        } catch (BusinessException $e) { $error = $e->getMessage(); }
        catch (Throwable $e) { error_log('appeal write: ' . $e->getMessage()); $error = 'Không thể lưu phúc khảo. Chưa có thay đổi nào được lưu. Vui lòng thử lại.'; }
    }
}
try {
    $requests = appeal_requests($user, $pageNumber);
    if ($isStudent) {
        $papers = appeal_student_papers($user);
        foreach ($papers as $paper) if (!$paper['maYeuCau'] && appeal_window($paper['thoiDiemCongBo'])['open']) $available[] = $paper;
    }
} catch (BusinessException $e) { $error = $e->getMessage(); }
catch (Throwable $e) { error_log('appeal read: ' . $e->getMessage()); $error = 'Không thể tải dữ liệu phúc khảo. Vui lòng thử lại.'; }
ui_page_start('appeals', $isStudent ? 'Phúc khảo của tôi' : 'Kết quả phúc khảo', 'Phúc khảo điểm thi', $isStudent ? 'Chỉ tiếp nhận bài thi của bạn từ ngày sau công bố điểm, trong 7 ngày.' : 'Xử lý yêu cầu của kỳ thi được phân công; điểm và hồ sơ phúc khảo được lưu cùng nhau.', $isStudent ? 'UC18' : 'UC16');
ui_flashes(); if ($error) ui_notice('error', $error);
?>
<?php if ($isStudent): ?>
<section class="panel ui-mt"><h2>Gửi yêu cầu phúc khảo</h2>
<?php if (!$available): ?><p>Hiện không có bài thi đủ điều kiện gửi yêu cầu. Điểm phải được công bố và bài thi chưa có yêu cầu phúc khảo.</p><?php else: ?>
<form method="post">
<?= csrf_field() ?><input type="hidden" name="action" value="submit">
<div class="field"><label for="appeal-paper">Bài thi</label><select id="appeal-paper" name="paper" required>
<option value="">Chọn bài thi</option>
<?php foreach ($available as $paper): ?><option value="<?= e($paper['maBaiThi']) ?>"<?= ui_input('paper') === $paper['maBaiThi'] ? ' selected' : '' ?>><?= e(catalog_display_name($paper['tenKyThi']) . ' · ' . $paper['tenMonThi'] . ' · ' . number_format((float)$paper['diemThi'], 2)) ?></option><?php endforeach; ?>
</select></div>
<div class="field"><label for="appeal-reason">Lý do phúc khảo</label><textarea id="appeal-reason" name="reason" maxlength="500" required><?= e(ui_input('reason')) ?></textarea></div>
<button class="btn btn-primary" type="submit">Xác nhận gửi yêu cầu phúc khảo</button>
</form><?php endif; ?>
<h3>Thời hạn theo bài thi (giờ Việt Nam)</h3>
<ul><?php foreach ($papers as $paper): $window = appeal_window($paper['thoiDiemCongBo']); ?>
<li><?= e($paper['tenMonThi']) ?> · <?= e(catalog_display_name($paper['tenKyThi'])) ?>:
<?php if ($window['start']): ?><?= e($window['start']->format('d/m/Y H:i')) ?> – <?= e($window['end']->modify('-1 second')->format('d/m/Y H:i:s')) ?><?= $window['open'] ? ' (đang tiếp nhận)' : ' (ngoài thời hạn)' ?>
<?php else: ?>Chưa có lịch tiếp nhận phúc khảo.<?php endif; ?></li>
<?php endforeach; ?></ul></section>
<?php endif; ?>
<section class="panel ui-mt"><h2><?= $isStudent ? 'Yêu cầu đã gửi' : 'Yêu cầu trong kỳ thi được phân công' ?></h2>
<p>Trang <?= $pageNumber ?> · Tối đa 200 yêu cầu mỗi trang.</p>
<?php if (!$requests && !$error): ?><p>Chưa có yêu cầu phúc khảo.</p><?php endif; ?>
<?php foreach ($requests as $row): ?>
<article class="ui-mt" data-appeal-request="<?= e($row['maYeuCau']) ?>">
<h3><?= e($row['maYeuCau']) ?> · <?= e($row['tenMonThi']) ?></h3>
<p><?= e(catalog_display_name($row['tenKyThi'])) ?><?= !$isStudent ? ' · ' . e($row['maThiSinh']) : '' ?> · <?= e($row['trangThai']) ?></p>
<p>Lý do: <?= e($row['lyDo'] ?? '') ?></p>
<p>Điểm hiện tại: <strong><?= $row['diemHienTai'] === null ? 'Chưa có điểm' : e(number_format((float)$row['diemHienTai'], 2)) ?></strong></p>
<?php if ($row['diemSauPhucKhao'] !== null): ?>
<p>Điểm trước phúc khảo: <strong><?= e(number_format((float)$row['diemTruocPhucKhao'], 2)) ?></strong> · Điểm sau phúc khảo: <strong><?= e(number_format((float)$row['diemSauPhucKhao'], 2)) ?></strong></p>
<p>Kết luận: <?= e($row['ketLuan']) ?></p><p>Minh chứng: <?= e($row['minhChung'] ?? '') ?></p><p>Biên bản: <?= e($row['bienBan'] ?? '') ?></p>
<?php elseif (!$isStudent && $row['trangThai'] === 'Chờ xử lý'): $formId = 'pk-' . $row['maYeuCau']; ?>
<details><summary>Nhập kết quả phúc khảo</summary><form method="post">
<?= csrf_field() ?><input type="hidden" name="action" value="complete"><input type="hidden" name="request" value="<?= e($row['maYeuCau']) ?>">
<div class="field"><label for="<?= e($formId) ?>-score">Điểm sau phúc khảo (0 – <?= e($row['thangDiem']) ?>, bước <?= e((string)GRADING_BUOC_DIEM) ?>)</label><input id="<?= e($formId) ?>-score" name="score" type="number" min="0" max="<?= e($row['thangDiem']) ?>" step="<?= e((string)GRADING_BUOC_DIEM) ?>" required></div>
<div class="field"><label for="<?= e($formId) ?>-conclusion">Kết luận</label><input id="<?= e($formId) ?>-conclusion" name="conclusion" maxlength="100" required></div>
<p>Biên bản và minh chứng chỉ bắt buộc khi điểm thay đổi.</p>
<div class="field"><label for="<?= e($formId) ?>-evidence">Thông tin minh chứng đã đối chiếu</label><textarea id="<?= e($formId) ?>-evidence" name="evidence" maxlength="255"></textarea></div>
<div class="field"><label for="<?= e($formId) ?>-minutes">Nội dung biên bản</label><textarea id="<?= e($formId) ?>-minutes" name="minutes" maxlength="1000"></textarea></div>
<button type="submit" class="btn btn-primary">Xác nhận lưu kết quả phúc khảo</button>
</form></details>
<?php endif; ?></article>
<?php endforeach; ?>
<nav class="ui-actions ui-mt" aria-label="Phân trang yêu cầu phúc khảo">
<?php if ($pageNumber > 1): ?><a class="btn btn-outline" href="<?= e(url('appeals/', ['page' => $pageNumber - 1])) ?>">Trang trước</a><?php endif; ?>
<?php if (count($requests) === 200 && $pageNumber < 100000): ?><a class="btn btn-outline" href="<?= e(url('appeals/', ['page' => $pageNumber + 1])) ?>">Trang sau</a><?php endif; ?>
</nav></section>
<?php ui_page_end(); ?>
