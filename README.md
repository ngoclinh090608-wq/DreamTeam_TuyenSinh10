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
    - `/grading/nhap-diem.php` — UC05 Nhập điểm thi; `/exam/tao-phong.php`, `/exam/phan-phong.php`, `/exam/phan-cong-ra-de.php` — UC06–UC08 (**đã nối CSDL, ghi dữ liệu thật**)
    - `/papers/` — UC09 Tra cứu thông tin lô bài thi (**đã nối CSDL**)
    - `/councils/` — UC10 Quản lý Hội đồng thi (**đã nối CSDL, ghi dữ liệu thật**)
    - `/assignments/` — UC11 Phân công coi thi (**đã nối CSDL, ghi dữ liệu thật**)
    - `/grading/` — UC12 Lịch sử chỉnh sửa điểm, UC13 Cập nhật kết quả chấm, UC14 Thống kê (**đã nối CSDL, ghi dữ liệu thật**)
    - `/publication/diem-thi.php` — UC15 Công bố điểm thi (**đã nối CSDL, ghi dữ liệu thật**)

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

### UC05 – UC08: Nhập điểm, phòng thi, phân phòng, phân công ra đề (Nguyễn Thị Thúy Vy) — đã chạy với CSDL

Làm theo đặc tả và Test Case Tuần 04, tên hàm theo bảng Message – Phương thức Tuần 07. Cần nhập `database/install.sql` trước.

| UC | Trang | Tài khoản | Làm gì |
|---|---|---|---|
| UC05 Nhập điểm thi | `/grading/nhap-diem.php` | `hdchamthi` | Lọc kỳ thi, môn thi, phòng thi → chọn túi bài → tải Excel (Phòng thi, Số phách, Điểm) → người chấm điền điểm → import lại → kiểm tra → xác nhận lưu cả túi trong 1 transaction |
| UC06 Tạo thông tin phòng thi | `/exam/tao-phong.php` | `bantuyensinh` | Chọn kỳ thi, điểm thi → hệ thống tự sinh mã phòng, tên phòng, sức chứa → chỉnh nếu cần → Tạo phòng. Chặn trùng mã phòng (toàn hệ thống) và trùng tên phòng trong cùng điểm thi |
| UC07 Phân phòng thi cho thí sinh | `/exam/phan-phong.php` | `bantuyensinh` | Chọn kỳ thi, điểm thi → Xem trước → (đổi phòng từng thí sinh nếu cần) → Xác nhận lưu. Có Chuyển phòng / Hủy phân phòng từng thí sinh |
| UC08 Xem phân công ra đề thi | `/exam/phan-cong-ra-de.php` | `hdrade` | Chọn kỳ thi → danh sách môn được phân công → Xem chi tiết (kỳ thi, môn, thời gian thực hiện, nhiệm vụ) → Quay lại danh sách. Chỉ đọc |

**UC05 chỉ nhập điểm qua file Excel của túi bài thi** tại `/grading/nhap-diem.php`:

1. Lọc **Kỳ thi → Môn thi → Phòng thi**, bấm **Lọc túi bài**, rồi chọn túi bài cần chấm.
2. Bấm **Tải file túi bài (.xlsx)**. File chứa đúng ba cột **Phòng thi, Số phách, Điểm**; phòng thi và số phách được điền sẵn, điểm để trống. Chỉ xuất các bài chưa có điểm, không xuất thông tin thí sinh. Túi có bài chưa đánh phách phải hoàn tất đánh phách trước.
3. Người chấm điền cột **Điểm** trong Excel, giữ nguyên phòng thi / số phách và tiêu đề, lưu `.xlsx` rồi **Import và kiểm tra file** trở lại đúng túi đã chọn.
4. Xem lỗi từng dòng; nếu hợp lệ thì **Xác nhận lưu điểm**. Không có nhập tay trên web. Bài đã có điểm chỉ chỉnh sửa tại UC13.

Tối đa 500 bài / 2 MB mỗi file; không dùng `.xls` hoặc công thức. Điểm theo thang môn, bước 0.25. Hệ thống kiểm tra đúng kỳ / môn / phòng / túi, số phách, trùng bài và điểm trước khi lưu. Khi xác nhận, kiểm tra lại dữ liệu và lưu toàn bộ trong một transaction; lỗi sẽ không lưu điểm nào. Bản xem trước hết hạn sau 15 phút.

Đọc/ghi `.xlsx` dùng phần mở rộng PHP `Phar` và `SimpleXML` (ngoài `pdo_mysql`, `mbstring`), không cần Composer. Kiểm thử: `php tests/kiem_thu_uc05_excel.php` trên dữ liệu mẫu gốc, và `BASE_URL=http://127.0.0.1:8080 bash tests/kiem_thu_uc05_08.sh`.

Quy tắc UC07: thí sinh thi tại **trường đăng ký nguyện vọng 1 trong năm tuyển sinh của kỳ thi**, không phân theo khu vực trường THCS. Điểm thi phải gắn rõ `DiemThi.maTruongTHPT`; chưa gắn trường thì báo thiếu cấu hình, không tự đoán trường. Chỉ lấy hồ sơ đủ điều kiện và đã duyệt. Thí sinh đang ở điểm thi sai trường có thể được phân lại về đúng trường NV1; người đã ở điểm thi khác của cùng trường sẽ không bị kéo sang.

Hệ thống xếp ABC tiếng Việt theo **tên gọi → họ và tên đệm → mã thí sinh khi trùng tên**, rồi **xếp đầy phòng trước mới dùng phòng tiếp theo**; phòng cuối có thể không đầy, các phòng sau còn trống sẽ chưa được dùng. Không đổi phòng tùy ý trong bảng xem trước. Khi lưu, server tính lại kết quả, kiểm tra đúng NV1, đủ danh sách và sức chứa trong transaction; kết quả đã thay đổi phải xem trước lại. Chuyển phòng cũng chỉ được sang phòng thuộc đúng trường NV1 và cùng kỳ thi.

Dữ liệu mẫu cũ có `DT01` đặt tại THCS và chưa gắn trường THPT nên UC07 sẽ báo thiếu cấu hình tại điểm này. `DT02` gắn `THPT_1`; các trường NV1 khác cần có điểm thi và phòng tương ứng trước khi phân. Kiểm thử tự tạo liên kết trường / nguyện vọng phù hợp rồi khôi phục, không sửa dữ liệu mẫu gốc. Các thí sinh đang ở điểm thi nhưng không thuộc danh sách NV1 hợp lệ được cảnh báo và vẫn chiếm chỗ cho đến khi chuyển/hủy.


Quy tắc UC08: tài khoản Hội đồng ra đề chỉ xem kỳ thi của Hội đồng ra đề (`HoiDongThi.loaiHoiDong = 'Ra đề'`) mà cán bộ của tài khoản là thành viên; kỳ thi khác báo “Không đủ quyền truy cập.” Ban Tuyển sinh xem được mọi kỳ thi.

Thử nhanh:

| Muốn thử | Làm |
|---|---|
| UC05 nhập điểm thành công | Môn Toán → phòng `P202` → túi `LO_P202_TOAN` → tải Excel → điền điểm 3 bài → import → xác nhận |
| UC05 “Điểm thi không hợp lệ.” | Điểm `8.3` (sai bước 0.25), `11`, để trống hoặc `abc` |
| UC05 “Không có bài thi cần nhập điểm.” | Chọn môn Ngữ văn (đã chấm hết) |
| UC05 “Điểm bài thi đã được xác nhận chính thức.” | Mở lại bài vừa nhập điểm |
| UC06 tự sinh mã | Điểm thi THCS Mẫu A → ô mã phòng gợi ý `P103` |
| UC06 “Thông tin phòng thi bị trùng.” | Mã `P101`, hoặc tên `Phòng 101` tại điểm thi THCS Mẫu A |
| UC07 “Điểm thi không phù hợp với trường đăng ký nguyện vọng 1.” | Chọn điểm thi gắn THPT_1: chỉ thí sinh có NV1 THPT_1 được phân tự động, dù THCS ở khu vực khác |
| UC08 xem chi tiết | Kỳ thi 2026 → Toán → Xem chi tiết |

Mã nguồn: `services/exam_service.php` (UC06–UC08), phần UC05 cuối `services/grading_service.php`; giao diện `includes/exam_ui.php`. Kiểm thử: `BASE_URL=http://127.0.0.1:8080 bash tests/kiem_thu_uc05_08.sh` (tự sao lưu và khôi phục dữ liệu).

### UC09 – UC11: Lô bài thi, Hội đồng thi, Phân công coi thi (Trần Thị Cẩm Vân) — đã chạy với CSDL

Làm theo đặc tả trong báo cáo giữa kỳ (`BaoCaoGK_NhomDreamTeam`, mục 3.3). Cần nhập `database/install.sql` trước.

| UC | Trang | Làm gì |
|---|---|---|
| UC09 Tra cứu thông tin lô bài thi | `/papers/` | Nhập mã lô hoặc chọn điểm thi / phòng / môn → danh sách lô phù hợp → “Xem” để thấy số bài, số bài đã đánh phách và danh sách bài thi để đối chiếu |
| UC10 Quản lý Hội đồng thi | `/councils/` | Danh sách → Xem → Cập nhật (đúng basic flow). Có thêm Thêm mới và Xóa (chặn xóa khi hội đồng còn cán bộ) |
| UC11 Phân công coi thi | `/assignments/` | Chọn điểm thi → phòng → môn → tick cán bộ + vai trò → Xác nhận → lưu trong 1 transaction. Có nút Hủy từng phân công để điều chỉnh |

Quy tắc UC11: mỗi phòng, mỗi môn phải có đủ **Giám thị 1** và **Giám thị 2**; không phân công trùng cán bộ trong cùng phòng + môn; một cán bộ không coi cùng một môn ở 2 phòng (trùng lịch); chỉ cán bộ thuộc Hội đồng coi thi của kỳ thi mới được chọn.

Thử nhanh:

| Muốn thử | Làm |
|---|---|
| UC09 điều kiện không hợp lệ | Bấm Tra cứu khi để trống hết, hoặc chọn Điểm thi Trường THCS Mẫu A + Phòng 201 |
| UC09 không tìm thấy | Mã lô `XYZ` |
| UC09 xem chi tiết | Môn Toán → Xem `LO_P101_TOAN` (5 bài) |
| UC10 thông tin không hợp lệ | Xem HD02 → Cập nhật → đổi tên thành “Hội đồng chấm thi” (trùng HD03) hoặc để trống |
| UC10 không cho xóa | Xem HD02 → Xóa (đang có 6 cán bộ) |
| UC11 trùng lịch | Điểm thi THCS Mẫu A, Phòng 101, Toán → tick Võ Hoàng Long làm Giám thị 1 |
| UC11 chưa đủ 2 giám thị | Hủy Giám thị 2 của phòng đó rồi bấm Tiếp tục mà không chọn ai |

**Lưu ý dữ liệu mẫu:** Hội đồng coi thi chỉ có 6 cán bộ (CB03–CB08) cho 8 chỗ giám thị mỗi môn, và seed đang xếp trùng lịch (vd CB03, CB04 coi Toán ở cả P101 và P202). Vì vậy sau khi hủy một phân công sẽ không còn ai rảnh để phân công lại. Muốn demo lưu thành công, thêm 1–2 cán bộ vào HD02 (bảng CanBo) hoặc bổ sung vào `database/seed/seed.sql`.

Mã nguồn: `services/paper_batch_service.php`, `services/council_service.php`, `services/assignment_service.php`; hàm giao diện chung `includes/ui.php`.

### UC12 – UC15: Chấm thi và công bố điểm (Ngọc Linh) — đã chạy với CSDL

Câu thông báo và luồng bám **nguyên văn báo cáo giữa kỳ** (`BaoCaoGK_NhomDreamTeam`, mục 3.4). Cần nhập `database/install.sql` trước (mục 5). Các chức năng này **có ghi vào database**.

| UC | Trang | Làm gì |
|---|---|---|
| UC12 Xem lịch sử chỉnh sửa điểm | `/grading/lich-su.php` | Chọn kỳ thi, môn thi, nhập mã phách → Tra cứu → xem kết quả chấm → bấm **Xem lịch sử chỉnh sửa** |
| UC13 Cập nhật kết quả chấm thi | `/grading/cap-nhat.php` | Nhập mã phách → nhập điểm mới (0–10, bước 0.25) + lý do → Xác nhận cập nhật / Hủy bỏ. Sửa điểm và ghi `LichSuChinhSuaDiem` trong 1 transaction |
| UC14 Thống kê kết quả chấm thi | `/grading/thong-ke.php` | Chọn kỳ thi, môn thi → tổng bài đã chấm, điểm trung bình, cao nhất, thấp nhất |
| UC15 Công bố điểm thi | `/publication/diem-thi.php` | Chọn kỳ thi, môn thi → Tra cứu → bấm Công bố điểm thi → hệ thống kiểm tra điều kiện → xác nhận / Hủy bỏ |

Dữ liệu mẫu để thử (kỳ thi `Kỳ thi tuyển sinh lớp 10 năm 2026`):

| Muốn thử | Nhập |
|---|---|
| Có lịch sử chỉnh sửa | Môn **Toán**, mã phách `P0052` |
| Chưa có lịch sử ("Kết quả chấm thi này chưa có lịch sử chỉnh sửa điểm.") | Môn **Ngữ văn**, mã phách `P0023`, bấm Xem lịch sử chỉnh sửa |
| Không tìm thấy | Môn **Toán**, mã phách `P0023` (phách này thuộc môn Văn) |
| Thiếu điều kiện tra cứu | Để trống mã phách rồi bấm Tra cứu |
| UC13 "Điểm cập nhật không hợp lệ." / "Vui lòng nhập lý do chỉnh sửa điểm." | Mã phách `P0023`, điểm `11` hoặc `8.3` (sai bước 0.25), hoặc để trống lý do |
| UC15 công bố thành công | Môn **Ngoại ngữ** (18/18 bài đã chấm, chưa công bố) |
| UC15 "Kết quả chấm thi đã được công bố." / "Kết quả chấm thi chưa đủ điều kiện công bố." | Môn **Ngữ văn** (đã công bố) / môn **Toán** khi còn 3 bài chưa chấm |

Muốn đưa dữ liệu về như ban đầu sau khi thử: trong phpMyAdmin xóa database `dreamteam_tuyensinh10` rồi nhập lại `install.sql`.

Mã nguồn: logic trong `services/grading_service.php`, form POST có CSRF (`services/csrf.php`), giao diện dùng chung `includes/ui.php` (+ tab riêng trong `includes/grading_ui.php`), CSS tiền tố `ui-` cuối `assets/css/style.css`.
UC22 (Cẩm Vân) giữ giao diện mẫu cũ, chuyển sang `/publication/tuyen-sinh.php`; `/publication/` giờ là trang chọn UC15 / UC22.

## 3. Phân chia phần code cho nhóm

| Thành viên | Thư mục | Chức năng demo gợi ý |
|---|---|---|
| Nguyễn Quốc Thiên | `aspirations/`, `candidates/`, `admission-info/` | UC02 Nguyện vọng |
| Nguyễn Thị Thúy Vy | `exam/`, `grading/nhap-diem.php` | UC05 – UC08 Nhập điểm, phòng thi, phân phòng, phân công ra đề (**đã xong**) |
| Trần Thị Cẩm Vân | `papers/`, `councils/`, `assignments/`, `staff/` | UC09 – UC11 Lô bài thi, Hội đồng thi, Phân công coi thi (**đã xong**) |
| Nguyễn Thị Ngọc Linh | `grading/`, `publication/diem-thi.php` | UC12 – UC15 Chấm thi, công bố điểm (**đã xong**) |
| Tô Nguyễn Khánh Duy | `appeals/`, `admission/` | UC17 Xét tuyển NV |
| Nguyễn Thị Cẩm Vân | `enrollment/`, `statistics/`, `publication/` | UC20 Nhập học |

**Cách làm việc nhóm (nhánh riêng, Pull Request, quy ước code): xem [`docs/HUONG_DAN_NHOM.md`](docs/HUONG_DAN_NHOM.md).**

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

Các trang danh sách đọc dữ liệu qua `services/live_data.php` (chỉ đọc, giữ nguyên layout). Khi làm chức năng thật, thay truy vấn của module mình bằng service riêng trong `services/`. Sau khi sửa file trong `database/tables/`, `views/`, `functions/`, `procedures/` hoặc `seed/`, chạy `php database/build.php` để tạo lại `install.sql`.

View, function, procedure đã viết (chi tiết ở `database/README.md`): `vw_KetQuaThiTheoMon`, `vw_ThongKeKetQuaMonThi`, `fn_DemBaiThiTheoMon`, `fn_DemBaiThiChuaCham`, `fn_DiemTrungBinhTheoMon`, `sp_CapNhatDiemThi`, `sp_CongBoKetQuaChamThi`, `sp_XemLichSuChinhSuaDiem`.
Nếu đã nhập database từ trước, không cần nhập lại cả `install.sql`: trong phpMyAdmin chọn database `dreamteam_tuyensinh10` → **Nhập** từng file trong `database/views/`, `database/functions/`, `database/procedures/` (chạy lại nhiều lần không sao).

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
├── tests/              kiem_thu_uc09_15.sh (CI chạy tự động)
├── .github/workflows/  ci.yml (GitHub Actions)
└── docs/               sơ đồ, tài liệu, biên bản
```
