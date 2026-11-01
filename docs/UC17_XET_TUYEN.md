# UC17 – Xét tuyển NV1, NV2, NV3

Triển khai theo UC17, BR12–BR14 trong `BaoCaoGK_NhomDreamTeam.docx` và các ca kiểm thử Tuần 04; schema theo Tuần 08. Báo cáo giữa kỳ quy định phân bổ chỉ tiêu riêng từng NV, nên không tự chuyển chỉ tiêu thiếu sang NV tiếp theo như mô tả cũ Tuần 02.

## Cập nhật XAMPP đang có dữ liệu

Bật Apache và MySQL. Chạy tại thư mục repository trong Git Bash:

```bash
git fetch origin
git switch duy
git pull --ff-only origin duy
/c/xampp/mysql/bin/mysql.exe -h 127.0.0.1 -u root dreamteam_tuyensinh10_demo < database/migrations/20261102_uc17_admission.sql
```

Nếu XAMPP ở ổ khác, đổi `/c/xampp`; nếu MySQL có mật khẩu, thêm `-p` để nhập tại lời nhắc. Nếu database có tên khác, dùng tên đang cấu hình trong `config/database.local.php`. Migration chỉ thêm bảng lưu vết `XetTuyenLanChay`, không xóa dữ liệu và có thể chạy lại. Không import lại snapshot demo hoặc `install.sql` vào database đang làm việc. Máy cài mới cần import snapshot demo theo README rồi chạy migration UC17 (snapshot demo chưa chứa bảng này).

Mở `http://localhost/DreamTeam_TuyenSinh10/admission/`. Đăng nhập `hdtuyensinh` với mật khẩu hiện tại; mật khẩu khởi tạo của bộ demo là `dreamteam2026`.

## Thao tác

1. Chọn kỳ thi, tải chỉ tiêu. Màn hình hiển thị tổng chỉ tiêu và số đăng ký NV1/NV2/NV3 của từng trường.
2. Nhập số lượng chỉ tiêu NV1/NV2/NV3. Tổng ba số phải đúng tổng chỉ tiêu trường. Mặc định toàn bộ ở NV1; Hội đồng điều chỉnh trước khi chạy.
3. Nhập hệ số Toán, Ngữ văn, Ngoại ngữ theo quy chế áp dụng. Tài liệu không ấn định các hệ số cụ thể; giao diện mặc định 1 và cho phép thay đổi. Điểm xét tuyển = tổng ba điểm nhân hệ số tương ứng + ưu tiên + khuyến khích; làm tròn hai chữ số. Nhập điểm cộng đã xác minh bằng từng dòng `maThiSinh,uuTien,khuyenKhich`; không khai báo nghĩa là 0. Các giá trị được lưu cùng lần xét.
4. Xác nhận đã chốt điểm chính thức và kết thúc phúc khảo. Database hiện chưa lưu lịch đóng phúc khảo; Hội đồng phải xác nhận thực tế, không tích hai ô khi chưa hoàn tất. Hệ thống kiểm tra thêm tất cả điểm đã chấm/công bố và không còn yêu cầu phúc khảo chưa có kết quả. Điểm sau phúc khảo được ưu tiên thay cho điểm chấm ban đầu.
5. Bấm **Chạy xét tuyển và xem trước**. Dữ liệu chính thức chưa đổi. Xét theo điểm từ cao xuống thấp trong từng trường, lần lượt NV1 → NV2 → NV3; đã trúng tuyển thì không xét các NV sau.
6. Nếu bằng điểm ở suất cuối, chọn A nhận toàn bộ nhóm bằng điểm (có thể vượt chỉ tiêu NV), hoặc B chỉ nhận các điểm cao hơn nhóm đó (có thể tuyển thiếu), rồi tính lại. Áp dụng cho cả ba NV, không tự chọn theo mã học sinh.
7. Kiểm tra danh sách, điểm chuẩn và số lượng, tích xác nhận rồi **Lưu kết quả xét tuyển chính thức**. Tạo danh sách chưa phê duyệt/chưa công bố cho UC22, giữ trạng thái `Trúng tuyển` trong bảng kết quả để tương thích UC19–UC22; NV thắng lưu tại `NguyenVong.trangThaiNguyenVong` và có thể truy qua `thuTuNguyenVong`.

Dữ liệu/chỉ tiêu/điểm thay đổi sau xem trước yêu cầu tính lại. Lưu thực hiện trong transaction; khi lỗi, kết quả trước đó được giữ nguyên. Không ghi đè kết quả đang nằm trong danh sách khác, đã phê duyệt/công bố hoặc có hồ sơ nhập học. Lịch sử ghi người xác nhận, cấu hình hệ số/điểm cộng/chỉ tiêu/phương án bằng điểm và điểm chuẩn.

Hiện hỗ trợ thi tuyển **hệ thường**, ba môn `TOAN`, `VAN`, `NN`, một kỳ thi trong mỗi đợt. Có nguyện vọng chuyên, nhiều chỉ tiêu/hệ cho cùng trường hoặc nhiều kỳ thi trong cùng đợt sẽ báo lỗi để tránh áp dụng sai công thức. Chưa hỗ trợ điểm học bạ hoặc công thức môn chuyên vì tài liệu/schema chưa đủ cấu hình các quy tắc đó.

Demo có 380 bài chưa chấm và kết quả nhập học/danh sách cũ, nên UC17 có thể báo chưa đủ điều kiện hoặc chặn ghi đè; đó là kiểm tra nghiệp vụ. Không tự điền điểm giả hoặc reset demo chỉ để chạy được. Có thể thử các ca thành công trên database kiểm thử riêng.

## Kiểm thử

Không chạy bộ test ghi trên demo. Trên database **kiểm thử** `dreamteam_tuyensinh10` đã import `database/install.sql`, chạy server riêng và test (Git Bash):

```bash
DB_NAME=dreamteam_tuyensinh10 php -S 127.0.0.1:8081 router.php
# Terminal thứ hai, cùng thư mục:
DB_NAME=dreamteam_tuyensinh10 BASE_URL=http://127.0.0.1:8081 php tests/kiem_thu_uc17.php
```

PHP cần `pdo_mysql`, Python 3 cần nằm trong PATH; DB_HOST/DB_PORT/DB_USER/DB_PASS có thể đặt theo môi trường. Test tạo dữ liệu UC17 riêng rồi dọn lại, bao gồm xét ba NV, hệ số/điểm cộng/phúc khảo, hai phương án bằng điểm, HTTP đăng nhập/phân quyền/CSRF/xem trước, xác nhận lưu, stale preview, rollback và chống sửa kết quả đã duyệt. CI gọi cùng bộ test.
