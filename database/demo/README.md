# Demo 5 trường THCS · 1.750 học sinh · 5.250 nguyện vọng

Nguồn là Excel/SQL mô phỏng do người dùng cung cấp, đã đối chiếu toàn bộ dữ liệu (xem `SOURCE.md`, `SOURCE_CHECK.json`). Bộ này **chỉ dành cho `dreamteam_tuyensinh10_demo`**. Không đổi tên schema, không sửa nghiệp vụ UC12–UC15, không đổi mật khẩu tài khoản.

## Import trên XAMPP/phpMyAdmin

1. Sao lưu cấu hình cục bộ hiện có nếu cần đổi kết nối. Mở phpMyAdmin.
2. Nếu **chưa có database demo**, nhập `database/demo/00_install_demo.sql`. File này tạo database demo, cấu trúc và bộ seed nhỏ gốc với đúng thứ tự khóa ngoại. Chỉ chạy bước khởi tạo này trên database demo trống, một lần. Nếu database demo đã được khởi tạo đúng schema/seed, bỏ qua bước này. Không nhập `database/install.sql` gốc để tạo database demo, vì file gốc chọn database phát triển.
3. Nhập `database/demo/01_import_1750.sql`. Đây là file nhập dữ liệu có thể chạy lại: dùng bảng tạm kiểm tra trước, transaction, không `INSERT IGNORE`/ghi đè hồ sơ, không tắt khóa ngoại. Cùng mã nhưng khác danh tính sẽ bị từ chối và rollback; trạng thái xử lý của các bản ghi đã có được giữ nguyên. Chỉ tiêu chỉ cập nhật 5 bản ghi mẫu `TT_THPT_1`…`TT_THPT_5` của `DOT2026`, và dừng nếu chỉ tiêu đã bị sửa sang giá trị ngoài seed/Excel.
4. Nhập `database/demo/02_verify.sql` để xem số lượng và lỗi FK/trùng lặp. Kỳ vọng: 5 trường mới, 1.750 học sinh mới, 5.250 nguyện vọng mới; các truy vấn lỗi trả 0.
5. Cho website dùng database demo: sao chép **mẫu** `database/demo/database.local.example.php` thành `config/database.local.php`. Nếu file cục bộ này đã có, chỉ sửa khóa `name` sang `dreamteam_tuyensinh10_demo`, giữ host/user/password của máy bạn; không ghi đè cấu hình đang có. File cấu hình thật được Git bỏ qua. Biến môi trường `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` vẫn được ưu tiên, nên cần kiểm tra cấu hình Apache nếu đã đặt các biến đó.
6. Mở website dưới thư mục `DreamTeam_TuyenSinh10` như trước. Không đổi tên thư mục hoặc đường dẫn PHP.

Không tạo file cấu hình local thật chứa mật khẩu trong Git. Database mặc định của ứng dụng vẫn là `dreamteam_tuyensinh10`; chỉ máy/tiến trình chủ động chọn cấu hình demo mới đổi kết nối.

## Dữ liệu thực tế sau khi import

| Trường mới | Mã | Học sinh |
|---|---|---:|
| THCS Sao Mai (mẫu) | THCS_DEMO1 | 320 |
| THCS Bình Minh (mẫu) | THCS_DEMO2 | 335 |
| THCS Hòa An (mẫu) | THCS_DEMO3 | 350 |
| THCS Ánh Dương (mẫu) | THCS_DEMO4 | 365 |
| THCS Tương Lai (mẫu) | THCS_DEMO5 | 380 |
| **Tổng mới** | | **1.750** |

Mã học sinh `TSD000001`…`TSD001750`; hồ sơ `HSD000001`…`HSD001750`; SBD `SBD600001`…`SBD601750`; nguyện vọng `NVD0000001`…`NVD0005250`. Mỗi học sinh mới có 3 thứ tự và 3 trường THPT khác nhau, trong thời gian `QD2026`.

Tổng toàn database: **9 THCS, 5 THPT, 1.770 học sinh, 5.304 nguyện vọng**. Số lớn hơn bộ Excel là do giữ 4 THCS, 20 học sinh, 54 nguyện vọng gốc để các bài kiểm thử cũ hoạt động. Chỉ tiêu 5 THPT trong demo là 320/335/350/365/380.

Giữ 11 tài khoản cũ, mật khẩu `dreamteam2026`. Không tạo thêm 1.750 tài khoản đăng nhập. Dùng `bantuyensinh` hoặc `gvcn` để xem danh sách học sinh; dùng `hdtuyensinh` để xem danh sách nguyện vọng tổng hợp. Tài khoản học sinh chỉ xem hàng nguyện vọng gắn với `maThiSinh` của mình, không xem toàn bộ 1.750 hồ sơ.

## Trang web

- `/candidates/`: tìm theo mã, hồ sơ, họ tên, SBD hoặc tên THCS; lọc trường; phân trang 25/50/100 dòng; thống kê số học sinh từng trường.
- `/aspirations/`: cùng bộ lọc/phân trang; chỉ truy vấn các nguyện vọng của trang hiện tại. Phạm vi học sinh lấy từ session, không nhận mã chủ sở hữu từ URL.
- Danh sách trường và thống kê dùng các truy vấn database đang có. Các trang danh sách mới không thay dữ liệu bằng HTML mẫu khi mất kết nối.
- Các chức năng này hiện là xem/tra cứu, không bổ sung nghiệp vụ thêm/sửa/xóa nguyện vọng hoặc hồ sơ mới.

## UC12–UC15

Bộ seed cũ đã đủ tình huống, nên không tạo thêm bài thi/điểm cho 1.750 học sinh mới:

- Giữ 54 bài thi, 51 kết quả chấm, 1 lịch sử chỉnh sửa.
- Toán: 18 bài, 15 đã chấm, 3 chưa chấm → chưa đủ điều kiện công bố.
- Ngoại ngữ: 18/18 đã chấm, chưa công bố → đủ điều kiện cho ca kiểm thử công bố.
- Văn: 18/18 đã chấm, đã công bố từ seed gốc.

Importer không gọi công bố điểm, không sửa điểm/lịch sử hay tự xét tuyển. Các ca ghi của bộ kiểm thử cũ chỉ chạy trong database demo, bật sao lưu/khôi phục, và đã khôi phục trạng thái sau khi kết thúc.

## Kiểm chứng và tái tạo

- `REPORT.json`: kết quả truy vấn thực tế từ database demo trong môi trường đám mây; **không phải** kết quả chạy trên MySQL Windows của người dùng.
- `php database/demo/verify.php`: kiểm tra lại 21 điều kiện, xuất JSON, thoát khác 0 nếu lỗi; luôn chọn database demo. `02_verify.sql` là lựa chọn qua phpMyAdmin.
- `IMPORT_TESTS.json`: nhập lại không trùng, từ chối va chạm định danh, dữ liệu UC12–UC15/tài khoản giữ nguyên khi import.
- `SOURCE_CHECK.json`: cả 4 file gửi riêng giống hệt ZIP; 5/1.750/5.250/5 dòng của các sheet khớp SQL nguồn.
- Kiểm tra trên cloud: 59 file PHP đúng cú pháp; 163/163 ca đăng nhập/phân quyền/UC09–UC15 đạt trên database demo; 55 kiểm tra trình duyệt đạt (tìm kiếm, phân trang, trường, thống kê, mobile, học sinh không tra cứu hồ sơ người khác).
- Tạo lại SQL khi nguồn/schema thay đổi: cài Python và `openpyxl`, chạy `python3 database/demo/build_demo.py`. Đọc lại hướng dẫn file 00 trước khi dùng SQL khởi tạo mới.

Xem `CLEANUP_REVIEW.md` trước khi dọn dữ liệu. Không có dữ liệu/file cũ nào bị xóa trong lần bổ sung này. Chủ dự án đã cho phép commit/push bản cập nhật lên `linh`. Không merge hoặc sửa nhánh main; phần dọn dữ liệu vẫn chờ duyệt riêng.

## Dọn bộ mẫu nhỏ sau khi bổ sung bài thi mới

Nếu muốn chỉ giữ bộ 1.750 học sinh và tên kỳ thi cũ, xem mục dọn dữ liệu trong [HUONG_DAN_BO_SUNG_BAI_THI.md](HUONG_DAN_BO_SUNG_BAI_THI.md). File `03_remove_old_seed.sql` xóa bộ mẫu nhỏ sau khi xác minh bộ mới đã đủ; sao lưu database demo trước khi nhập. Các số 1.770/5.304 và 54/51 ở trên mô tả trạng thái trước bước dọn, không phải số liệu sau dọn.
