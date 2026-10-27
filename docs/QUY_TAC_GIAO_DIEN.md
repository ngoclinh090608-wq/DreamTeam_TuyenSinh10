# Quy tắc thống nhất UI cho cả nhóm

- **Màu chính:** xanh `#1265b0`, xanh đậm `#102a4c`, màu nhấn vàng `#ffbe59`, nền `#f6f8fc`.
- **Font:** Be Vietnam Pro cho giao diện tiếng Việt (có font dự phòng khi offline).
- **Trang công khai:** header ngang, menu lịch/chỉ tiêu/trường/hướng dẫn, footer đồng bộ.
- **Trang quản lý:** sidebar 4 nhóm, topbar, thông báo dữ liệu mẫu và các thẻ/bảng/form thống nhất.
- **Trang bắt buộc thống nhất:** `auth/login.php`, `results/index.php`, `admin/index.php`.
- **Phần của Trần Thị Cẩm Vân:** `papers/`, `councils/`, `assignments/`, `staff/`; giao diện UC11 nằm ở `assignments/index.php`.
- **Tất cả form đang là demo:** chưa nối SQL, không gọi đăng nhập thật hoặc xử lý xét tuyển.
- **Chỉnh sửa màn hình riêng:** giữ nguyên `header.php`/`footer.php`, chỉnh `includes/module_page.php` hoặc tách view module, không sửa sidebar riêng.
- **Lưu ý:** Hệ tuyển sinh thực tế Cần Thơ có nghiệp vụ khác, nhưng Dream Team dùng NV1–NV3 theo tài liệu của nhóm.
