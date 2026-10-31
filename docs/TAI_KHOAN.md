# Bảng tài khoản dùng cho môi trường phát triển

Tài liệu riêng cho nhóm, không hiển thị trên trang đăng nhập. Đây là tài khoản của bộ SQL đi kèm dự án, không phải tài khoản vận hành thực tế. Mật khẩu dưới đây là mật khẩu khởi tạo công khai của bộ mô phỏng; database đã đổi mật khẩu phải dùng giá trị hiện tại, không nhập lại dữ liệu để đặt lại mật khẩu.

| Tài khoản | Mật khẩu khởi tạo | Vai trò |
|---|---|---|
| `quantri` | `dreamteam2026` | Quản trị hệ thống |
| `bantuyensinh` | `dreamteam2026` | Ban Tuyển sinh |
| `hdtuyensinh` | `dreamteam2026` | Hội đồng tuyển sinh |
| `hdcoithi` | `dreamteam2026` | Hội đồng coi thi |
| `hdchamthi` | `dreamteam2026` | Hội đồng chấm thi |
| `catphach` | `dreamteam2026` | Bộ phận cắt phách |
| `hdphuckhao` | `dreamteam2026` | Hội đồng phúc khảo |
| `hdrade` | `dreamteam2026` | Hội đồng ra đề |
| `gvcn` | `dreamteam2026` | Giáo viên chủ nhiệm lớp 9 |
| `SBD0001` | `dreamteam2026` | Học sinh |
| `SBD0002` | `dreamteam2026` | Học sinh |

Chức năng hiển thị theo phân quyền trong `includes/permissions.php`; tài liệu này không cấp thêm quyền. Mật khẩu được lưu dưới dạng băm trong bảng TaiKhoan. Chức năng Đổi mật khẩu, CSRF, khóa tạm và xác thực giữ nguyên. Không đưa mật khẩu cá nhân hoặc mật khẩu vận hành vào tài liệu Git.
