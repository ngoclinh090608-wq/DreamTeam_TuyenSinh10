-- Demo-only attribution: Phan Thùy Dương is the simulated grading operator.
-- Does not identify a real historical operator or change scores/timestamps.
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dreamteam_tuyensinh10_demo;
START TRANSACTION;
UPDATE KetQuaChamThi k
JOIN BaiThi b ON b.maBaiThi = k.maBaiThi
SET k.nguoiNhap = 'Phan Thùy Dương'
WHERE b.maKyThi = 'KTDEMO26'
  AND k.nguoiNhap = 'Bộ dữ liệu DEMO (tạo tự động)';
COMMIT;
