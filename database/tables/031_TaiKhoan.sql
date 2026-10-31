-- Bảng TaiKhoan (bổ sung sau báo cáo Tuần 08): tài khoản đăng nhập và vai trò phân quyền.
-- vaiTro ứng với 10 actor trong báo cáo giữa kỳ. Mật khẩu lưu dạng băm bcrypt (password_hash), không lưu chữ thường.
-- Tài khoản cán bộ có thể nối CanBo, tài khoản học sinh nối ThiSinh.
CREATE TABLE TaiKhoan (
  maTaiKhoan VARCHAR(20) NOT NULL,
  tenDangNhap VARCHAR(50) NOT NULL,
  matKhau VARCHAR(255) NOT NULL,
  hoTen VARCHAR(100) NOT NULL,
  vaiTro VARCHAR(30) NOT NULL,
  maCanBo VARCHAR(20) NULL,
  maThiSinh VARCHAR(20) NULL,
  trangThai VARCHAR(20) NOT NULL DEFAULT 'Hoạt động',
  soLanSai INT NOT NULL DEFAULT 0,
  khoaDen DATETIME NULL,
  lanDangNhapCuoi DATETIME NULL,
  ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (maTaiKhoan),
  UNIQUE KEY uq_taikhoan_ten (tenDangNhap),
  CONSTRAINT ck_taikhoan_vaitro CHECK (vaiTro IN ('HOC_SINH','GVCN','BAN_TUYEN_SINH','HD_TUYEN_SINH','HD_COI_THI','HD_CHAM_THI','CAT_PHACH','HD_PHUC_KHAO','HD_RA_DE','QUAN_TRI')),
  CONSTRAINT ck_taikhoan_trangthai CHECK (trangThai IN ('Hoạt động','Bị khóa')),
  CONSTRAINT ck_taikhoan_solansai CHECK (soLanSai >= 0),
  CONSTRAINT fk_taikhoan_canbo FOREIGN KEY (maCanBo) REFERENCES CanBo(maCanBo),
  CONSTRAINT fk_taikhoan_thisinh FOREIGN KEY (maThiSinh) REFERENCES ThiSinh(maThiSinh)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
