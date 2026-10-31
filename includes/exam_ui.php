<?php
declare(strict_types=1);
/**
 * Phần giao diện riêng của module exam/ (Kỳ thi, Phòng thi, Phân phòng, Phân công ra đề - UC06, UC07, UC08).
 * Hàm hiển thị chung nằm trong includes/ui.php.
 */
require_once __DIR__ . '/ui.php';
require_once dirname(__DIR__) . '/services/exam_service.php';

/**
 * Thanh tab điều hướng giữa các chức năng thuộc module exam.
 */
function exam_tabs(string $current): void {
    $u = current_user();
    $tabs = [];

    // Tổng quan: mọi người có quyền module exam
    $tabs['index.php'] = 'Tổng quan phòng thi';

    if (has_role($u, ['BAN_TUYEN_SINH', 'QUAN_TRI'])) {
        $tabs['tao-phong.php'] = 'Tạo phòng thi · UC06';
        $tabs['phan-phong.php'] = 'Phân phòng thi · UC07';
    }

    if (has_role($u, ['HD_RA_DE', 'BAN_TUYEN_SINH', 'QUAN_TRI'])) {
        $tabs['phan-cong-ra-de.php'] = 'Phân công ra đề · UC08';
    }

    echo '<nav class="ui-tabs" aria-label="Chức năng kỳ thi">';
    foreach ($tabs as $file => $label) {
        $on = $file === $current;
        echo '<a href="' . e(url('exam/' . $file)) . '"' . ($on ? ' class="is-active" aria-current="page"' : '') . '>' . e($label) . '</a>';
    }
    echo '</nav>';
}

