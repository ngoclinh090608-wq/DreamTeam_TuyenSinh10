# Database demo hiện hành

Dùng duy nhất **`dreamteam_tuyensinh10_demo.sql`** trong thư mục này để cài demo mới. Đây là bản xuất người dùng cung cấp ngày 31/10/2026, đã chuẩn hóa tên bảng theo schema của dự án để PHP chạy trên cả Windows và Linux. Không đổi mã, điểm hay mật khẩu; bỏ DEFINER root@localhost để tránh gắn quyền vào máy xuất.

| Dữ liệu | Số lượng |
|---|---:|
| Học sinh | 1.750 |
| Nguyện vọng | 5.250 |
| Bài thi | 5.250 |
| Kết quả chấm | 4.870 |
| Kỳ thi | 1 |
| Tài khoản | 11 |
| Lịch sử sửa điểm | 0 |

Kỳ thi: `KTDEMO26` — **Kỳ thi tuyển sinh lớp 10 năm 2026**. Chưa chấm 380 bài; không tạo lịch sử sửa điểm giả. Năm THPT: Sao Mai, Bình Minh, Hòa An, Ánh Dương, Trần Quốc Khải. Năm THCS mới có 320/335/350/365/380 học sinh; THCS thứ năm là Tương Lai. Bốn THCS cũ vẫn là danh mục liên kết cán bộ, không còn 20 học sinh cũ.

## Máy đã có dữ liệu như bảng trên

Không nhập lại SQL, không xóa/reset database. Pull chỉ cập nhật code/file; không tự thay đổi database localhost. Nếu đã có sửa điểm/nguyện vọng sau lúc xuất, giữ database hiện tại của bạn.

## Cài trên máy mới

1. Sao lưu database hiện có nếu có. File này chỉ dành cho database demo **trống**; không nhập chồng, không dùng trên database chính. Các file full dump không phải migration và không có rollback toàn bộ DDL.
2. Trong phpMyAdmin, nhập `database/demo/dreamteam_tuyensinh10_demo.sql` một lần. File tự chọn đúng tên database demo. Tài khoản import cần quyền tạo database, bảng, routine và view.
3. Sao chép `database.local.example.php` sang `config/database.local.php` nếu chưa có. Nếu đã có thì chỉ sửa `name` sang `dreamteam_tuyensinh10_demo`, giữ thông tin kết nối cá nhân; DB_* trong môi trường vẫn được ưu tiên. Không commit file cấu hình thật.
4. Mở website và kiểm tra dashboard. Không sửa cấu hình chung chỉ để đổi host/user/password của riêng máy mình.

## Kiểm chứng chỉ đọc

Chạy `php database/demo/verify.php` với cấu hình host/port/user/pass cục bộ; công cụ chọn database demo, báo database thực tế và số liệu hiện tại, không sửa dữ liệu. Số kết quả/lịch sử có thể tăng sau khi nhập hoặc sửa điểm qua nghiệp vụ.

## Các file cũ

Các script nhập nhiều bước, dọn seed và đổi tên, cùng báo cáo trung gian đã được bỏ khỏi phiên bản hiện hành; có thể xem lại trong lịch sử Git. Hai bản xuất `dreamteam_tuyensinh10.sql` và `dreamteam_tuyensinh10_test.sql` người dùng gửi là bộ nhỏ cũ, không đưa thêm vào repo để tránh nhầm. `database/install.sql`, `tables/`, `seed/`, `views/`, `functions/`, `procedures/` và các bài test vẫn giữ cho môi trường kiểm thử riêng; không nhập chúng chồng lên demo lớn.

## Nguồn và dọn file

Bản xuất phpMyAdmin do người dùng cung cấp ngày 31/10/2026, SHA-256 gốc: `675a3c2c42bdcaca16f6070cb1b47db5f0e845b26043fc6f8f716a7b63868a9d`. Bản SQL hiện hành chỉ thêm chọn database, chuẩn hóa tên bảng/view và bỏ DEFINER cục bộ, giữ nguyên dữ liệu.

Excel nguồn giai đoạn đầu, báo cáo trung gian và các script nhập nhiều bước đã bỏ khỏi nhánh hiện hành để tránh nhầm. Có thể khôi phục từ lịch sử Git. Thư mục demo chỉ giữ SQL đầy đủ, hướng dẫn này, mẫu cấu hình và công cụ kiểm tra chỉ đọc.

### Cấu hình sau khi đồng bộ

Các nhánh dùng mặc định database demo nếu không có cấu hình ghi đè. Khi chạy kiểm thử bộ seed nhỏ, đặt DB_NAME sang database kiểm thử.

## Bổ sung hồ sơ học sinh theo biểu mẫu

Máy đã có database: sao lưu trước, chọn database demo trong phpMyAdmin và nhập **`database/migrations/20261031_student_profile.sql`** một lần. Migration chỉ thêm ba bảng `HoSoBoSung`, `GiaDinhThiSinh`, `HocTapTHCS`; không xóa/import lại học sinh, nguyện vọng, bài thi, điểm hoặc tài khoản. Chạy lại migration không tạo trùng dữ liệu.

Máy mới dùng snapshot SQL hiện hành đã bao gồm các bảng này. Sau cập nhật, học sinh có thể lưu ảnh, liên hệ, gia đình, học bạ lớp 6–9 và khai báo diện ưu tiên của mình. Họ tên/ngày sinh/trường/SBD chính thức vẫn do nhà trường quản lý. Điểm học bạ và diện ưu tiên do học sinh khai báo không tự thay đổi điểm thi hoặc điểm xét tuyển.

Ảnh lưu trong `storage/student-photos/` (Git bỏ qua), không nằm trong SQL. Giới hạn ảnh 2 MB, định dạng JPG/PNG/WebP; PHP cần cho phép upload và ghi vào storage. Ảnh chỉ được trả qua endpoint có xác thực của chính học sinh. Sao lưu cả database và thư mục ảnh khi cần chuyển máy. Học sinh có thể nhập/sửa thông tin của mình; các dữ liệu tự khai này không tự cộng vào điểm thi hoặc xét tuyển.

## Tạo đầy đủ hồ sơ bổ sung cho tất cả thí sinh

Máy đã có database và ba bảng hồ sơ bổ sung: sao lưu database rồi nhập **`database/migrations/20261031_fill_student_profiles.sql`** bằng phpMyAdmin. Nếu chưa có ba bảng, nhập migration `20261031_student_profile.sql` trước. Không nhập lại bản full SQL vào database đang dùng.

Script tạo dữ liệu **giả lập** cho toàn bộ thí sinh hiện có, không tạo tài khoản đăng nhập mới. Với bộ 1.750 học sinh: có 1.750 dòng HoSoBoSung, 5.250 dòng gia đình (mẹ/cha/người giám hộ), 7.000 dòng học bạ lớp 6–9. Chỉ bổ sung trường đang trống/null; giữ nội dung đã nhập, ảnh tải lên, điểm thi, nguyện vọng và tài khoản. Có transaction và kiểm tra va chạm số định danh; nhập lại không tạo trùng.

Tên người thân, giới tính, địa chỉ và học bạ được sinh để kiểm thử giao diện; không phải dữ liệu đã xác minh hay thông tin cá nhân thực. Email thuộc miền dành cho thử nghiệm `example.test`; số định danh bắt đầu bằng 900 và điện thoại bắt đầu bằng 000 là giá trị giả lập, không phải số được cấp/liên hệ thật. Không gửi email/SMS từ bộ dữ liệu này. Học bạ giả lập không sửa hoặc tính lại điểm thi/xét tuyển.

Snapshot cài mới đã kèm bước bổ sung hồ sơ, nên máy mới không cần chạy seed này riêng. Máy đã có thông tin người dùng nhập thì dùng migration, không thay bằng snapshot.
