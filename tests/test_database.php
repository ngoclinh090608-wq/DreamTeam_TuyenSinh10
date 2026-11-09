<?php
declare(strict_types=1);
/** Fail closed before test writes. Reserved application databases are never test targets. */
require_once dirname(__DIR__) . '/config/database.php';
function require_test_database(): void {
    $name = getenv('DB_NAME') ?: '';
    if (!preg_match('/\Aqa_dt_[a-z0-9_]+\z/D', $name) || db()->query('SELECT DATABASE()')->fetchColumn() !== $name) {
        throw new RuntimeException('Test ghi dữ liệu chỉ chạy trên database riêng có tên qa_dt_*; kiểm tra DB_NAME của cả web và test.');
    }
}
