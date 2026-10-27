<?php
declare(strict_types=1);
/** Phần hiển thị dùng chung cho grading/ và publication/ (UC12 – UC15). Vẫn dùng header/footer/sidebar chung. */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/services/csrf.php';
require_once dirname(__DIR__) . '/services/grading_service.php';

app_session_start();
require_auth();

/** Mở trang trong layout quản trị chung. */
function grading_page_start(string $active, string $title, string $eyebrow, string $desc, string $uc): void {
    $page_title = $title; $layout = 'dashboard';
    require __DIR__ . '/header.php';
    echo '<div class="grading-ui">';
    echo '<div class="page-head"><div><div class="eyebrow"><span></span>' . e($eyebrow) . '</div><h1>' . e($title) . '</h1><p>' . e($desc) . '</p></div>'
       . '<div class="page-head-actions"><span class="uc-tag">' . e($uc) . '</span></div></div>';
}

function grading_page_end(): void {
    $layout = 'dashboard';
    echo '</div>';
    require __DIR__ . '/footer.php';
}

/** Thanh tab giữa các chức năng của module. */
function grading_tabs(string $module, string $current): void {
    $tabs = $module === 'grading'
        ? ['lich-su.php' => 'Lịch sử chỉnh sửa điểm · UC12', 'cap-nhat.php' => 'Cập nhật kết quả chấm · UC13', 'thong-ke.php' => 'Thống kê kết quả chấm · UC14']
        : ['diem-thi.php' => 'Công bố điểm thi · UC15', 'tuyen-sinh.php' => 'Công bố kết quả tuyển sinh · UC22'];
    echo '<nav class="grading-tabs" aria-label="Chức năng">';
    foreach ($tabs as $file => $label) {
        $on = $file === $current;
        echo '<a href="' . e(url($module . '/' . $file)) . '"' . ($on ? ' class="is-active" aria-current="page"' : '') . '>' . e($label) . '</a>';
    }
    echo '</nav>';
}

/** Hộp thông báo: success | error | warning | info. */
function grading_notice(string $type, string $message, array $details = []): void {
    $icon = ['success' => '✓', 'error' => '!', 'warning' => '!', 'info' => 'ⓘ'][$type] ?? 'ⓘ';
    echo '<div class="grading-notice grading-notice-' . e($type) . '" role="' . ($type === 'error' ? 'alert' : 'status') . '">'
       . '<span class="grading-notice-icon" aria-hidden="true">' . $icon . '</span><div><strong>' . e($message) . '</strong>';
    if ($details) {
        echo '<ul>';
        foreach ($details as $d) echo '<li>' . e($d) . '</li>';
        echo '</ul>';
    }
    echo '</div></div>';
}

function grading_flashes(): void {
    foreach (flash_take() as $f) grading_notice($f['type'], $f['message']);
}

/** Ô chọn kỳ thi / môn thi. */
function grading_select(string $name, string $label, array $items, string $keyCol, string $labelCol, string $selected, string $placeholder): void {
    echo '<div class="field"><label for="' . e($name) . '">' . e($label) . ' <span>*</span></label><select id="' . e($name) . '" name="' . e($name) . '">';
    echo '<option value="">' . e($placeholder) . '</option>';
    foreach ($items as $it) {
        $v = (string)$it[$keyCol];
        echo '<option value="' . e($v) . '"' . ($v === $selected ? ' selected' : '') . '>' . e($it[$labelCol]) . '</option>';
    }
    echo '</select></div>';
}

function grading_status_badge(?string $value): string {
    if ($value === null || $value === '') return '<span class="status status-pending">Chưa có điểm</span>';
    $ok = str_starts_with(ui_lower($value), 'đã');
    return '<span class="status ' . ($ok ? 'status-success' : 'status-pending') . '">' . e($value) . '</span>';
}

function grading_fmt_time(?string $dt): string {
    if (!$dt) return '—';
    $t = strtotime($dt);
    return $t ? date('d/m/Y H:i', $t) : e($dt);
}

/** Chuyển hướng sau POST (mẫu POST → Redirect → GET). */
function grading_redirect(string $path, array $query = []): never {
    header('Location: ' . url($path) . ($query ? '?' . http_build_query($query) : ''), true, 303);
    exit;
}

function grading_input(string $key): string {
    $v = $_POST[$key] ?? $_GET[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}
