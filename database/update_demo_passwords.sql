-- Khôi phục 11 tài khoản DEMO về mật khẩu ban đầu dreamteam2026 (bcrypt).
-- Nhập file này trong phpMyAdmin khi database đã được cài trước đó.
-- Không nhập lại install.sql và không xóa database.
-- Chỉ khớp đúng mã + tên tài khoản mẫu; không thay đổi vai trò/trạng thái tài khoản.
USE dreamteam_tuyensinh10;
START TRANSACTION;
UPDATE TaiKhoan
SET matKhau = '$2y$10$m.1I1MF8uLV9orbw6fwRhOJ81f58KqrKIIF/xpGklJ2loInjp9hpy', soLanSai = 0, khoaDen = NULL
WHERE (maTaiKhoan, tenDangNhap) IN (
  ('TK01', 'quantri'),
  ('TK02', 'bantuyensinh'),
  ('TK03', 'hdtuyensinh'),
  ('TK04', 'hdcoithi'),
  ('TK05', 'hdchamthi'),
  ('TK06', 'catphach'),
  ('TK07', 'hdphuckhao'),
  ('TK08', 'hdrade'),
  ('TK09', 'gvcn'),
  ('TK10', 'SBD0001'),
  ('TK11', 'SBD0002')
);
COMMIT;
