-- Migration: thêm cột lopChuyen vào bảng NguyenVong cho tính năng quản lý nguyện vọng chuyên.
-- Chạy an toàn trên máy đã có dữ liệu; cột mới mặc định 'Thường'.
SET NAMES utf8mb4;
USE dreamteam_tuyensinh10_demo;
ALTER TABLE NguyenVong ADD COLUMN IF NOT EXISTS lopChuyen VARCHAR(50) NOT NULL DEFAULT 'Thường';
