# Ba bản dữ liệu hiện hành

Ba file SQL dưới đây là các bản xuất mới nhất do người dùng cung cấp. Bản demo cũ đã được thay thế tại cùng đường dẫn; không giữ thêm bản sao cũ trong repository.

| File | Database | Học sinh | Nguyện vọng | Bài thi | Kết quả |
|---|---|---:|---:|---:|---:|
| `dreamteam_tuyensinh10_demo.sql` | `dreamteam_tuyensinh10_demo` | 1.750 | 5.250 | 5.250 | 4.870 |
| `dreamteam_tuyensinh10.sql` | `dreamteam_tuyensinh10` | 20 | 54 | 54 | 51 |
| `dreamteam_tuyensinh10_test.sql` | `dreamteam_tuyensinh10_test` | 20 | 54 | 54 | 51 |

## Chọn và nhập dữ liệu

- Chạy website: dùng bản **demo**, có đầy đủ hồ sơ bổ sung, gia đình và học bạ. Mặc định ứng dụng kết nối database này.
- Hai bản nhỏ là snapshot riêng người dùng gửi, chưa có ba bảng hồ sơ bổ sung. Không dùng thay cho bộ kiểm thử CI: CI vẫn dùng `../install.sql`, được build từ schema hiện hành.
- Mỗi file tự chọn database riêng. Chỉ nhập vào database trống; không nhập ba file chồng lên nhau. Máy đã có dữ liệu đang làm việc không cần nhập lại sau khi pull code.
- Sao chép `database.local.example.php` sang `config/database.local.php` nếu cần thay host/user/password/name. Biến môi trường DB_* được ưu tiên; cấu hình local không đưa lên Git.

Bản demo có 11 tài khoản, 380 bài chưa chấm, 1.750 hồ sơ bổ sung, 5.250 dòng gia đình và 7.000 dòng học bạ. Giữ nguyên tên người nhập, thời gian, điểm, mật khẩu và nội dung các dòng dữ liệu trong file người dùng gửi. Chỉ chuẩn hóa tên bảng/view cho Windows và Linux, bỏ DEFINER gắn với máy xuất, thêm CREATE DATABASE/USE và thống nhất xuống dòng.

## Máy đã dùng bản demo cũ

Không nhập lại full dump để cập nhật máy đang có dữ liệu. Các migration trong `../migrations/` vẫn giữ để hỗ trợ máy cũ:

1. `20261031_student_profile.sql`: thêm ba bảng hồ sơ còn thiếu.
2. `20261031_fill_student_profiles.sql`: tùy chọn điền hồ sơ giả lập vào ô trống, không ghi đè nội dung đã nhập.
3. `20261101_demo_grading_author.sql`: chỉ thay nhãn người nhập tự động cũ bằng Phan Thùy Dương, người nhập giả lập của buổi demo; giữ nguyên thời gian và điểm.

Máy nhập snapshot demo mới đã có các nội dung trên, không cần chạy migration riêng. Ảnh học sinh nằm trong `storage/student-photos/` trên máy, không nằm trong SQL; sao lưu thư mục ảnh cùng database.

## Kiểm tra

`php database/demo/verify.php` kiểm tra chỉ đọc database demo theo host/user/password cấu hình, báo số liệu và liên kết dữ liệu. Không chạy test ghi dữ liệu trên demo đang dùng.

SHA-256 của ba file tải lên gốc:

- Demo: `937914ec18b56077ab80e1a42b9f0d7393cdd4c92d7fead690cc6e63174d01de`
- Bản nhỏ: `01d12c6d0754795e57ada1a7e2ae4beefd2c3e6c15ceec94ddf96e67af63e356`
- Test: `5d87a9fc98240a8301f008a51f74cc25d70fc15693475d00d2d7f88aea5e54e8`
