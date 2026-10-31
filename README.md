# DreamTeam_TuyenSinh10

Ứng dụng PHP/MySQL–MariaDB phục vụ đồ án quản lý tuyển sinh lớp 10. Dự án có đăng nhập, phân quyền, đọc dữ liệu thật và các nghiệp vụ ghi database đã triển khai. Dữ liệu kèm theo là mô phỏng; không dùng nhận hồ sơ tuyển sinh thật.

## Chạy trên XAMPP

1. Đặt dự án tại `C:\xampp\htdocs\DreamTeam_TuyenSinh10`, bật Apache và MySQL.
2. Máy mới: nhập **`database/demo/dreamteam_tuyensinh10_demo.sql`** bằng phpMyAdmin vào database demo trống. Xem [hướng dẫn dữ liệu demo](database/demo/README.md).
3. Máy đã có dữ liệu: không nhập lại bản SQL đầy đủ hoặc reset database chỉ vì vừa pull code. Với biểu mẫu hồ sơ học sinh mới, xem migration bổ sung trong hướng dẫn demo.
4. Nếu cần cấu hình riêng, sao chép `database/demo/database.local.example.php` thành `config/database.local.php`; giữ thông tin kết nối cá nhân. File local được Git bỏ qua.
5. Mở `http://localhost/DreamTeam_TuyenSinh10/`, đăng nhập rồi vào bảng điều khiển.

PHP đọc `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` từ môi trường trước, rồi cấu hình local. Mặc định là `127.0.0.1:3306`, database `dreamteam_tuyensinh10_demo`, user `root`, mật khẩu rỗng. File `.env.example` chỉ là mẫu; ứng dụng không tự nạp `.env`.

Nếu chạy bằng PHP CLI:

```bash
php -S 127.0.0.1:8080 router.php
```

Dùng PHP 8.2 với `pdo_mysql`, `mbstring`, `Phar`, `SimpleXML`; thao tác Excel cần các extension này. Không cần Composer hoặc npm để chạy website. CI dùng MariaDB 10.4.

## Dữ liệu và tài khoản

Bản SQL demo hiện hành có **1.750 học sinh, 5.250 nguyện vọng, 5.250 bài thi, 4.870 kết quả chấm**, một kỳ thi `KTDEMO26` tên “Kỳ thi tuyển sinh lớp 10 năm 2026”. Có 380 bài chưa chấm; không có lịch sử sửa điểm giả. Snapshot cũng kèm hồ sơ bổ sung giả lập (liên hệ, gia đình, học bạ) cho tất cả học sinh; số liệu có thể thay đổi sau khi người dùng thao tác.

Năm THPT: Sao Mai, Bình Minh, Hòa An, Ánh Dương, Trần Quốc Khải. Dashboard thống kê nguyện vọng theo THPT và học sinh theo THCS từ database đang kết nối; khi không tải được dữ liệu, hiển thị thông báo lỗi.

Danh sách tài khoản và mật khẩu khởi tạo nằm trong [bảng tài khoản riêng](docs/TAI_KHOAN.md), không hiển thị trên website. Pull code không đặt lại mật khẩu.

## Các màn hình chính

| Phần | Đường dẫn | Trạng thái |
|---|---|---|
| Đăng nhập, đổi mật khẩu | `auth/` | Session, CSRF và phân quyền |
| Bảng điều khiển | `admin/` | Số liệu và biểu đồ từ database |
| Hồ sơ học sinh | `admin/` (vai trò học sinh) | Lưu liên hệ, ảnh, gia đình, học bạ cá nhân; ba tab Hồ sơ/Hướng dẫn/Văn bản |
| Thí sinh, nguyện vọng | `candidates/`, `aspirations/` | Tra cứu, bộ lọc, phân trang; học sinh chỉ xem nguyện vọng của mình |
| UC05 nhập điểm | `grading/nhap-diem.php` | Chọn lô bài, tải/import Excel, kiểm tra và lưu điểm |
| UC06–UC08 tổ chức thi | `exam/` | Tạo phòng, phân phòng, xem phân công ra đề |
| UC09–UC11 | `papers/`, `councils/`, `assignments/` | Tra cứu lô bài, quản lý hội đồng, phân công coi thi |
| UC12–UC14 | `grading/` | Lịch sử sửa điểm, cập nhật điểm, thống kê |
| UC15 | `publication/diem-thi.php` | Công bố điểm theo điều kiện nghiệp vụ |

Các module còn lại có mức độ hoàn thiện khác nhau; không coi toàn bộ chức năng trong menu là nghiệp vụ đã hoàn chỉnh. Trang `results/` thông báo trạng thái tra cứu trực tuyến chưa mở; không trả thẻ điểm cố định hoặc kết quả giả.

## Kiểm thử và phát triển

- [Hướng dẫn nhóm và phân công](docs/HUONG_DAN_NHOM.md).
- [Schema, build SQL và bộ dữ liệu kiểm thử](database/README.md).
- [Cài và kiểm tra dữ liệu demo](database/demo/README.md).

`database/install.sql` chứa bộ nhỏ 20 học sinh để kiểm thử; **không nhập chồng lên demo lớn**. CI kiểm tra cú pháp, SQL sinh lại, đường dẫn, Excel, phân phòng và UC05–UC15 trên database riêng. Kết quả mỗi lần chạy xem tại GitHub Actions.

Giữ logic nghiệp vụ trong `services/`, dùng layout `includes/` và CSS/JavaScript trong `assets/`. Không commit cấu hình chứa mật khẩu, dữ liệu học sinh thật hoặc file session/log. Tài liệu báo cáo môn học nằm trong `docs/tai-lieu-tham-khao/`; nguồn tài nguyên hình ảnh ghi tại `assets/images/SOURCES.md`.

### UC06–UC07: tạo phòng theo điểm thi được chọn

Chọn kỳ thi và điểm thi, sau đó chọn đúng một mục ở **Điểm thi cần tạo phòng** và bấm **Tạo phòng cho điểm thi đã chọn**. Hệ thống chỉ tạo đủ phòng còn thiếu tại điểm đó, mỗi phòng mới 30 chỗ. Không tạo đồng loạt cho các trường hoặc điểm thi khác. Nếu trường chưa có điểm thi, chọn mục **Tạo điểm thi mới** của trường đó trong danh sách; mỗi lần cũng chỉ tạo một điểm.

UC07 lấy thí sinh đăng ký NV1 đúng trường, xếp ABC theo tên gọi, họ và tên đệm; đầy phòng trước mới dùng phòng sau. Mã phòng mới: THPT_5 → P501, P502…; THPT_4 → P401, P402… Mã đã dùng được bỏ qua. Bấm tạo phòng tại điểm đã chọn cũng đổi mã tự sinh dài cũ R…_0001 sang dạng mới, giữ liên kết thí sinh, lô bài và phân công coi thi trong cùng transaction. Không xóa bài thi hoặc điểm cũ.
