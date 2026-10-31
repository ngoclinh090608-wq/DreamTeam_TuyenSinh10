# Nguồn dữ liệu hiện hành

Nguồn: bản xuất phpMyAdmin `dreamteam_tuyensinh10_demo.sql` do chủ dự án cung cấp ngày 31/10/2026. SHA-256 file gốc: `675a3c2c42bdcaca16f6070cb1b47db5f0e845b26043fc6f8f716a7b63868a9d`.

File trong repository giữ dữ liệu của bản gốc; chỉ bổ sung CREATE DATABASE/USE để chọn đúng database demo, chuẩn hóa chữ hoa/thường tên bảng/view theo schema PHP và bỏ DEFINER root@localhost. Các file gốc người dùng gửi được giữ ngoài checkout trong phiên làm việc, không bị xóa.

Excel trong `source/` là nguồn giai đoạn đầu cho 1.750 học sinh/5.250 nguyện vọng, không chứa toàn bộ trạng thái bài thi/điểm hiện hành. Không dùng để ghi đè database đã được cập nhật.
