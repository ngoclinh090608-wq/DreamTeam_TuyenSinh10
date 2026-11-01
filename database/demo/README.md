# Dữ liệu demo hiện hành

Repository chỉ giữ bản xuất đầy đủ mới nhất dùng để chạy website. Schema và seed kiểm thử được CI ghép thành `../install.sql` bằng `php database/build.php`; file sinh không lưu trên Git; không lưu thêm các snapshot nhỏ trùng mục đích.

| File | Database | Học sinh | Nguyện vọng | Bài thi | Kết quả |
|---|---|---:|---:|---:|---:|
| `dreamteam_tuyensinh10_demo.sql` | `dreamteam_tuyensinh10_demo` | 1.750 | 5.250 | 5.250 | 4.870 |

## Chọn và nhập dữ liệu

- Chạy website: dùng bản **demo**, có đầy đủ hồ sơ bổ sung, gia đình và học bạ. Mặc định ứng dụng kết nối database này.
- CI dùng `../install.sql`, được build từ schema hiện hành và bộ seed kiểm thử nhỏ.
- File demo tự chọn database `dreamteam_tuyensinh10_demo`. Chỉ nhập vào database trống. Máy đã có dữ liệu đang làm việc không cần nhập lại sau khi pull code.
- Sao chép `database.local.example.php` sang `config/database.local.php` nếu cần thay host/user/password/name. Biến môi trường DB_* được ưu tiên; cấu hình local không đưa lên Git.

Bản demo có 11 tài khoản, 380 bài chưa chấm, 1.750 hồ sơ bổ sung, 5.250 dòng gia đình và 7.000 dòng học bạ. Tên người nhập, thời gian, điểm và mật khẩu được giữ nguyên. Điểm thi và phòng thi được chuẩn hóa theo trường THPT nguyện vọng 1; trường THPT Sao Mai được bổ sung phòng thứ 12 để đủ 359 thí sinh.

## Cập nhật và các file còn giữ

Snapshot hiện tại lấy từ file `tuyensinh10.sql` người dùng cung cấp ngày 02/11/2026. Đã có đủ bảng hồ sơ, dữ liệu hồ sơ bổ sung, người nhập điểm demo, điểm thi/phòng thi theo NV1, cột `lopChuyen` và bảng `XetTuyenLanChay`. Máy cài mới chỉ nhập snapshot này, không cần chạy thêm migration.

Bốn migration lịch sử cho hồ sơ, điền hồ sơ giả lập, đổi nhãn người nhập điểm và phân lại điểm thi đã được loại khỏi Git vì nội dung đã nằm trong snapshot. Không nhập lại snapshot trên database đang có dữ liệu để cập nhật các phần này; máy cũ cần sao lưu và kiểm tra cấu trúc/dữ liệu trước khi cập nhật có phạm vi.

Hai migration bổ sung lớp chuyên và UC17 cũng đã được gộp trong snapshot, nên không còn lưu ở thư mục migrations. Nếu máy cũ còn thiếu cấu trúc, sao lưu rồi đối chiếu schema; migration cũ vẫn lấy được từ lịch sử Git, không nhập chồng snapshot để cập nhật.

Ảnh học sinh ở `storage/student-photos/`, không nằm trong SQL; sao lưu ảnh cùng database.

## Kiểm tra

`php database/demo/verify.php` kiểm tra chỉ đọc database demo theo host/user/password cấu hình, báo số liệu và liên kết dữ liệu. Không chạy test ghi dữ liệu trên demo đang dùng.

SHA-256 của file người dùng cung cấp, trước khi chuẩn hóa tên bảng cho Linux và bỏ `DEFINER` phụ thuộc tài khoản máy xuất:

`f27426a63fb1c3c4dc066a461a974d9de7c9f6d30b9da0f4dede8afbbd5037b5`

Các giá trị dữ liệu trong bản xuất được giữ nguyên. `../seed/seed.sql` vẫn cần cho CI; `../install.sql` được tự tạo khi kiểm thử; không nhập chúng vào database demo.
