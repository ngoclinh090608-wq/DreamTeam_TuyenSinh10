# Bổ sung 5.250 bài thi và 4.870 kết quả DEMO

Chỉ dùng database `dreamteam_tuyensinh10_demo`, không nhập vào database phát triển hoặc dữ liệu thật. Sao lưu database demo trước khi nhập.

1. Nếu chưa có database demo: nhập `00_install_demo.sql` một lần trên database trống. Bỏ qua nếu đã khởi tạo.
2. Nhập `01_import_1750.sql` để có 1.750 học sinh và 5.250 nguyện vọng.
3. Nhập `BoSung_5250_BaiThi_4870_KetQua_DEMO.sql`.

File bổ sung tạo kỳ thi riêng `KTDEMO26`: mỗi môn 1.750 bài; Toán đã chấm 1.750, Văn 1.600, Ngoại ngữ 1.520. Tổng 380 bài chưa chấm. Không tạo lịch sử sửa điểm giả. Chọn kỳ thi này khi demo UC05 và UC12–UC15.

Đã thử nhập và nhập lại trên MariaDB 10.11: vẫn có 5.250 bài, 4.870 kết quả mới; giữ 54 bài và 51 kết quả của kỳ thi gốc. Chưa kiểm chứng trên MySQL/XAMPP Windows. File SQL giữ nguyên bản người dùng cung cấp; sử dụng INSERT IGNORE nên không coi lần chạy thành công là kiểm chứng toàn bộ xung đột dữ liệu của database đã chỉnh sửa.

## Chỉ giữ bộ dữ liệu lớn (tùy chọn, có xóa dữ liệu)

Người dùng đã yêu cầu bỏ 20 học sinh/54 nguyện vọng và kỳ thi mẫu nhỏ. Xuất (Export) toàn bộ `dreamteam_tuyensinh10_demo` trong phpMyAdmin để sao lưu, rồi nhập `03_remove_old_seed.sql` sau khi file bổ sung ở bước 3 đã chạy thành công. Không chạy trên database chính. Dừng các thao tác ghi của website trong lúc nhập.

File dọn dữ liệu dùng transaction, giữ khóa ngoại và dừng khi bộ dữ liệu mới chưa đủ. Nó xóa TS001–TS020 cùng nguyện vọng, bài thi, điểm, lịch sử điểm, phúc khảo, xét tuyển/nhập học liên quan và phòng/lô bài thi của KT2026. Giữ nguyên các dữ liệu mới và chỉ tiêu. Giữ tài khoản, mật khẩu, vai trò, cán bộ và các danh mục trường còn cần cho cán bộ; chuyển hội đồng/phân công ra đề sang KTDEMO26. Tài khoản học sinh gắn TS001–TS020 được gắn sang TSD000001–TSD000020 tương ứng, cập nhật họ tên, giữ tên đăng nhập cũ.

Kỳ thi KTDEMO26 được đổi tên thành **Kỳ thi tuyển sinh lớp 10 năm 2026**, KT2026 bị xóa. Sau nhập, mở lại trang từ menu và chọn lại kỳ thi; URL cũ chứa `kyThi=KT2026` không còn hợp lệ.

Kỳ vọng trên bộ nguyên gốc: 1.750 học sinh, 5.250 nguyện vọng, 5.250 bài, 4.870 kết quả. Nếu đã nhập thêm điểm cho bài mới thì số kết quả được giữ lại có thể cao hơn. Không sinh lịch sử sửa điểm giả; muốn xem lịch sử mới, sửa điểm qua UC13. Sau dọn, không nhập lại các file 00/01 hoặc file BoSung vì chúng phục vụ trạng thái trước dọn. Bộ test seed cũ cần chạy ở database kiểm thử riêng.

Đã thử trên bản sao MariaDB 10.11: nhập và nhập lại đều thành công, dữ liệu mới/điểm mới/chỉ tiêu và thông tin xác thực giữ nguyên. Chưa chạy trên localhost Windows của người dùng. Pull code không tự xóa hay đổi database; chỉ nhập file 03 mới áp dụng.

## Đổi tên 5 trường THPT và biểu đồ THCS

Sau khi pull code, nhập `04_rename_thpt.sql` vào `dreamteam_tuyensinh10_demo`: THPT_1 → Sao Mai, THPT_2 → Bình Minh, THPT_3 → Hòa An, THPT_4 → Ánh Dương, THPT_5 → Trần Quốc Khải theo tên người dùng chọn. File chỉ đổi tên; giữ mã trường, chỉ tiêu, nguyện vọng và điểm. Có thể nhập lại. Sao lưu database trước khi nhập. Không đổi tên các THCS; THCS_DEMO5 vẫn là Tương Lai. Dashboard/thống kê có thêm biểu đồ số học sinh theo THCS từ database đang kết nối; biểu đồ nguyện vọng/chỉ tiêu vẫn dùng các trường THPT. Pull code không tự chạy SQL.
