<?php
declare(strict_types=1);
// DEMO_MODE chỉ hiển thị dữ liệu giả lập / giao diện để trình bày.
// KHÔNG mặc định dùng DEMO_MODE như cơ chế bỏ qua phân quyền khi triển khai thật.
return [
    'name' => getenv('APP_NAME') ?: 'CỔNG THÔNG TIN TUYỂN SINH VÀO LỚP 10',
    'demo_mode' => filter_var(getenv('DEMO_MODE') ?: '1', FILTER_VALIDATE_BOOLEAN),
    'timezone' => 'Asia/Ho_Chi_Minh',
    'base_path' => getenv('APP_BASE_PATH') ?: null,
];
