# Giao diện cổng tuyển sinh lớp 10 — nhánh linh

## Phạm vi

Giao diện dùng #3F7CA5, #7FA6C9, #CCE1F3, #F7F4F4 và #FFFFFF; menu ngang xanh, nội dung trắng, biểu tượng sách SVG, các form và bảng có cùng cách trình bày. Không dùng tên DreamTeam làm tên hiển thị.

Website An Giang không truy cập được do proxy trả HTTP 403. Ảnh người dùng cung cấp thể hiện menu hỗ trợ nền trắng; đoạn HTML cung cấp là khung tải ứng dụng, không chứa nội dung trang chủ. Phần bố cục còn lại được xây dựng từ yêu cầu của người dùng, không phải bản sao đã kiểm chứng của toàn bộ website An Giang. Không dùng mã nguồn, logo hay analytics của website tham khảo.

## Các file giao diện

- `index.php`: thông báo, tra cứu mẫu, liên kết chức năng, lộ trình, trường, hướng dẫn và FAQ.
- `includes/header.php`, `includes/footer.php`, `includes/sidebar.php`: nhận diện, điều hướng và khung dùng chung.
- `assets/css/portal.css`: bảng màu và responsive cho trang công khai, đăng nhập, dashboard, UC12–UC15. Nạp sau CSS cũ để giữ các thành phần nghiệp vụ sẵn có.
- `assets/js/app.js`: menu mobile/Escape, trạng thái ARIA, bộ lọc trường và chuyển yêu cầu tra cứu mẫu từ trang chủ.
- `auth/login.php`, `results/index.php`: thay nội dung trình bày; giữ nguyên xử lý đăng nhập và tra cứu DEMO001.
- `config/app.php`, `includes/module_config.php`: tên hiển thị, mô tả hệ thống.
- `assets/images/logo.svg`: biểu tượng sách của dự án; nguồn ảnh xem `assets/images/SOURCES.md`.
- `schools/danh-sach.php`, `admission-info/huong-dan.php`: trang xem công khai mới. Các đường dẫn quản trị cũ vẫn giữ nguyên phân quyền.
- `includes/portal_schools.php`: hiển thị lại dữ liệu trường từ hàm chỉ đọc `live_home_schools()` đang có; không thêm SQL hoặc API mới.
- `includes/portal_guide.php`: nội dung hướng dẫn dùng chung.

Không thay đổi các file nghiệp vụ trong `services/`, `grading/`, `publication/`, database, CSRF, phân quyền hay transaction. Thông tin trường và kết quả trong môi trường này là dữ liệu thực hành. Trang tra cứu công khai vẫn chỉ hiển thị mẫu DEMO001; không mô tả nó là tra cứu kết quả thi thật.

## Chạy và kiểm tra

XAMPP: giữ nguyên thư mục `DreamTeam_TuyenSinh10` dưới `htdocs`; bật Apache và MariaDB theo README. PHP server ở thư mục dự án: `php -S 127.0.0.1:8080 router.php`. Các đường dẫn mới được tạo bằng `url()` để hỗ trợ cả root và thư mục con.

Kiểm tra trong môi trường đám mây:

- 55 file PHP hợp lệ; `node --check assets/js/app.js` và `git diff --check` đạt.
- Bộ kiểm thử sẵn có: 163/163 ca đạt trên container MariaDB test riêng `dreamteam-ui-test-db`, không dùng database phát triển. Bản sao/khôi phục dữ liệu của test được bật.
- Chromium: trang chủ, thông tin, trường, đăng nhập, tra cứu tại 360/390/768/1024px và trang chủ tại 1440px; không tràn ngang, không lỗi JavaScript. Tra cứu DEMO001, bộ lọc không kết quả/khôi phục và đóng/mở menu bằng Escape hoạt động.
- Dashboard/UC12–UC14: 1440/390px. Trang quản lý trường/thông tin và UC15: 1440/390px, dùng đúng tài khoản theo vai trò. Sidebar mobile mở/đóng được. UC15 trong kiểm tra trình duyệt chỉ đọc; các thao tác ghi được kiểm tra bởi suite trên database test riêng.
- Trang công khai, CSS và SVG trả HTTP 200 dưới tiền tố `/DreamTeam_TuyenSinh10/`; form tra cứu và URL CSS có tiền tố chính xác. Đây là kiểm tra bằng PHP server trong thư mục con, chưa chạy Apache/XAMPP Windows trực tiếp.

## Còn thiếu

Ảnh chụp thật và ghi nhận giấy phép chưa hoàn tất vì nguồn ảnh bị chặn mạng. Không thay bằng ảnh AI, ảnh watermark hoặc nguồn chưa xác minh. Quyền truy cập website tham khảo và hai domain Wikimedia đã được lưu trong bản nháp cấu hình môi trường; bản nháp chưa được áp dụng cho phiên hiện tại. Cần lưu cấu hình qua Environment settings và Publish, sau đó thử lại việc tải ảnh và cập nhật SOURCES.md.

Giao diện được phát triển trên nhánh `linh`. Bản tích hợp `integrate-linh-vy` kết hợp giao diện này với các nghiệp vụ mới nhất của `vy` để đưa vào `main` qua Pull Request.

## Kiểm chứng khi đồng bộ sang vy

- Giữ nguyên toàn bộ code riêng UC05–UC08, SQL, service, phân quyền và xử lý giao dịch của vy; diff so với đầu nhánh vy chỉ chứa 17 file giao diện/tài liệu.
- 62 file PHP hợp lệ; cú pháp JavaScript và kiểm tra khoảng trắng Git đạt.
- Hai database test độc lập được tạo cho vy gốc và vy có giao diện mới. Cả hai đều đạt 163/163 ca UC09–UC15.
- Bộ UC05–UC08 in 31 kết quả: 19 đạt, 12 lỗi trên cả hai bản; kết quả từng ca giống nhau. Phần tổng kết do script tự in chỉ đếm 9 ca vì các hàm trong pipeline chạy ở subshell; không coi con số đó là tổng số ca đã thực thi.
- UC05 gốc gọi `grading_ds_mon()` trong khi service cung cấp `grading_ds_mon_thi()`. Một số ca UC06/UC07 không tìm được thông báo thành công, dù các kiểm tra dữ liệu tương ứng đạt. Không sửa các lỗi nghiệp vụ/test có sẵn trong lần đồng bộ giao diện này.

## Kiểm thử bản tích hợp linh + vy

Bản tích hợp đã gộp `origin/linh` (`ad4e689`) và `origin/vy` (`5bb33af`) trên nền `origin/main` (`e5274e3`). Chỉ có xung đột tài liệu; đã giữ nội dung đầy đủ hơn từ vy. Không thêm hoặc thay đổi tài liệu Word/VPP.

- 62 file PHP hợp lệ; hai file SQL sinh tự động khớp nguồn.
- MariaDB test mới độc lập: UC09–UC15 đạt 163/163; UC05–UC08 đạt 58/58; cả hai script thoát mã 0.
- Mật khẩu demo giữ nguyên `dreamteam2026`.
- Các lỗi UC05–UC08 ở lần kiểm tra lịch sử phía trên đã được bản sửa của vy khắc phục; không còn xuất hiện trong lần chạy tích hợp này.
