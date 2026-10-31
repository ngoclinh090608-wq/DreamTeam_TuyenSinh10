<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth.php';
$account = require_module('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once dirname(__DIR__) . '/services/student_profile.php';
    if (!has_role($account, ['HOC_SINH']) || !csrf_verify()) {
        http_response_code(403);
        flash_set('error', 'Yêu cầu không hợp lệ. Vui lòng tải lại trang và thử lại.');
    } else {
        try {
            student_profile_save($account, $_POST, isset($_FILES['anhHoSo']) && is_array($_FILES['anhHoSo']) ? $_FILES['anhHoSo'] : null);
            flash_set('success', 'Đã lưu thông tin hồ sơ bổ sung.');
        } catch (BusinessException $e) {
            flash_set('error', $e->getMessage());
        } catch (PDOException $e) {
            error_log('student profile save: '.$e->getCode());
            flash_set('error', $e->getCode() === '23000' ? 'Số định danh đã được sử dụng hoặc dữ liệu liên kết không hợp lệ.' : 'Không thể lưu hồ sơ lúc này. Dữ liệu trước đó được giữ nguyên.');
        } catch (Throwable $e) {
            error_log('student profile save failed: '.get_class($e));
            flash_set('error', 'Không thể lưu hồ sơ lúc này. Vui lòng thử lại.');
        }
    }
    header('Location: '.url('admin/'), true, 303);
    exit;
}
render_module('admin');
