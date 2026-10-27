<?php
declare(strict_types=1);
/**
 * Hàm hiển thị dùng chung cho các trang nghiệp vụ đã nối CSDL (vẫn dùng header/footer/sidebar chung).
 * CSS tương ứng: các class tiền tố `ui-` ở cuối assets/css/style.css (chữ to, dễ đọc).
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/services/csrf.php';

app_session_start();
require_auth();

/** Mở trang trong layout quản trị chung. $active là khóa module trên sidebar. */
function ui_page_start(string $active, string $title, string $eyebrow, string $desc, string $uc): void {
    $page_title = $title; $layout = 'dashboard';
    require __DIR__ . '/header.php';
    echo '<div class="ui-page">';
    echo '<div class="page-head"><div><div class="eyebrow"><span></span>' . e($eyebrow) . '</div><h1>' . e($title) . '</h1><p>' . e($desc) . '</p></div>'
       . '<div class="page-head-actions"><span class="uc-tag">' . e($uc) . '</span></div></div>';
}

function ui_page_end(): void {
    $layout = 'dashboard';
    echo '</div>';
    require __DIR__ . '/footer.php';
}

/** Hộp thông báo: success | error | warning | info. */
function ui_notice(string $type, string $message, array $details = []): void {
    $icon = ['success' => '✓', 'error' => '!', 'warning' => '!', 'info' => 'ⓘ'][$type] ?? 'ⓘ';
    echo '<div class="ui-notice ui-notice-' . e($type) . '" role="' . ($type === 'error' ? 'alert' : 'status') . '">'
       . '<span class="ui-notice-icon" aria-hidden="true">' . $icon . '</span><div><strong>' . e($message) . '</strong>';
    if ($details) {
        echo '<ul>';
        foreach ($details as $d) echo '<li>' . e($d) . '</li>';
        echo '</ul>';
    }
    echo '</div></div>';
}

function ui_flashes(): void {
    foreach (flash_take() as $f) ui_notice($f['type'], $f['message']);
}

/** Ô chọn (select) có dòng gợi ý đầu tiên. */
function ui_select(string $name, string $label, array $items, string $keyCol, string $labelCol, string $selected, string $placeholder, bool $required = true): void {
    echo '<div class="field"><label for="' . e($name) . '">' . e($label) . ($required ? ' <span>*</span>' : '') . '</label><select id="' . e($name) . '" name="' . e($name) . '">';
    echo '<option value="">' . e($placeholder) . '</option>';
    foreach ($items as $it) {
        $v = (string)$it[$keyCol];
        echo '<option value="' . e($v) . '"' . ($v === $selected ? ' selected' : '') . '>' . e($it[$labelCol]) . '</option>';
    }
    echo '</select></div>';
}

/** Nhãn trạng thái: chữ bắt đầu bằng “Đã/Đủ” hiện màu xanh, còn lại màu vàng. */
function ui_status_badge(?string $value, string $empty = 'Chưa có điểm'): string {
    if ($value === null || $value === '') return '<span class="status status-pending">' . e($empty) . '</span>';
    $v = ui_lower($value);
    $ok = str_starts_with($v, 'đã') || str_starts_with($v, 'đủ');
    return '<span class="status ' . ($ok ? 'status-success' : 'status-pending') . '">' . e($value) . '</span>';
}

function ui_fmt_time(?string $dt): string {
    if (!$dt) return '—';
    $t = strtotime($dt);
    return $t ? date('d/m/Y H:i', $t) : e($dt);
}

/** Chuyển hướng sau POST (mẫu POST → Redirect → GET). */
function ui_redirect(string $path, array $query = []): never {
    header('Location: ' . url($path) . ($query ? '?' . http_build_query($query) : ''), true, 303);
    exit;
}

/** Lấy tham số (POST ưu tiên, rồi GET), đã cắt khoảng trắng. */
function ui_input(string $key): string {
    $v = $_POST[$key] ?? $_GET[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}

/** Kiểm tra CSRF cho request POST; trả về thông báo lỗi hoặc null. */
function ui_csrf_error(): ?string {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return null;
    return csrf_verify() ? null : 'Phiên làm việc đã hết hạn hoặc yêu cầu không hợp lệ. Vui lòng thử lại.';
}
