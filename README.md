# DreamTeam_TuyenSinh10 — Giao diện thống nhất (Tuần 09)

Bộ giao diện PHP dành cho **Dream Team**, phát triển dựa trên bố cục tham khảo của 3 trang tuyển sinh lớp 10 Cần Thơ (trang chủ, đăng nhập, tra cứu), nhưng **không sao chép backend, dữ liệu trường hay thông tin chính thức của Cần Thơ**.

> **Quan trọng:** Đây là **GIAO DIỆN MẪU**, chưa có nghiệp vụ tuyển sinh thật, chưa nối database, chưa có đăng nhập thực tế. Tất cả dữ liệu hiển thị chỉ nhằm thống nhất UI; thao tác thêm/sửa/xóa/đăng nhập không ghi thông tin. Không dùng để nhận hồ sơ thật.

## 1. Cách chạy (XAMPP — Windows)

1. Giải nén ZIP. Trong ZIP có thư mục **`DreamTeam_TuyenSinh10`**.
2. Chép thư mục đó vào thư mục `htdocs` của XAMPP mà bạn đang sử dụng. **Không cần đổi cấu hình hoặc đụng tới Docker, ổ đĩa hay dự án khác**.
3. Mở XAMPP Control Panel, nhấn **Start** tại mục **Apache** (bật thêm **MySQL** nếu muốn xem dữ liệu từ database, xem mục 5).
4. Vào `http://localhost/DreamTeam_TuyenSinh10/`.
5. Thử các trang:
    - `/` — Trang chủ
    - `/auth/login.php` — Trang đăng nhập mẫu, 2 tab
    - `/results/` — Tra cứu mẫu (`DEMO001`)
    - `/admin/` — Dashboard quản trị
    - `/assignments/` — UC11 Phân công coi thi (giao diện mẫu)
    - `/councils/` — UC10 Hội đồng thi
    - `/papers/` — UC09 Lô bài thi

Để chạy bằng PHP thay vì Apache (nếu máy có PHP trong PATH):

```bash
php -S 127.0.0.1:8080 router.php
```

Sau đó mở `http://127.0.0.1:8080/`.

## 2. Phần nào đã làm xong?

- Trang chủ: hero, lịch tuyển sinh, chỉ tiêu trường, bộ lọc trường, hướng dẫn, footer.
- Đăng nhập: tab học sinh / quản trị, giao diện form và hiển thị mật khẩu, không kiểm tra tài khoản.
- Tra cứu: form nhận SBD/CCCD, **chỉ mã giả lập `DEMO001`** hiển thị một kết quả mẫu.
- Layout trang nội bộ: sidebar, thanh điều hướng, thẻ số liệu, bộ lọc bảng, bảng dữ liệu, mẫu biểu mẫu UC11.
- 16 module dashboard (xem danh sách theo tên thư mục), chia sẻ **`includes/header.php`**, **`includes/footer.php`**, **`includes/sidebar.php`**, **`assets/css/style.css`**, **`assets/js/app.js`**.
- Giao diện co giãn trên điện thoại/máy tính.
- `.htaccess` và `router.php` giúp cấm tải trực tiếp thư mục nội bộ (cần kiểm tra lại khi chuyển lên hosting thật).

## 3. Phân chia phần code cho nhóm

| Thành viên | Thư mục | Chức năng demo gợi ý |
|---|---|---|
| Nguyễn Quốc Thiên | `aspirations/`, `candidates/`, `admission-info/` | UC02 Nguyện vọng |
| Nguyễn Thị Thúy Vy | `exam/` | UC07 Phân phòng thi |
| Trần Thị Cẩm Vân | `papers/`, `councils/`, `assignments/`, `staff/` | UC11 Phân công coi thi |
| Nguyễn Thị Ngọc Linh | `grading/`, `publication/` | UC13 Chấm thi |
| Tô Nguyễn Khánh Duy | `appeals/`, `admission/` | UC17 Xét tuyển NV |
| Nguyễn Thị Cẩm Vân | `enrollment/`, `statistics/`, `publication/` | UC20 Nhập học |

Chỉ là cách ghép nhóm chức năng. Tên, thứ tự và nghiệp vụ phải rà soát theo **báo cáo tuần 08 bản `(1)`** đã chốt.

## 4. Quy tắc thống nhất giao diện

1. Mỗi module đặt tại `ten-module/index.php`.
2. Dùng header/footer chung, không viết header/sidebar riêng cho từng thành viên.
3. Giao diện xem dữ liệu gọi `render_module('ten-module')` trong `includes/functions.php`.
4. Khi làm chức năng thật, thay phần dữ liệu giả trong `includes/module_config.php` và `includes/sample_data.php` bằng SQL/Service **mà vẫn giữ layout**.
5. Logic PHP viết trong `services/` để trang giao diện đơn giản, dễ test.
6. Dùng CSS `assets/css/style.css`, thêm class mới có tiền tố module khi cần.
7. Không đưa CCCD, mật khẩu hay danh sách học sinh thật lên GitHub.

## 5. Database (MySQL / MariaDB) — đã có

Nhóm dùng **MySQL/MariaDB** (XAMPP). Schema bám theo báo cáo Tuần 08 `(1)`: 30 bảng, giữ nguyên tên bảng và cột của nhóm (xem `database/README.md`).

Cài đặt:
1. Bật **Apache** và **MySQL** trong XAMPP.
2. Mở `http://localhost/phpmyadmin` → tab **Nhập (Import)** → chọn `database/install.sql` → Thực hiện (tự tạo database `dreamteam_tuyensinh10`, 30 bảng, dữ liệu mẫu).
3. Mở lại `http://localhost/DreamTeam_TuyenSinh10/admin/`: các thẻ số liệu và bảng đã đọc từ database. Nếu chưa nhập DB, giao diện tự dùng dữ liệu giả như cũ.

Các trang danh sách đọc dữ liệu qua `services/live_data.php` (chỉ đọc, giữ nguyên layout). Khi làm chức năng thật, thay truy vấn của module mình bằng service riêng trong `services/`. Sau khi sửa file trong `database/tables/` hoặc `database/seed/`, chạy `php database/build.php` để tạo lại `install.sql`.

## 6. Bảo mật / demo

- `DEMO_MODE` mặc định chỉ **hiển thị giao diện và dữ liệu giả**, không đồng nghĩa đã có cơ chế bảo mật đăng nhập.
- Nếu chạy host công khai, **chưa được** dùng bộ khung này để lưu dữ liệu cá nhân hoặc cho phép ghi DB.
- `storage/` và `.env` bị loại trừ khỏi Git qua `.gitignore`. Không đặt mật khẩu DB trong file commit.
- Với Apache `.htaccess` bảo vệ `config`, `database`, `includes`, `services`, `storage`, `docs`, `tests`. PHP built-in server dùng `router.php` để chặn tương tự.
- Header, footer và logo là minh họa mới cho Dream Team, không dùng tên và logo cổng tuyển sinh chính thức.

## 7. Cấu trúc

```
DreamTeam_TuyenSinh10/
├── index.php, .htaccess, .gitignore, README.md, router.php
├── config/             app.php, database.php
├── includes/           functions.php, auth.php, permissions.php, header.php, footer.php, sidebar.php,
│                       module_config.php, module_page.php, sample_data.php
├── auth/               login.php
├── admin/              index.php
├── candidates/         index.php
├── schools/            index.php
├── admission-info/     index.php
├── aspirations/        index.php
├── results/            index.php
├── exam/               index.php
├── councils/           index.php
├── staff/              index.php
├── assignments/        index.php
├── papers/             index.php
├── grading/            index.php
├── appeals/            index.php
├── admission/          index.php
├── enrollment/         index.php
├── publication/        index.php
├── statistics/         index.php
├── services/           nơi viết logic nghiệp vụ thật
├── api/                API nếu sau này cần AJAX
├── assets/             css/style.css, js/app.js, images/logo.svg
├── database/           tables/, views/, procedures/, functions/, seed/
├── storage/            file tạm/log (không commit)
├── tests/              kiểm thử
└── docs/               sơ đồ, tài liệu, biên bản
```
