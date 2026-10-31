# Nguồn dữ liệu

Workbook người dùng cung cấp: `DuLieuMau_5Truong_1750HocSinh.xlsx`. SHA-256: `21802353b2c975ab74cc1143fbada28954a60a1d5b55b2bf77c02d639de86f57`. Các sheet xác nhận đây là dữ liệu mô phỏng, không phải học sinh thật.

Nguồn chính: 5 dòng Truong_THCS, 1.750 dòng ThiSinh, 5.250 dòng NguyenVong, 5 dòng ChiTieu_THPT. Mã, tên tiếng Việt, ngày sinh, số báo danh và nguyện vọng được giữ theo Excel. ZIP được dùng để đối chiếu, không chạy trực tiếp vì các INSERT trong đó chưa idempotent.

Tạo lại SQL (chỉ khi thay nguồn/schema): cài `openpyxl`, chạy `python3 database/demo/build_demo.py`. Không đổi bộ seed nhỏ của dự án.
