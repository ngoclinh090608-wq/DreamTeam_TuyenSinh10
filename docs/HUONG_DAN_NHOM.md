# Hướng dẫn làm việc nhóm — Dream Team

Mỗi người code phần UC của mình trên **nhánh riêng**, xong thì gửi **Pull Request (PR)** để trưởng nhóm (Ngọc Linh) duyệt và gộp vào `main`.
Nhánh `main` đã khóa: không ai đẩy thẳng vào `main` được.

---

## 1. Phân công

| Thành viên | Nhánh | UC | Thư mục làm việc |
|---|---|---|---|
| Nguyễn Quốc Thiên | `thien` | UC1 Xem thông tin tuyển sinh · UC2 Quản lý nguyện vọng · UC3 Cấp số báo danh · UC4 Tra cứu kết quả xét tuyển | `admission-info/`, `aspirations/`, `candidates/`, `results/` |
| Nguyễn Thị Thúy Vy | `vy` | UC5 Nhập điểm thi · UC6 Tạo thông tin phòng thi · UC7 Phân phòng thi · UC8 Xem phân công ra đề | `exam/` (UC5 có thể đặt `grading/nhap-diem.php`) |
| Trần Thị Cẩm Vân | `van-tran` | UC9 Tra cứu lô bài thi · UC10 Quản lý Hội đồng thi · UC11 Phân công coi thi — **đã có code**, kiểm tra và chỉnh tiếp | `papers/`, `councils/`, `assignments/` |
| Nguyễn Thị Ngọc Linh | `linh` | UC12–UC15 Chấm thi, thống kê, công bố điểm — **đã có code** | `grading/`, `publication/diem-thi.php` |
| Tô Nguyễn Khánh Duy | `duy` | UC16 Quản lý kết quả phúc khảo · UC17 Xét tuyển NV1–NV3 · UC18 Gửi yêu cầu phúc khảo | `appeals/`, `admission/` |
| Nguyễn Thị Cẩm Vân | `van-nguyen` | UC19 Xem tình trạng nhập học · UC20 Cập nhật tình trạng nhập học · UC21 Thống kê tuyển sinh · UC22 Công bố kết quả tuyển sinh | `enrollment/`, `statistics/`, `publication/tuyen-sinh.php` |

Đặc tả (basic / alternative / exception flow và **câu thông báo nguyên văn**) lấy theo **báo cáo giữa kỳ** `BaoCaoGK_NhomDreamTeam`. Tên bảng, cột theo `database/README.md`.

---

## 2. Chuẩn bị

Làm theo phần XAMPP trong [README chính](../README.md) và [hướng dẫn demo](../database/demo/README.md). Cài Git, clone repository và chuyển sang đúng nhánh của mình. Không nhập bộ seed kiểm thử vào database demo đang dùng.

Kiểm tra nhánh hiện tại bằng `git branch --show-current`. Nếu có thay đổi chưa commit, lưu lại trước khi chuyển nhánh hoặc đồng bộ; không dùng reset/force-push để bỏ qua xung đột.

---

## 3. Mỗi lần ngồi code

```bash
git switch ten-nhanh-cua-ban      # chắc chắn đang ở nhánh của mình
git pull origin ten-nhanh-cua-ban  # lấy thay đổi mới của nhánh mình
git fetch origin
git merge origin/main             # nhận các thay đổi chung đã được duyệt
```
… code và chạy thử trên localhost …
```bash
git add -A
git commit -m "UC02: kiểm tra trùng thứ tự nguyện vọng"
git push
```

Commit nhỏ, thường xuyên; message ghi **mã UC + việc đã làm**.

---

## 4. Gửi Pull Request

1. Sau khi `git push`, vào trang repo trên GitHub → bấm **Compare & pull request** (hoặc tab **Pull requests → New pull request**, chọn `base: main` ← `compare: nhánh của bạn`).
2. Tiêu đề: `UC1–UC4: ...`. Mô tả: đã làm gì, đã test những trường hợp nào.
3. Bấm **Create pull request**. Trưởng nhóm xem tab **Files changed** rồi **Merge**.
4. Nếu được góp ý: sửa tiếp trên nhánh của mình, `git push` lại — PR tự cập nhật.

---

## 5. CI — kiểm tra tự động trên GitHub

Mỗi lần `git push` và mỗi Pull Request vào `main`, GitHub Actions tự chạy (tab **Actions**, file `.github/workflows/ci.yml`):

1. Kiểm tra cú pháp tất cả file PHP.
2. Kiểm tra `database/install.sql` đã được build lại (`php database/build.php`) sau khi sửa file trong `database/`.
3. Nhập `install.sql` vào MariaDB sạch — SQL hỏng sẽ báo lỗi ở đây.
4. Chạy web và các bài kiểm thử hiện có: URL, điều hướng/tải lô bài UC05, đọc/import Excel, phân phòng ABC UC07, UC05–UC08 và UC09–UC15. Danh sách lệnh chính xác nằm trong `.github/workflows/ci.yml`.

Trong Pull Request sẽ thấy ✅ (qua) hoặc ❌ (lỗi). **Chỉ gộp khi ✅.** Nếu ❌: bấm **Details** để xem bước nào lỗi, sửa trên nhánh của mình rồi `git push` lại.

Các bài kiểm thử cần bộ seed nhỏ nguyên gốc trong database **kiểm thử riêng**, không chạy trên demo đang làm việc. Đặt DB_NAME cùng các biến kết nối cho cả PHP server và tiến trình kiểm thử; đặt BASE_URL trỏ tới server đó. Ví dụ Git Bash cho bộ nhỏ mặc định:

```bash
DB_NAME=dreamteam_tuyensinh10 php -S 127.0.0.1:8080 router.php
# Terminal khác, cùng cấu hình database:
DB_NAME=dreamteam_tuyensinh10 BASE_URL=http://127.0.0.1:8080 bash tests/kiem_thu_uc09_15.sh
```

Các script có thao tác ghi, tạo tình huống lỗi và cơ chế khôi phục riêng; vẫn phải dùng database kiểm thử tách biệt. Không dựa vào sao lưu của test để bảo vệ dữ liệu đang làm việc.

---

## 6. Quy ước code (để cả dự án giống nhau)

Xem code UC9–UC15 làm mẫu (`papers/`, `councils/`, `assignments/`, `grading/`).

- **Logic trong `services/`** (một file cho phần của mình, vd `services/aspiration_service.php`). Trang giao diện chỉ gọi hàm và hiển thị.
- **Tên hàm** theo class diagram Tuần 07 (vd `timLoBaiThi`, `luuThongTinPhanCong`).
- **Giao diện:** `require_once dirname(__DIR__) . '/includes/ui.php';` rồi dùng `ui_page_start(...)`, `ui_notice(...)`, `ui_select(...)`, `ui_page_end()`. Không viết header/sidebar riêng. Class CSS dùng sẵn tiền tố `ui-` (chữ to); class mới đặt tiền tố module của mình.
- **Form ghi dữ liệu dùng POST + CSRF:** trong form có `<?= csrf_field() ?>`, đầu trang kiểm tra `ui_csrf_error()`.
- **Ghi nhiều bảng phải dùng transaction** (`beginTransaction` / `commit` / `rollBack`), lỗi thì dữ liệu cũ giữ nguyên.
- **Câu thông báo đúng nguyên văn báo cáo**, khai báo thành hằng số đầu file service.
- **Không** đưa CCCD, mật khẩu, dữ liệu học sinh thật vào repo.

### Sửa database
- Sửa/thêm file trong `database/tables/`, `views/`, `functions/`, `procedures/` hoặc `seed/` (đặt tên theo số mục trong báo cáo, vd `procedures/35_sp_XetTuyenNguyenVong.sql`).
- Chạy `php database/build.php` (hoặc `C:\xampp\php\php database\build.php`) để tạo lại `install.sql`, commit cả file đó.

### File dùng chung — báo nhóm trước khi sửa
`includes/` (header, footer, sidebar, ui.php), `assets/css/style.css`, `README.md`, `database/install.sql`, `database/seed/seed.sql`.
Hai người cùng sửa một chỗ sẽ bị **conflict** khi gộp.

---

## 7. Gặp lỗi thường gặp

| Hiện tượng | Cách xử lý |
|---|---|
| `git push` báo **protected branch** / bị từ chối | Bạn đang ở `main`. Chạy `git switch ten-nhanh-cua-ban` rồi push lại. |
| Lỡ commit trên `main` ở máy mình | `git switch -c tam` (giữ commit sang nhánh tạm) → `git switch ten-nhanh-cua-ban` → `git merge tam` → `git branch -D tam`. Hoặc nhắn trưởng nhóm. |
| `git pull origin main` báo **CONFLICT** | Mở file bị báo, tìm đoạn `<<<<<<<` … `>>>>>>>`, giữ phần đúng, xóa các dấu đó → `git add -A` → `git commit`. Không chắc thì hỏi nhóm. |
| Trang trắng / lỗi CSDL | Kiểm tra Apache/MySQL, PHP error log, DB_* và config/database.local.php. Không tự xóa hoặc nhập lại database đang dùng. |
| Cần bộ dữ liệu sạch để kiểm thử | Chuẩn bị database kiểm thử riêng và sao lưu trước; xem database/README.md. Không reset demo đang dùng. |

## 8. Quy tắc giao diện và tài khoản

- Dùng header, footer, sidebar chung; không tạo phiên bản riêng cho từng thành viên. Giữ bố cục công khai và quản trị hiện hành.
- Dùng CSS/JS có sẵn; class mới đặt theo module. Không bỏ xác thực/phân quyền để xem trước trang quản trị.
- Không hiển thị số liệu giả để thay lỗi kết nối database. Trường THCS của học sinh và THPT nhận nguyện vọng là hai danh mục khác nhau.
- Các tài khoản/mật khẩu khởi tạo ghi trong [bảng tài khoản riêng](TAI_KHOAN.md). Dùng chức năng Đổi mật khẩu nếu còn đăng nhập được; khi quên mật khẩu, nhờ người quản lý database xử lý đúng tài khoản sau khi sao lưu, không nhập lại toàn bộ dữ liệu.
