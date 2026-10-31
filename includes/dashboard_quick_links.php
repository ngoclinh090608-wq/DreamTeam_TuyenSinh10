<?php
/** Presentation only: reuse module permissions and the UC11 page's role guard. */
$dashboardUser = current_user();
$dashboardLinks = [
    ['admission-info', 'Thông tin tuyển sinh', 'Xem đợt tuyển sinh và chỉ tiêu', '▤'],
    ['aspirations', 'Đăng ký nguyện vọng', 'Thông tin nguyện vọng tuyển sinh', '✧'],
    ['exam', 'Kỳ thi & phòng thi', 'Thông tin kỳ thi và nhiệm vụ được phân công', '▣'],
    ['councils', 'Hội đồng thi', 'Quản lý thông tin hội đồng thi', '♧'],
    ['assignments', 'Phân công coi thi', 'Phân công cán bộ theo phòng và môn thi', '⇆'],
    ['papers', 'Bài thi & số phách', 'Tra cứu thông tin lô bài thi', '▥'],
    ['grading', 'Chấm thi & điểm', 'Nhập điểm, lịch sử chỉnh sửa và thống kê', '✓'],
    ['appeals', 'Phúc khảo', 'Thông tin yêu cầu phúc khảo', '↺'],
    ['admission', 'Xét tuyển nguyện vọng', 'Theo dõi và thực hiện xét tuyển', '◇'],
    ['enrollment', 'Nhập học', 'Thông tin và xác nhận nhập học', '☑'],
    ['publication', 'Công bố kết quả', 'Quản lý công bố theo nhiệm vụ được giao', '◉'],
    ['statistics', 'Thống kê tuyển sinh', 'Báo cáo tổng hợp tuyển sinh', '▥'],
];
$dashboardLinks = array_values(array_filter($dashboardLinks, fn($link) =>
    can_access_module($dashboardUser, $link[0])
    && ($link[0] !== 'assignments' || has_role($dashboardUser, ['HD_COI_THI']))
));
?>
<div class="quick-links" data-dashboard-links>
 <?php foreach ($dashboardLinks as [$moduleKey, $label, $description, $symbol]): ?>
  <a href="<?= e(url($moduleKey . '/')) ?>"><span class="quick-icon" aria-hidden="true"><?= e($symbol) ?></span><span><?= e($label) ?><small><?= e($description) ?></small></span><span aria-hidden="true">→</span></a>
 <?php endforeach; ?>
 <?php if (!$dashboardLinks): ?><p class="field-note">Các chức năng được hiển thị theo quyền của tài khoản. Bạn có thể sử dụng những mục được cấp quyền trong menu bên trái.</p><?php endif; ?>
</div>
