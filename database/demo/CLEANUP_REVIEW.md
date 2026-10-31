# Báo cáo dọn dữ liệu — chờ duyệt trước khi xóa

Yêu cầu đính kèm quy định: “Trước khi xóa bất kỳ dữ liệu hoặc file nào, hãy báo danh sách dự kiến xóa để tôi duyệt.” Do đó lần này không thực hiện DELETE/TRUNCATE/DROP TABLE/DROP DATABASE đối với dữ liệu/bảng có sẵn, không xóa file cũ.

## Không có dữ liệu trùng định danh cần xóa

Sau hai lần nhập, vẫn đúng 1.750 học sinh mới và 5.250 nguyện vọng. Các kiểm tra trùng mã hồ sơ/SBD/thứ tự nguyện vọng và khóa ngoại đều 0 lỗi. Hồ sơ trùng tên không được xem là cùng học sinh khi mã/SBD khác nhau.

## Phải giữ

- 4 THCS gốc `THCS_A`…`THCS_D`, 20 học sinh `TS001`…`TS020`, 54 nguyện vọng `NV001`…`NV054`: được liên kết với bài thi, điểm, phúc khảo, tài khoản và các ca kiểm thử gốc. Đây không phải dữ liệu trùng 5 trường mới.
- 5 THPT, khu vực, kỳ thi, môn, hội đồng, cán bộ và 11 tài khoản: là dữ liệu nền cần dùng.
- Toàn bộ bài thi/điểm/lịch sử phục vụ UC12–UC15; không sửa trạng thái công bố bởi importer.
- Source nghiệp vụ và tài liệu thành viên; không xóa Excel nguồn. Không chép thêm ZIP và các SQL thô chưa idempotent vào repository để tránh nhiều bản import dễ nhầm; file người dùng đã tải lên vẫn được giữ nguyên ngoài checkout.

## Đề xuất dọn tiếp, chưa thực hiện

Sau khi duyệt, có thể thay các mảng `rows` minh họa của `candidates` (4 dòng) và `aspirations` (3 dòng) trong `includes/module_config.php` bằng mảng rỗng. Hai đường dẫn đó hiện đã dùng truy vấn/phân trang riêng nên các dòng minh họa này không còn được hiển thị. Giữ phần metadata của module.

Có thể xét bỏ thêm 4 dòng `candidates` không dùng trong `includes/sample_data.php` sau khi kiểm tra lại các nhánh liên quan. Không xóa toàn bộ file vì các module khác còn dùng dữ liệu mẫu trong đó.

Không đề xuất xóa bảng hoặc xóa học sinh/nguyện vọng trong database. Tất cả 1.770 học sinh và 5.304 nguyện vọng hiện đều có mục đích rõ ràng: 1.750/5.250 cho demo lớn và 20/54 cho kiểm thử nghiệp vụ cũ.

Các bảng tạm `demo1750_*` chỉ là vùng staging của phiên import; chúng không thay thế hay xóa bảng dữ liệu ứng dụng. Procedure tạm của importer được dọn sau khi dùng; không đụng các procedure nghiệp vụ.
