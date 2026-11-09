<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/ui.php';
require_once dirname(__DIR__) . '/services/candidate_number_service.php';
$user = require_role(['BAN_TUYEN_SINH']);
$error = null; $rows = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = ui_csrf_error();
    if (!$error) {
        try {
            $ids = $_POST['candidates'] ?? [];
            if (!is_array($ids)) throw new BusinessException('Danh sách thí sinh không hợp lệ.');
            $issued = candidate_issue_numbers($user, $ids);
            flash_set('success', 'Đã cấp số báo danh cho ' . count($issued) . ' thí sinh.');
            ui_redirect('candidates/cap-so-bao-danh.php');
        } catch (BusinessException $e) { $error = $e->getMessage(); }
        catch (Throwable $e) { error_log('issue SBD: ' . $e->getMessage()); $error = 'Không thể cấp số báo danh. Chưa có thay đổi nào được lưu.'; }
    }
}
try { $rows = db()->query("SELECT maThiSinh,hoTen,maHoSo FROM ThiSinh WHERE dieuKienDuThi=1 AND trangThaiDangKyDuThi='Đã duyệt' AND (soBaoDanh IS NULL OR soBaoDanh='') ORDER BY maThiSinh LIMIT 200")->fetchAll(); }
catch (Throwable $e) { error_log('pending SBD: ' . $e->getMessage()); $error = 'Không thể tải danh sách thí sinh. Vui lòng thử lại.'; }
ui_page_start('candidates', 'Cấp số báo danh', 'Hồ sơ dự thi', 'Chỉ cấp cho hồ sơ đã duyệt và đủ điều kiện. Số đã cấp được giữ nguyên.', 'UC03');
ui_flashes(); if ($error) ui_notice('error', $error);
?>
<section class="panel ui-mt"><form method="post">
<?= csrf_field() ?>
<p>Hiển thị tối đa 200 hồ sơ chưa có số báo danh. Chọn hồ sơ rồi xác nhận cấp số.</p>
<div class="table-scroll"><table class="data-table"><thead><tr><th>Chọn</th><th>Mã thí sinh</th><th>Mã hồ sơ</th><th>Họ tên</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr><td><input type="checkbox" name="candidates[]" value="<?= e($row['maThiSinh']) ?>" aria-label="Chọn <?= e($row['maThiSinh']) ?>"></td><td><?= e($row['maThiSinh']) ?></td><td><?= e($row['maHoSo']) ?></td><td><?= e($row['hoTen']) ?></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="4">Không có hồ sơ đủ điều kiện đang chờ cấp số báo danh.</td></tr><?php endif; ?>
</tbody></table></div><div class="ui-actions ui-mt"><button class="btn btn-primary" type="submit"<?= !$rows ? ' disabled' : '' ?>>Xác nhận cấp số báo danh</button><a class="btn btn-outline" href="<?= e(url('candidates/')) ?>">Danh sách thí sinh</a></div>
</form></section>
<?php ui_page_end(); ?>
