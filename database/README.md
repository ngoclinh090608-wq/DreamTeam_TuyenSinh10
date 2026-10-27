# Cơ sở dữ liệu Dream Team (MySQL / MariaDB)

Thiết kế theo báo cáo Tuần 08: 30 bảng, giữ nguyên tên bảng và tên cột của nhóm (camelCase).
Khác biệt khi chuyển từ kiểu SQL Server sang MySQL: NVARCHAR -> VARCHAR (utf8mb4), BIT -> TINYINT(1),
DATETIME2/SYSDATETIME() -> DATETIME/CURRENT_TIMESTAMP, NVARCHAR(MAX) -> TEXT, UNIQUE FILTERED -> UNIQUE (MySQL cho phép nhiều NULL).

## Cài đặt nhanh (XAMPP)
1. Bật Apache và MySQL trong XAMPP, mở http://localhost/phpmyadmin
2. Tab **Nhập (Import)** -> chọn `database/install.sql` -> Thực hiện.
   (File này tự tạo database `dreamteam_tuyensinh10`, 30 bảng và dữ liệu mẫu. Chạy lại sẽ KHÔNG xóa bảng cũ; muốn làm lại thì xóa database trước.)
3. Mở lại trang Tổng quan: các thẻ số liệu hiện số thật.

Kết nối PHP đọc biến môi trường DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS (mặc định 127.0.0.1:3306, dreamteam_tuyensinh10, root, không mật khẩu - đúng với XAMPP).

## Cấu trúc
- `tables/`  một file mỗi bảng, đánh số theo thứ tự khóa ngoại (001...030)
- `views/`, `functions/`, `procedures/`  dành cho 14 view, 18 function, 44 procedure của Tuần 8 (chưa viết)
- `seed/seed.sql`  dữ liệu mẫu mô phỏng
- `build.php`  gộp thành `install_schema.sql` (chỉ cấu trúc) và `install.sql` (cấu trúc + dữ liệu mẫu). Sau khi sửa file nào trong tables/ hoặc seed/ thì chạy `php database/build.php` rồi commit lại cả hai file install.

## Dữ liệu mẫu (để demo 6 chức năng)
20 thí sinh (18 đủ điều kiện, 1 chưa duyệt, 1 bị từ chối), 5 trường THPT với chỉ tiêu nhỏ (tổng 15 so với 18 thí sinh có nguyện vọng, nên xét tuyển có cạnh tranh), 4 phòng thi sức chứa 12,
phân phòng và phân công coi thi đủ 2 giám thị cho mỗi phòng/môn, 54 bài thi có số phách, 51 bài đã chấm (3 bài Toán chưa chấm),
điểm Toán và Văn đã công bố, Ngoại ngữ chưa công bố, 2 yêu cầu phúc khảo (1 đã xử lý, 1 chờ), 54 nguyện vọng NV1-NV3 ở trạng thái "Chưa xét".

## Chưa có / cần nhóm thống nhất
- Bảng Phach, KetQuaThongKe, BaoCaoThongKe đã bỏ (cô đồng ý), cần xóa khỏi mục lục báo cáo Tuần 8. Số phách lưu ở BaiThi.soPhach; thống kê (UC14, UC21) tính trực tiếp bằng view/truy vấn.
- Hệ số điểm từng môn, điểm ưu tiên/khuyến khích (BR12) chưa có cột nào lưu; cần bổ sung khi viết UC17.
- Bảng tài khoản đăng nhập chưa có trong thiết kế; hiện dùng chế độ DEMO.
