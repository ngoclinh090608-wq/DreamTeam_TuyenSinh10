<?php
declare(strict_types=1);
/** Phần riêng của grading/ và publication/ (UC12 – UC15). Hàm hiển thị chung nằm trong includes/ui.php. */
require_once __DIR__ . '/ui.php';
require_once dirname(__DIR__) . '/services/grading_service.php';

/** Thanh tab giữa các chức năng của module. */
function grading_tabs(string $module, string $current): void {
    $tabs = $module === 'grading'
        ? ['lich-su.php' => 'Lịch sử chỉnh sửa điểm · UC12', 'cap-nhat.php' => 'Cập nhật kết quả chấm · UC13', 'thong-ke.php' => 'Thống kê kết quả chấm · UC14']
        : array_filter([
            'diem-thi.php' => has_role(current_user(), ['BAN_TUYEN_SINH']) ? 'Công bố điểm thi · UC15' : null,
            'tuyen-sinh.php' => has_role(current_user(), ['HD_TUYEN_SINH']) ? 'Công bố kết quả tuyển sinh · UC22' : null,
        ]);
    echo '<nav class="ui-tabs" aria-label="Chức năng">';
    foreach ($tabs as $file => $label) {
        $on = $file === $current;
        echo '<a href="' . e(url($module . '/' . $file)) . '"' . ($on ? ' class="is-active" aria-current="page"' : '') . '>' . e($label) . '</a>';
    }
    echo '</nav>';
}
