# Mật khẩu tài khoản demo

Mật khẩu chung của 11 tài khoản mẫu là **`dreamteam2026`**:

`quantri`, `bantuyensinh`, `hdtuyensinh`, `hdcoithi`, `hdchamthi`, `catphach`, `hdphuckhao`, `hdrade`, `gvcn`, `SBD0001`, `SBD0002`.

- Database mới: nhập `database/install.sql` như bình thường; seed đã chứa mật khẩu mới dưới dạng bcrypt.
- Database đã có trên XAMPP: cập nhật code, mở phpMyAdmin, chọn database `dreamteam_tuyensinh10`, tab Nhập (Import), chọn `database/update_demo_passwords.sql` và thực hiện. Sau đó đăng xuất/đăng nhập lại bằng `dreamteam2026`.
- Script chỉ cập nhật đúng cặp mã/tên của 11 tài khoản mẫu và bỏ khóa tạm do đăng nhập sai; không xóa dữ liệu, không thay đổi vai trò hoặc trạng thái khóa tài khoản.
- Các nhánh giao diện nền cũ chưa có bảng `TaiKhoan`/đăng nhập thật thì không dùng script này.

Đây là mật khẩu công khai dành cho dữ liệu thực hành. Quy tắc đổi mật khẩu trong tài khoản (ít nhất 8 ký tự), CSRF và phân quyền vẫn giữ nguyên.

## Khôi phục mật khẩu ban đầu

Nếu database của bạn chưa từng đổi sang `1234`, không cần chạy thêm SQL. Nếu đã đổi, lấy phiên bản mới nhất của `database/update_demo_passwords.sql` và nhập bằng phpMyAdmin để khôi phục về `dreamteam2026`. Cập nhật code trên GitHub không tự thay đổi database XAMPP trên máy bạn.
