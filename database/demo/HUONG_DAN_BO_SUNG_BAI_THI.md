# Bổ sung 5.250 bài thi và 4.870 kết quả DEMO

Chỉ dùng database `dreamteam_tuyensinh10_demo`, không nhập vào database phát triển hoặc dữ liệu thật. Sao lưu database demo trước khi nhập.

1. Nếu chưa có database demo: nhập `00_install_demo.sql` một lần trên database trống. Bỏ qua nếu đã khởi tạo.
2. Nhập `01_import_1750.sql` để có 1.750 học sinh và 5.250 nguyện vọng.
3. Nhập `BoSung_5250_BaiThi_4870_KetQua_DEMO.sql`.

File bổ sung tạo kỳ thi riêng `KTDEMO26`: mỗi môn 1.750 bài; Toán đã chấm 1.750, Văn 1.600, Ngoại ngữ 1.520. Tổng 380 bài chưa chấm. Không tạo lịch sử sửa điểm giả. Chọn kỳ thi này khi demo UC05 và UC12–UC15.

Đã thử nhập và nhập lại trên MariaDB 10.11: vẫn có 5.250 bài, 4.870 kết quả mới; giữ 54 bài và 51 kết quả của kỳ thi gốc. Chưa kiểm chứng trên MySQL/XAMPP Windows. File SQL giữ nguyên bản người dùng cung cấp; sử dụng INSERT IGNORE nên không coi lần chạy thành công là kiểm chứng toàn bộ xung đột dữ liệu của database đã chỉnh sửa.
