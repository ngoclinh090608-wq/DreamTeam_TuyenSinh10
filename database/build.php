<?php
// Gộp các file trong tables/ (+ seed) thành install.sql để nhập một lần qua phpMyAdmin.
// Chạy: php database/build.php
$dir = __DIR__;
$sql = "-- install.sql được tạo tự động bởi build.php. Không sửa tay.\nCREATE DATABASE IF NOT EXISTS dreamteam_tuyensinh10 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nUSE dreamteam_tuyensinh10;\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n";
foreach (['tables', 'views', 'functions', 'procedures'] as $d) {
    $files = glob("$dir/$d/*.sql") ?: []; sort($files);
    foreach ($files as $f) $sql .= "\n" . file_get_contents($f);
}
$sql .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
file_put_contents("$dir/install.sql", $sql . "\n" . file_get_contents("$dir/seed/seed.sql"));
echo "Đã tạo install.sql (cấu trúc + dữ liệu mẫu kiểm thử)\n";
