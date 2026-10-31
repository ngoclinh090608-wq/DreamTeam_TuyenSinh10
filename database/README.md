# Database: demo và kiểm thử

## Chọn bộ dữ liệu

| Mục đích | File nhập | Database |
|---|---|---|
| Chạy/demo website | `demo/dreamteam_tuyensinh10_demo.sql` | `dreamteam_tuyensinh10_demo` |
| Kiểm thử trên bộ seed nhỏ | `install.sql` | `dreamteam_tuyensinh10` |

Bản demo có 1.750 học sinh; bộ kiểm thử có 20 học sinh. Không nhập hai bộ chồng lên nhau. Máy đã có dữ liệu không cần import lại khi pull code. Xem [hướng dẫn demo](demo/README.md) cho cài đặt mới và kiểm chứng chỉ đọc.

Ứng dụng chọn DB_* từ môi trường trước, rồi `config/database.local.php`, cuối cùng các mặc định trong `config/database.php`. Mặc định hiện tại là database demo. File local chứa thông tin riêng không được commit. `.env.example` chỉ là mẫu, không được PHP tự nạp.

## Mã nguồn cấu trúc

- `tables/`: 31 bảng gồm bảng tài khoản; thứ tự file theo khóa ngoại.
- `views/`: các view tra cứu/thống kê và phân phòng đã triển khai.
- `functions/`: các hàm đếm phòng, sức chứa, bài thi và tính điểm trung bình.
- `procedures/`: nghiệp vụ quản lý/phân phòng, phân công ra đề, nhập/sửa/công bố điểm và lịch sử điểm.
- `seed/seed.sql`: dữ liệu mô phỏng nhỏ dùng cho kiểm thử.
- `build.php`: gộp các phần trên thành `install.sql`.
- `demo/`: một bản SQL đầy đủ của dữ liệu demo hiện hành, mẫu cấu hình và công cụ kiểm tra chỉ đọc.

Những file trong tables/views/functions/procedures là mã nguồn schema để nhóm phát triển, không phải nhiều bộ dữ liệu cần nhập lần lượt. File `install.sql` được sinh từ chúng; không sửa tay. Snapshot demo là bản xuất dữ liệu riêng, không được build từ seed nhỏ.

## Khi sửa schema hoặc seed kiểm thử

1. Sửa file nguồn tương ứng; giữ tên bảng/cột thống nhất với PHP và tài liệu nhóm.
2. Chạy:
   ```bash
   php database/build.php
   ```
3. Kiểm tra diff và commit cả file nguồn lẫn `database/install.sql`. CI sẽ build lại và so sánh.
4. Thử import trên database kiểm thử trống, có sao lưu. Không tự reset hoặc cập nhật schema của database đang làm việc bằng cách nhập lại file đầy đủ.

Function/procedure dùng DELIMITER và DROP IF EXISTS theo các file hiện hành. Khi cần cập nhật một database đã có, chuẩn bị thay đổi có phạm vi rõ ràng và kiểm tra tương thích dữ liệu; không coi việc import full dump là migration.

## Kiểm thử

CI dùng PHP 8.2 và MariaDB 10.4, nhập bộ nhỏ vào database riêng và chạy các test trong `.github/workflows/ci.yml`. Bộ gốc có 20 học sinh, 54 nguyện vọng, 54 bài thi, 51 kết quả; các test cần đúng mã và trạng thái seed này.

Đặt DB_NAME cho cả PHP server lẫn lệnh test để tránh kết nối nhầm demo. Dù một số test có sao lưu/khôi phục, không chạy test ghi dữ liệu trên database demo đang dùng.

SQL demo hiện hành được kiểm tra trên MariaDB cloud. File xuất gốc từ XAMPP đã được chuẩn hóa tên bảng/view để tương thích hệ thống phân biệt chữ hoa/thường. Không thay đổi mã học sinh, điểm, mật khẩu hay dữ liệu localhost khi chỉ pull repository.
