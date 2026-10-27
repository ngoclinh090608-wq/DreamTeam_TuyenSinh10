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

## 2. Chuẩn bị (làm một lần)

1. **Nhận lời mời** vào repo: mở email từ GitHub → **Accept invitation**.
2. Cài **XAMPP** (Apache + MySQL) và **Git** (https://git-scm.com).
3. Khai báo tên Git (dùng đúng email tài khoản GitHub của bạn):
   ```bash
   git config --global user.name "Họ Tên"
   git config --global user.email "email-github-cua-ban@gmail.com"
   ```
4. Mở cửa sổ lệnh trong `C:\xampp\htdocs` (File Explorer → vào thư mục → gõ `cmd` vào thanh địa chỉ → Enter), chạy:
   ```bash
   git clone https://github.com/ngoclinh090608-wq/DreamTeam_TuyenSinh10.git
   cd DreamTeam_TuyenSinh10
   git switch ten-nhanh-cua-ban
   ```
   (ví dụ `git switch thien`). Thư mục phải tên đúng `DreamTeam_TuyenSinh10`.
5. Bật **Apache** và **MySQL** trong XAMPP → mở http://localhost/phpmyadmin → **Nhập (Import)** → chọn `database/install.sql` → Thực hiện.
6. Mở http://localhost/DreamTeam_TuyenSinh10/ để kiểm tra. Thử trang mẫu đã làm: `/grading/`, `/councils/`.

Kiểm tra mình đang ở nhánh nào: `git branch` (dòng có dấu `*`).

---

## 3. Mỗi lần ngồi code

```bash
git switch ten-nhanh-cua-ban      # chắc chắn đang ở nhánh của mình
git pull origin main              # lấy code mới nhất đã gộp vào main
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
4. Chạy web và **kiểm thử chức năng** (`tests/kiem_thu_uc09_15.sh`, 98 ca cho UC9–UC15).

Trong Pull Request sẽ thấy ✅ (qua) hoặc ❌ (lỗi). **Chỉ gộp khi ✅.** Nếu ❌: bấm **Details** để xem bước nào lỗi, sửa trên nhánh của mình rồi `git push` lại.

Chạy bộ kiểm thử trên máy trước khi push (Git Bash, XAMPP đang bật, đã nhập `install.sql`):
```bash
bash tests/kiem_thu_uc09_15.sh
```
Script tự sao lưu và khôi phục dữ liệu nên không làm mất dữ liệu trên máy. Khi làm xong UC của mình, mỗi bạn nên viết thêm ca kiểm thử theo cùng mẫu (hàm `kiem`, `dung`) để CI kiểm tra luôn phần mình.

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
| Trang trắng / lỗi CSDL | Kiểm tra XAMPP đã bật MySQL; nhập lại `install.sql` (xóa database `dreamteam_tuyensinh10` trước nếu đã có). |
| Muốn dữ liệu mẫu về như ban đầu | phpMyAdmin → xóa database `dreamteam_tuyensinh10` → nhập lại `database/install.sql`. |
