# Database: demo và kiểm thử

## Chọn bộ dữ liệu

| Mục đích | File nhập | Database |
|---|---|---|
| Chạy/demo website | `demo/dreamteam_tuyensinh10_demo.sql` | `dreamteam_tuyensinh10_demo` |
| Kiểm thử trên bộ seed nhỏ | `install.sql` (tạo bằng `php database/build.php`) | `qa_dt_test` |

Bản demo có 1.750 học sinh; bộ kiểm thử có 20 học sinh. Không nhập hai bộ chồng lên nhau. Máy đã có dữ liệu không cần import lại khi pull code. Xem [hướng dẫn demo](demo/README.md) cho cài đặt mới và kiểm chứng chỉ đọc.

Ứng dụng chọn DB_* từ môi trường trước, rồi `config/database.local.php`, cuối cùng các mặc định trong `config/database.php`. Mặc định hiện tại là database demo. File local chứa thông tin riêng không được commit. `.env.example` chỉ là mẫu, không được PHP tự nạp.

## Mã nguồn cấu trúc

- `tables/`: 35 bảng gồm tài khoản và ba bảng hồ sơ học sinh bổ sung; thứ tự file theo khóa ngoại.
- `views/`: các view tra cứu/thống kê và phân phòng đã triển khai.
- `functions/`: các hàm đếm phòng, sức chứa, bài thi và tính điểm trung bình.
- `procedures/`: nghiệp vụ quản lý/phân phòng, phân công ra đề, nhập/sửa/công bố điểm và lịch sử điểm.
- `seed/seed.sql`: dữ liệu mô phỏng nhỏ dùng cho kiểm thử.
- `build.php`: gộp các phần trên thành `install.sql`.
- `demo/`: snapshot đầy đủ dùng để chạy website, mẫu cấu hình và công cụ kiểm tra chỉ đọc. Xem hướng dẫn trong `demo/README.md`; CI dùng `install.sql` và không phụ thuộc snapshot demo.

Những file trong tables/views/functions/procedures là mã nguồn schema để nhóm phát triển, không phải nhiều bộ dữ liệu cần nhập lần lượt. File `install.sql` được sinh từ chúng, được Git bỏ qua; không sửa tay hoặc commit. Snapshot demo là bản xuất dữ liệu riêng, không được build từ seed nhỏ.

## Khi sửa schema hoặc seed kiểm thử

1. Sửa file nguồn tương ứng; giữ tên bảng/cột thống nhất với PHP và tài liệu nhóm.
2. Chạy:
   ```bash
   php database/build.php
   ```
3. Kiểm tra diff và chỉ commit các file nguồn. CI tự tạo `database/install.sql` trước khi nhập và chạy kiểm thử.
4. Thử import trên database kiểm thử trống, có sao lưu. Không tự reset hoặc cập nhật schema của database đang làm việc bằng cách nhập lại file đầy đủ.

Function/procedure dùng DELIMITER và DROP IF EXISTS theo các file hiện hành. Khi cần cập nhật một database đã có, chuẩn bị thay đổi có phạm vi rõ ràng và kiểm tra tương thích dữ liệu; không coi việc import full dump là migration.

## Kiểm thử

CI dùng PHP 8.2 và MariaDB 10.4, nhập bộ nhỏ vào database riêng và chạy các test trong `.github/workflows/ci.yml`. Bộ gốc có 20 học sinh, 54 nguyện vọng, 54 bài thi, 51 kết quả; các test cần đúng mã và trạng thái seed này.

Đặt DB_NAME cho cả PHP server lẫn lệnh test để tránh kết nối nhầm demo. Dù một số test có sao lưu/khôi phục, không chạy test ghi dữ liệu trên database demo đang dùng.

SQL demo hiện hành được kiểm tra trên MariaDB cloud. File xuất gốc từ XAMPP đã được chuẩn hóa tên bảng/view để tương thích hệ thống phân biệt chữ hoa/thường. Không thay đổi mã học sinh, điểm, mật khẩu hay dữ liệu localhost khi chỉ pull repository.

## Cập nhật database đang dùng

Snapshot demo hiện hành đã có hồ sơ bổ sung, cột lớp chuyên và bảng lưu vết UC17. Các migration đã tích hợp vào snapshot được bỏ khỏi Git. Máy cài mới chỉ nhập `demo/dreamteam_tuyensinh10_demo.sql` vào database trống.

Máy đang dùng dữ liệu cũ: sao lưu và đối chiếu cấu trúc trước khi bổ sung phần thiếu; có thể lấy lại migration từ lịch sử Git. Không import chồng toàn bộ snapshot lên dữ liệu đang làm việc.

## Bản sửa QA 10/11/2026: công bố điểm và phúc khảo

- Bộ build/test nay chỉ dùng `qa_dt_*` (mặc định `qa_dt_test`, CI dùng `qa_dt_ci`). Ví dụ `DB_NAME=qa_dt_test php database/build.php`. Không chạy các test ghi trên `dreamteam_tuyensinh10` hoặc `dreamteam_tuyensinh10_demo`. Đặt cùng DB_NAME cho PHP server và lệnh test; database test phải nằm trên môi trường riêng.
- Schema mới có `KetQuaChamThi.thoiDiemCongBo`. Với database đã cài, kiểm tra/sao lưu rồi áp dụng **riêng** `migrations/20261110_publication_timestamp.sql` vào đúng database, trước khi triển khai phần phúc khảo. File chỉ bổ sung cột nullable; không xóa bảng, đổi điểm hay nhập lại seed. Phiên sửa QA này không áp dụng migration vào hai database nhóm được bảo vệ.
- Với cài mới từ snapshot demo: snapshot được giữ nguyên; áp dụng migration trên sau khi import vào database mới. Build từ tables/ đã có cột này.
- UC15 lưu thời điểm công bố theo giờ Việt Nam. Phúc khảo bắt đầu 00:00 ngày kế tiếp và kết thúc trước 00:00 ngày thứ tám (đủ 7 ngày). Công bố lại không gia hạn. Ví dụ công bố ngày 09/11 → nhận từ 00:00 10/11 đến hết 16/11.
- Dữ liệu đã công bố từ trước không có lịch sử thời điểm công bố: cột mới để NULL; không suy đoán từ `thoiDiemNhap`. Chỉ điền lại sau khi đối chiếu thời điểm công bố thực tế từ hồ sơ của nhóm. Khi chưa có thời điểm, hệ thống không nhận phúc khảo và không cho chốt xét tuyển/công bố kết quả tuyển sinh. Đây là hành vi bảo vệ dữ liệu, không tự mở thêm 7 ngày cho điểm cũ.
- Học sinh gửi một yêu cầu cho mỗi bài thi; chỉ điểm đã công bố, trong hạn, đúng chủ hồ sơ mới được nhận. Hội đồng phúc khảo chỉ xử lý kỳ thi được phân công; kết quả, minh chứng dạng văn bản, biên bản, điểm chính thức và lịch sử điểm nằm trong cùng giao dịch. Không có upload tệp trong luồng này.
- UC22 tạo hồ sơ nhập học còn thiếu cho người trúng tuyển khi công bố; trạng thái đã xác nhận được giữ nguyên. Không cần chèn hồ sơ nhập học bằng SQL để chạy UC20 cho danh sách mới.
- Kiểm thử hồi quy mới: `php tests/kiem_thu_qa_regression.php` (gọi helper Python HTTP), cùng `php tests/kiem_thu_uc17.php`. Cần PHP 8.2, Python 3, mysql client, web test đang chạy, DB_NAME riêng và seed mới. Test tự dọn fixture QA trong database test.
