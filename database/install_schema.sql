-- install.sql được tạo tự động bởi build.php. Không sửa tay.
CREATE DATABASE IF NOT EXISTS dreamteam_tuyensinh10 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dreamteam_tuyensinh10;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- Bảng KhuVuc (theo báo cáo Tuần 08)
CREATE TABLE KhuVuc (
  maKhuVuc VARCHAR(20) NOT NULL,
  tenKhuVuc VARCHAR(100) NOT NULL,
  quanHuyen VARCHAR(100) NULL,
  PRIMARY KEY (maKhuVuc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng TruongTHCS (theo báo cáo Tuần 08)
CREATE TABLE TruongTHCS (
  maTruongTHCS VARCHAR(20) NOT NULL,
  tenTruongTHCS VARCHAR(100) NOT NULL,
  diaChi VARCHAR(255) NULL,
  quanHuyen VARCHAR(100) NULL,
  maKhuVuc VARCHAR(20) NOT NULL,
  PRIMARY KEY (maTruongTHCS),
  CONSTRAINT fk_thcs_khuvuc FOREIGN KEY (maKhuVuc) REFERENCES KhuVuc(maKhuVuc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng TruongTHPT (theo báo cáo Tuần 08)
CREATE TABLE TruongTHPT (
  maTruongTHPT VARCHAR(20) NOT NULL,
  tenTruongTHPT VARCHAR(100) NOT NULL,
  maKhuVuc VARCHAR(20) NOT NULL,
  PRIMARY KEY (maTruongTHPT),
  CONSTRAINT fk_thpt_khuvuc FOREIGN KEY (maKhuVuc) REFERENCES KhuVuc(maKhuVuc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng DotTuyenSinh (theo báo cáo Tuần 08)
CREATE TABLE DotTuyenSinh (
  maDot VARCHAR(20) NOT NULL,
  tenDot VARCHAR(100) NOT NULL,
  namTuyenSinh INT NOT NULL,
  PRIMARY KEY (maDot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng ThongTinTuyenSinh (theo báo cáo Tuần 08)
CREATE TABLE ThongTinTuyenSinh (
  maThongTin VARCHAR(20) NOT NULL,
  chiTieuTuyenSinh INT NOT NULL DEFAULT 0,
  heDaoTao VARCHAR(50) NOT NULL,
  phuongThucTuyenSinh VARCHAR(100) NOT NULL,
  hocPhi DECIMAL(18,2) NOT NULL DEFAULT 0,
  noiDungChiTiet TEXT NULL,
  trangThaiCongBo VARCHAR(50) NOT NULL DEFAULT 'Chưa công bố',
  maTruongTHPT VARCHAR(20) NOT NULL,
  maDot VARCHAR(20) NOT NULL,
  PRIMARY KEY (maThongTin),
  CONSTRAINT ck_ttts_chitieu CHECK (chiTieuTuyenSinh >= 0),
  CONSTRAINT ck_ttts_hocphi CHECK (hocPhi >= 0),
  CONSTRAINT fk_ttts_thpt FOREIGN KEY (maTruongTHPT) REFERENCES TruongTHPT(maTruongTHPT),
  CONSTRAINT fk_ttts_dot FOREIGN KEY (maDot) REFERENCES DotTuyenSinh(maDot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng KyThi (theo báo cáo Tuần 08)
CREATE TABLE KyThi (
  maKyThi VARCHAR(20) NOT NULL,
  tenKyThi VARCHAR(150) NOT NULL,
  thoiGianKetThuc DATETIME NOT NULL,
  maDot VARCHAR(20) NOT NULL,
  PRIMARY KEY (maKyThi),
  CONSTRAINT fk_kythi_dot FOREIGN KEY (maDot) REFERENCES DotTuyenSinh(maDot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng MonThi (theo báo cáo Tuần 08)
CREATE TABLE MonThi (
  maMonThi VARCHAR(20) NOT NULL,
  tenMonThi VARCHAR(100) NOT NULL,
  thangDiem DECIMAL(5,2) NOT NULL,
  PRIMARY KEY (maMonThi),
  CONSTRAINT ck_monthi_thangdiem CHECK (thangDiem > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng DiemThi (theo báo cáo Tuần 08)
CREATE TABLE DiemThi (
  maDiemThi VARCHAR(20) NOT NULL,
  tenDiemThi VARCHAR(150) NOT NULL,
  maTruongTHCS VARCHAR(20) NULL,
  maTruongTHPT VARCHAR(20) NULL,
  maKyThi VARCHAR(20) NOT NULL,
  PRIMARY KEY (maDiemThi),
  CONSTRAINT fk_diemthi_thcs FOREIGN KEY (maTruongTHCS) REFERENCES TruongTHCS(maTruongTHCS),
  CONSTRAINT fk_diemthi_thpt FOREIGN KEY (maTruongTHPT) REFERENCES TruongTHPT(maTruongTHPT),
  CONSTRAINT fk_diemthi_kythi FOREIGN KEY (maKyThi) REFERENCES KyThi(maKyThi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng PhongThi (theo báo cáo Tuần 08)
CREATE TABLE PhongThi (
  maPhong VARCHAR(20) NOT NULL,
  tenPhong VARCHAR(100) NOT NULL,
  sucChua INT NOT NULL,
  maDiemThi VARCHAR(20) NOT NULL,
  PRIMARY KEY (maPhong),
  CONSTRAINT ck_phongthi_succhua CHECK (sucChua > 0),
  CONSTRAINT fk_phongthi_diemthi FOREIGN KEY (maDiemThi) REFERENCES DiemThi(maDiemThi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng ThiSinh (theo báo cáo Tuần 08)
CREATE TABLE ThiSinh (
  maThiSinh VARCHAR(20) NOT NULL,
  maHoSo VARCHAR(20) NOT NULL,
  hoTen VARCHAR(100) NOT NULL,
  ngaySinh DATE NOT NULL,
  soBaoDanh VARCHAR(20) NULL,
  dieuKienDuThi TINYINT(1) NOT NULL DEFAULT 0,
  trangThaiDangKyDuThi VARCHAR(50) NOT NULL DEFAULT 'Chưa duyệt',
  maTruongTHCS VARCHAR(20) NOT NULL,
  PRIMARY KEY (maThiSinh),
  UNIQUE KEY uq_thisinh_mahoso (maHoSo),
  UNIQUE KEY uq_thisinh_sbd (soBaoDanh),
  CONSTRAINT ck_thisinh_trangthai CHECK (trangThaiDangKyDuThi IN ('Chưa duyệt','Đã duyệt','Từ chối')),
  CONSTRAINT fk_thisinh_thcs FOREIGN KEY (maTruongTHCS) REFERENCES TruongTHCS(maTruongTHCS)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng HoiDongThi (theo báo cáo Tuần 08)
CREATE TABLE HoiDongThi (
  maHoiDongThi VARCHAR(20) NOT NULL,
  tenHoiDongThi VARCHAR(100) NOT NULL,
  thongTinHoiDong VARCHAR(255) NULL,
  loaiHoiDong VARCHAR(50) NOT NULL,
  maKyThi VARCHAR(20) NOT NULL,
  PRIMARY KEY (maHoiDongThi),
  CONSTRAINT fk_hdt_kythi FOREIGN KEY (maKyThi) REFERENCES KyThi(maKyThi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng CanBo (theo báo cáo Tuần 08)
CREATE TABLE CanBo (
  maCanBo VARCHAR(20) NOT NULL,
  hoTen VARCHAR(100) NOT NULL,
  soDienThoai VARCHAR(15) NULL,
  email VARCHAR(255) NULL,
  chucVu VARCHAR(100) NULL,
  maTruongTHCS VARCHAR(20) NULL,
  maTruongTHPT VARCHAR(20) NULL,
  maHoiDongThi VARCHAR(20) NULL,
  PRIMARY KEY (maCanBo),
  CONSTRAINT fk_canbo_thcs FOREIGN KEY (maTruongTHCS) REFERENCES TruongTHCS(maTruongTHCS),
  CONSTRAINT fk_canbo_thpt FOREIGN KEY (maTruongTHPT) REFERENCES TruongTHPT(maTruongTHPT),
  CONSTRAINT fk_canbo_hdt FOREIGN KEY (maHoiDongThi) REFERENCES HoiDongThi(maHoiDongThi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng PhanCongCoiThi (theo báo cáo Tuần 08)
CREATE TABLE PhanCongCoiThi (
  maPhanCong VARCHAR(20) NOT NULL,
  vaiTro VARCHAR(50) NOT NULL,
  maPhong VARCHAR(20) NOT NULL,
  maMonThi VARCHAR(20) NOT NULL,
  maCanBo VARCHAR(20) NOT NULL,
  PRIMARY KEY (maPhanCong),
  UNIQUE KEY uq_pccoithi (maPhong, maMonThi, maCanBo),
  CONSTRAINT fk_pccoi_phong FOREIGN KEY (maPhong) REFERENCES PhongThi(maPhong),
  CONSTRAINT fk_pccoi_mon FOREIGN KEY (maMonThi) REFERENCES MonThi(maMonThi),
  CONSTRAINT fk_pccoi_canbo FOREIGN KEY (maCanBo) REFERENCES CanBo(maCanBo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng PhanPhongThi (theo báo cáo Tuần 08)
CREATE TABLE PhanPhongThi (
  maPhanPhong VARCHAR(20) NOT NULL,
  maThiSinh VARCHAR(20) NOT NULL,
  maPhong VARCHAR(20) NOT NULL,
  ngayPhan DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  trangThai VARCHAR(30) NOT NULL,
  PRIMARY KEY (maPhanPhong),
  CONSTRAINT ck_ppt_trangthai CHECK (trangThai IN ('Đã phân','Đã chuyển phòng','Đã hủy')),
  CONSTRAINT fk_ppt_thisinh FOREIGN KEY (maThiSinh) REFERENCES ThiSinh(maThiSinh),
  CONSTRAINT fk_ppt_phong FOREIGN KEY (maPhong) REFERENCES PhongThi(maPhong)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng PhanCongRaDe (theo báo cáo Tuần 08)
CREATE TABLE PhanCongRaDe (
  maPhanCong VARCHAR(20) NOT NULL,
  maKyThi VARCHAR(20) NOT NULL,
  maMonThi VARCHAR(20) NOT NULL,
  maCanBo VARCHAR(20) NOT NULL,
  thoiGianThucHien DATETIME NULL,
  thongTinNhiemVu VARCHAR(255) NOT NULL,
  PRIMARY KEY (maPhanCong),
  CONSTRAINT fk_pcrd_kythi FOREIGN KEY (maKyThi) REFERENCES KyThi(maKyThi),
  CONSTRAINT fk_pcrd_mon FOREIGN KEY (maMonThi) REFERENCES MonThi(maMonThi),
  CONSTRAINT fk_pcrd_canbo FOREIGN KEY (maCanBo) REFERENCES CanBo(maCanBo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng LoBaiThi (theo báo cáo Tuần 08)
CREATE TABLE LoBaiThi (
  maLoBaiThi VARCHAR(20) NOT NULL,
  thongTinLoBaiThi VARCHAR(255) NOT NULL,
  maMonThi VARCHAR(20) NOT NULL,
  maPhong VARCHAR(20) NOT NULL,
  PRIMARY KEY (maLoBaiThi),
  CONSTRAINT fk_lobt_mon FOREIGN KEY (maMonThi) REFERENCES MonThi(maMonThi),
  CONSTRAINT fk_lobt_phong FOREIGN KEY (maPhong) REFERENCES PhongThi(maPhong)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng BaiThi (theo báo cáo Tuần 08)
CREATE TABLE BaiThi (
  maBaiThi VARCHAR(20) NOT NULL,
  maKyThi VARCHAR(20) NOT NULL,
  maMonThi VARCHAR(20) NOT NULL,
  maThiSinh VARCHAR(20) NOT NULL,
  maLoBaiThi VARCHAR(20) NULL,
  soPhach VARCHAR(20) NULL,
  trangThaiCham VARCHAR(50) NOT NULL DEFAULT 'Chưa chấm',
  PRIMARY KEY (maBaiThi),
  UNIQUE KEY uq_baithi_sophach (soPhach),
  UNIQUE KEY uq_baithi_ts_mon_ky (maThiSinh, maMonThi, maKyThi),
  CONSTRAINT fk_baithi_kythi FOREIGN KEY (maKyThi) REFERENCES KyThi(maKyThi),
  CONSTRAINT fk_baithi_mon FOREIGN KEY (maMonThi) REFERENCES MonThi(maMonThi),
  CONSTRAINT fk_baithi_thisinh FOREIGN KEY (maThiSinh) REFERENCES ThiSinh(maThiSinh),
  CONSTRAINT fk_baithi_lo FOREIGN KEY (maLoBaiThi) REFERENCES LoBaiThi(maLoBaiThi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng KetQuaChamThi (theo báo cáo Tuần 08)
CREATE TABLE KetQuaChamThi (
  maKetQuaChamThi VARCHAR(20) NOT NULL,
  diemThi DECIMAL(5,2) NOT NULL,
  nguoiNhap VARCHAR(100) NOT NULL,
  thoiDiemNhap DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  trangThaiCongBo VARCHAR(50) NOT NULL DEFAULT 'Chưa công bố',
  maBaiThi VARCHAR(20) NOT NULL,
  PRIMARY KEY (maKetQuaChamThi),
  UNIQUE KEY uq_kqct_baithi (maBaiThi),
  CONSTRAINT ck_kqct_diem CHECK (diemThi >= 0),
  CONSTRAINT fk_kqct_baithi FOREIGN KEY (maBaiThi) REFERENCES BaiThi(maBaiThi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng LichSuChinhSuaDiem (theo báo cáo Tuần 08)
CREATE TABLE LichSuChinhSuaDiem (
  maLichSu VARCHAR(20) NOT NULL,
  diemTruocChinhSua DECIMAL(5,2) NOT NULL,
  diemSauChinhSua DECIMAL(5,2) NOT NULL,
  thoiGianChinhSua DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  nguoiThucHien VARCHAR(100) NOT NULL,
  lyDoChinhSua VARCHAR(255) NOT NULL,
  maKetQuaChamThi VARCHAR(20) NOT NULL,
  PRIMARY KEY (maLichSu),
  CONSTRAINT ck_lscsd_truoc CHECK (diemTruocChinhSua >= 0),
  CONSTRAINT ck_lscsd_sau CHECK (diemSauChinhSua >= 0),
  CONSTRAINT fk_lscsd_kqct FOREIGN KEY (maKetQuaChamThi) REFERENCES KetQuaChamThi(maKetQuaChamThi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng QuyDinhNguyenVong (theo báo cáo Tuần 08)
CREATE TABLE QuyDinhNguyenVong (
  maQuyDinh VARCHAR(20) NOT NULL,
  namTuyenSinh INT NOT NULL,
  soLuongNguyenVongToiDa TINYINT NOT NULL DEFAULT 3,
  thoiGianBatDau DATETIME NOT NULL,
  thoiGianKetThuc DATETIME NOT NULL,
  ghiChu VARCHAR(255) NULL,
  PRIMARY KEY (maQuyDinh),
  CONSTRAINT ck_qdnv_soluong CHECK (soLuongNguyenVongToiDa BETWEEN 1 AND 3),
  CONSTRAINT ck_qdnv_thoigian CHECK (thoiGianKetThuc > thoiGianBatDau)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng NguyenVong (theo báo cáo Tuần 08)
CREATE TABLE NguyenVong (
  maNguyenVong VARCHAR(20) NOT NULL,
  maThiSinh VARCHAR(20) NOT NULL,
  maTruongTHPT VARCHAR(20) NOT NULL,
  maQuyDinh VARCHAR(20) NOT NULL,
  thuTuNguyenVong TINYINT NOT NULL,
  ngayDangKy DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  trangThaiNguyenVong VARCHAR(30) NOT NULL DEFAULT 'Chờ xử lý',
  PRIMARY KEY (maNguyenVong),
  UNIQUE KEY uq_nv_thutu (maThiSinh, thuTuNguyenVong),
  CONSTRAINT ck_nv_thutu CHECK (thuTuNguyenVong BETWEEN 1 AND 3),
  CONSTRAINT fk_nv_thisinh FOREIGN KEY (maThiSinh) REFERENCES ThiSinh(maThiSinh),
  CONSTRAINT fk_nv_thpt FOREIGN KEY (maTruongTHPT) REFERENCES TruongTHPT(maTruongTHPT),
  CONSTRAINT fk_nv_quydinh FOREIGN KEY (maQuyDinh) REFERENCES QuyDinhNguyenVong(maQuyDinh)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng YeuCauPhucKhao (theo báo cáo Tuần 08)
CREATE TABLE YeuCauPhucKhao (
  maYeuCau VARCHAR(20) NOT NULL,
  maThiSinh VARCHAR(20) NOT NULL,
  maBaiThi VARCHAR(20) NOT NULL,
  ngayYeuCau DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  lyDo VARCHAR(500) NULL,
  trangThai VARCHAR(30) NOT NULL DEFAULT 'Chờ xử lý',
  PRIMARY KEY (maYeuCau),
  UNIQUE KEY uq_ycpk_baithi (maBaiThi),
  CONSTRAINT fk_ycpk_thisinh FOREIGN KEY (maThiSinh) REFERENCES ThiSinh(maThiSinh),
  CONSTRAINT fk_ycpk_baithi FOREIGN KEY (maBaiThi) REFERENCES BaiThi(maBaiThi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng KetQuaPhucKhao (theo báo cáo Tuần 08)
CREATE TABLE KetQuaPhucKhao (
  maKetQua VARCHAR(20) NOT NULL,
  maYeuCau VARCHAR(20) NOT NULL,
  diemTruocPhucKhao DECIMAL(5,2) NOT NULL,
  diemSauPhucKhao DECIMAL(5,2) NOT NULL,
  ketLuan VARCHAR(100) NOT NULL,
  ngayHoanThanh DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  maCanBo VARCHAR(20) NOT NULL,
  PRIMARY KEY (maKetQua),
  UNIQUE KEY uq_kqpk_yeucau (maYeuCau),
  CONSTRAINT ck_kqpk_truoc CHECK (diemTruocPhucKhao >= 0),
  CONSTRAINT ck_kqpk_sau CHECK (diemSauPhucKhao >= 0),
  CONSTRAINT fk_kqpk_yeucau FOREIGN KEY (maYeuCau) REFERENCES YeuCauPhucKhao(maYeuCau),
  CONSTRAINT fk_kqpk_canbo FOREIGN KEY (maCanBo) REFERENCES CanBo(maCanBo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng MinhChungPhucKhao (theo báo cáo Tuần 08)
CREATE TABLE MinhChungPhucKhao (
  maMinhChung VARCHAR(20) NOT NULL,
  thongTinMinhChung VARCHAR(255) NOT NULL,
  maKetQua VARCHAR(20) NOT NULL,
  PRIMARY KEY (maMinhChung),
  CONSTRAINT fk_mcpk_kq FOREIGN KEY (maKetQua) REFERENCES KetQuaPhucKhao(maKetQua)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng BienBanPhucKhao (theo báo cáo Tuần 08)
CREATE TABLE BienBanPhucKhao (
  maBienBan VARCHAR(20) NOT NULL,
  maKetQua VARCHAR(20) NOT NULL,
  noiDung VARCHAR(1000) NOT NULL,
  ngayLap DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  nguoiLap VARCHAR(100) NOT NULL,
  duongDanTep VARCHAR(500) NULL,
  PRIMARY KEY (maBienBan),
  CONSTRAINT fk_bbpk_kq FOREIGN KEY (maKetQua) REFERENCES KetQuaPhucKhao(maKetQua)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng KetQuaXetTuyen (theo báo cáo Tuần 08)
CREATE TABLE KetQuaXetTuyen (
  maKetQua VARCHAR(20) NOT NULL,
  maThiSinh VARCHAR(20) NOT NULL,
  maNguyenVong VARCHAR(20) NOT NULL,
  diemXetTuyen DECIMAL(5,2) NULL,
  trangThaiXetTuyen VARCHAR(30) NOT NULL DEFAULT 'Chưa xét',
  maTruongTHPT VARCHAR(20) NULL,
  PRIMARY KEY (maKetQua),
  UNIQUE KEY uq_kqxt_nguyenvong (maNguyenVong),
  CONSTRAINT ck_kqxt_diem CHECK (diemXetTuyen IS NULL OR diemXetTuyen >= 0),
  CONSTRAINT ck_kqxt_trangthai CHECK (trangThaiXetTuyen IN ('Chưa xét','Trúng tuyển','Không trúng tuyển')),
  CONSTRAINT fk_kqxt_thisinh FOREIGN KEY (maThiSinh) REFERENCES ThiSinh(maThiSinh),
  CONSTRAINT fk_kqxt_nv FOREIGN KEY (maNguyenVong) REFERENCES NguyenVong(maNguyenVong),
  CONSTRAINT fk_kqxt_thpt FOREIGN KEY (maTruongTHPT) REFERENCES TruongTHPT(maTruongTHPT)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng TinhTrangNhapHoc (theo báo cáo Tuần 08)
CREATE TABLE TinhTrangNhapHoc (
  maTinhTrang VARCHAR(20) NOT NULL,
  maKetQua VARCHAR(20) NOT NULL,
  trangThaiNhapHoc VARCHAR(40) NOT NULL DEFAULT 'Chưa xác nhận',
  thoiGianXacNhanNhapHoc DATETIME NULL,
  PRIMARY KEY (maTinhTrang),
  UNIQUE KEY uq_ttnh_ketqua (maKetQua),
  CONSTRAINT ck_ttnh_trangthai CHECK (trangThaiNhapHoc IN ('Chưa xác nhận','Đã xác nhận','Không nhập học')),
  CONSTRAINT fk_ttnh_kqxt FOREIGN KEY (maKetQua) REFERENCES KetQuaXetTuyen(maKetQua)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng LichSuCapNhatNhapHoc (theo báo cáo Tuần 08)
CREATE TABLE LichSuCapNhatNhapHoc (
  maCapNhat VARCHAR(20) NOT NULL,
  maTinhTrang VARCHAR(20) NOT NULL,
  noiDungCapNhat VARCHAR(500) NOT NULL,
  thoiGianCapNhat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  nguoiCapNhat VARCHAR(100) NULL,
  PRIMARY KEY (maCapNhat),
  CONSTRAINT fk_lscnnh_tt FOREIGN KEY (maTinhTrang) REFERENCES TinhTrangNhapHoc(maTinhTrang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng DanhSachKetQuaTuyenSinh (theo báo cáo Tuần 08)
CREATE TABLE DanhSachKetQuaTuyenSinh (
  maDanhSach VARCHAR(20) NOT NULL,
  namTuyenSinh INT NOT NULL,
  maDot VARCHAR(20) NOT NULL,
  trangThaiPheDuyet VARCHAR(30) NOT NULL DEFAULT 'Chưa phê duyệt',
  trangThaiCongBo VARCHAR(30) NOT NULL DEFAULT 'Chưa công bố',
  PRIMARY KEY (maDanhSach),
  CONSTRAINT fk_dskq_dot FOREIGN KEY (maDot) REFERENCES DotTuyenSinh(maDot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng KetQuaTuyenSinh (theo báo cáo Tuần 08)
CREATE TABLE KetQuaTuyenSinh (
  maKetQua VARCHAR(20) NOT NULL,
  maDanhSach VARCHAR(20) NOT NULL,
  maKetQuaXetTuyen VARCHAR(20) NOT NULL,
  PRIMARY KEY (maKetQua),
  UNIQUE KEY uq_kqts_ds_kqxt (maDanhSach, maKetQuaXetTuyen),
  CONSTRAINT fk_kqts_ds FOREIGN KEY (maDanhSach) REFERENCES DanhSachKetQuaTuyenSinh(maDanhSach),
  CONSTRAINT fk_kqts_kqxt FOREIGN KEY (maKetQuaXetTuyen) REFERENCES KetQuaXetTuyen(maKetQua)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- 4.5. vw_PhanPhongThiSinh (theo báo cáo Tuần 08)
-- Tra cứu phòng thi được phân cho từng thí sinh.
CREATE OR REPLACE VIEW vw_PhanPhongThiSinh AS
SELECT
  pp.maPhanPhong,
  t.maThiSinh,
  t.hoTen,
  t.soBaoDanh,
  p.tenPhong,
  d.tenDiemThi,
  d.maKyThi,
  pp.ngayPhan,
  pp.trangThai
FROM PhanPhongThi pp
JOIN ThiSinh t ON t.maThiSinh = pp.maThiSinh
JOIN PhongThi p ON p.maPhong = pp.maPhong
JOIN DiemThi d ON d.maDiemThi = p.maDiemThi;

-- 4.7. vw_PhanCongRaDe (theo báo cáo Tuần 08)
-- Tra cứu nhiệm vụ ra đề thi của cán bộ theo môn và kỳ thi.
CREATE OR REPLACE VIEW vw_PhanCongRaDe AS
SELECT
  pc.maPhanCong,
  c.hoTen,
  k.tenKyThi,
  m.tenMonThi,
  pc.thoiGianThucHien,
  pc.thongTinNhiemVu,
  pc.maKyThi,
  pc.maMonThi,
  pc.maCanBo
FROM PhanCongRaDe pc
JOIN CanBo c ON c.maCanBo = pc.maCanBo
JOIN KyThi k ON k.maKyThi = pc.maKyThi
JOIN MonThi m ON m.maMonThi = pc.maMonThi;

-- 4.8. vw_KetQuaThiTheoMon (Nguyễn Thị Ngọc Linh)
-- Điểm từng môn của thí sinh và trạng thái công bố. Bài chưa chấm có diemThi = NULL.
CREATE OR REPLACE VIEW vw_KetQuaThiTheoMon AS
SELECT t.maThiSinh, t.hoTen, b.maKyThi, m.tenMonThi, b.maBaiThi,
       k.diemThi, b.trangThaiCham, k.trangThaiCongBo
FROM BaiThi b
JOIN ThiSinh t ON t.maThiSinh = b.maThiSinh
JOIN MonThi m ON m.maMonThi = b.maMonThi
LEFT JOIN KetQuaChamThi k ON k.maBaiThi = b.maBaiThi;

-- 4.9. vw_ThongKeKetQuaMonThi (Nguyễn Thị Ngọc Linh) – dùng cho UC14.
-- Thêm cột maKyThi so với báo cáo để thống kê đúng theo từng kỳ thi.
CREATE OR REPLACE VIEW vw_ThongKeKetQuaMonThi AS
SELECT b.maKyThi, m.maMonThi, m.tenMonThi,
       COUNT(k.maKetQuaChamThi) AS soBaiDaCham,
       CAST(AVG(k.diemThi) AS DECIMAL(9,2)) AS diemTrungBinh,
       MAX(k.diemThi) AS diemCaoNhat,
       MIN(k.diemThi) AS diemThapNhat
FROM BaiThi b
JOIN MonThi m ON m.maMonThi = b.maMonThi
LEFT JOIN KetQuaChamThi k ON k.maBaiThi = b.maBaiThi
GROUP BY b.maKyThi, m.maMonThi, m.tenMonThi;

-- 6.7. fn_DemPhongTheoDiemThi (theo báo cáo Tuần 08)
-- Đếm số phòng thuộc địa điểm thi.
DROP FUNCTION IF EXISTS fn_DemPhongTheoDiemThi;
DELIMITER $$
CREATE FUNCTION fn_DemPhongTheoDiemThi(p_maDiemThi VARCHAR(20))
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
  DECLARE v_count INT;
  SELECT COUNT(*) INTO v_count FROM PhongThi WHERE maDiemThi = p_maDiemThi;
  RETURN v_count;
END$$
DELIMITER ;

-- 6.8. fn_TongSucChuaDiemThi (theo báo cáo Tuần 08)
-- Tính tổng sức chứa phòng tại một địa điểm thi.
DROP FUNCTION IF EXISTS fn_TongSucChuaDiemThi;
DELIMITER $$
CREATE FUNCTION fn_TongSucChuaDiemThi(p_maDiemThi VARCHAR(20))
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
  DECLARE v_sum INT;
  SELECT COALESCE(SUM(sucChua), 0) INTO v_sum FROM PhongThi WHERE maDiemThi = p_maDiemThi;
  RETURN v_sum;
END$$
DELIMITER ;

-- 6.9. fn_DemThiSinhTheoPhong (theo báo cáo Tuần 08)
-- Đếm số thí sinh đang được phân vào một phòng thi (trạng thái 'Đã phân').
DROP FUNCTION IF EXISTS fn_DemThiSinhTheoPhong;
DELIMITER $$
CREATE FUNCTION fn_DemThiSinhTheoPhong(p_maPhong VARCHAR(20))
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
  DECLARE v_count INT;
  SELECT COUNT(*) INTO v_count
  FROM PhanPhongThi
  WHERE maPhong = p_maPhong AND trangThai = 'Đã phân';
  RETURN v_count;
END$$
DELIMITER ;

-- 6.10. fn_DemBaiThiTheoMon (Nguyễn Thị Ngọc Linh): số bài thi theo kỳ thi và môn.
DROP FUNCTION IF EXISTS fn_DemBaiThiTheoMon;
DELIMITER $$
CREATE FUNCTION fn_DemBaiThiTheoMon(p_maKyThi VARCHAR(20), p_maMonThi VARCHAR(20))
RETURNS INT READS SQL DATA
BEGIN
  RETURN (SELECT COUNT(*) FROM BaiThi WHERE maKyThi = p_maKyThi AND maMonThi = p_maMonThi);
END$$
DELIMITER ;

-- 6.11. fn_DemBaiThiChuaCham (Nguyễn Thị Ngọc Linh): số bài của kỳ thi chưa có kết quả chấm.
DROP FUNCTION IF EXISTS fn_DemBaiThiChuaCham;
DELIMITER $$
CREATE FUNCTION fn_DemBaiThiChuaCham(p_maKyThi VARCHAR(20))
RETURNS INT READS SQL DATA
BEGIN
  RETURN (SELECT COUNT(*) FROM BaiThi b
          LEFT JOIN KetQuaChamThi k ON k.maBaiThi = b.maBaiThi
          WHERE b.maKyThi = p_maKyThi AND (k.maKetQuaChamThi IS NULL OR b.trangThaiCham <> 'Đã chấm'));
END$$
DELIMITER ;

-- 6.13. fn_DiemTrungBinhTheoMon (Nguyễn Thị Ngọc Linh): điểm trung bình các bài đã chấm của một môn trong kỳ.
-- Trả về NULL nếu môn chưa có bài nào được chấm.
DROP FUNCTION IF EXISTS fn_DiemTrungBinhTheoMon;
DELIMITER $$
CREATE FUNCTION fn_DiemTrungBinhTheoMon(p_maKyThi VARCHAR(20), p_maMonThi VARCHAR(20))
RETURNS DECIMAL(9,2) READS SQL DATA
BEGIN
  RETURN (SELECT AVG(k.diemThi) FROM KetQuaChamThi k JOIN BaiThi b ON b.maBaiThi = k.maBaiThi
          WHERE b.maKyThi = p_maKyThi AND b.maMonThi = p_maMonThi);
END$$
DELIMITER ;

-- 5.14. sp_QuanLyPhongThi (Nguyễn Thị Thúy Vy) – UC06.
-- Quản lý phòng thi (Thêm/Sửa/Xóa/Xem). Chặn trùng mã phòng hoặc trùng tên phòng tại cùng điểm thi.
DROP PROCEDURE IF EXISTS sp_QuanLyPhongThi;
DELIMITER $$
CREATE PROCEDURE sp_QuanLyPhongThi(
  IN  p_hanhDong VARCHAR(20),
  IN  p_maPhong VARCHAR(20),
  IN  p_tenPhong VARCHAR(100),
  IN  p_sucChua INT,
  IN  p_maDiemThi VARCHAR(20),
  OUT p_thongBao VARCHAR(200)
)
proc: BEGIN
  DECLARE v_tonTai INT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SET p_thongBao = 'Không thể lưu thông tin phòng thi.';
  END;

  SET p_thongBao = '';

  IF p_hanhDong = 'Them' THEN
    IF p_maPhong IS NULL OR TRIM(p_maPhong) = '' OR
       p_tenPhong IS NULL OR TRIM(p_tenPhong) = '' OR
       p_sucChua IS NULL OR p_sucChua <= 0 OR
       p_maDiemThi IS NULL OR TRIM(p_maDiemThi) = '' THEN
      SET p_thongBao = 'Thông tin phòng thi không hợp lệ.';
      LEAVE proc;
    END IF;

    -- Kiểm tra điểm thi tồn tại
    SELECT COUNT(*) INTO v_tonTai FROM DiemThi WHERE maDiemThi = p_maDiemThi;
    IF v_tonTai = 0 THEN
      SET p_thongBao = 'Điểm thi không tồn tại trong hệ thống.';
      LEAVE proc;
    END IF;

    -- Kiểm tra trùng mã phòng hoặc trùng tên phòng tại cùng điểm thi
    SELECT COUNT(*) INTO v_tonTai
    FROM PhongThi
    WHERE maPhong = p_maPhong OR (maDiemThi = p_maDiemThi AND tenPhong = p_tenPhong);
    IF v_tonTai > 0 THEN
      SET p_thongBao = 'Thông tin phòng thi bị trùng.';
      LEAVE proc;
    END IF;

    START TRANSACTION;
    INSERT INTO PhongThi (maPhong, tenPhong, sucChua, maDiemThi)
    VALUES (p_maPhong, p_tenPhong, p_sucChua, p_maDiemThi);
    COMMIT;
    SET p_thongBao = 'Tạo thông tin phòng thi thành công.';

  ELSEIF p_hanhDong = 'Sua' THEN
    IF p_maPhong IS NULL OR TRIM(p_maPhong) = '' OR
       p_tenPhong IS NULL OR TRIM(p_tenPhong) = '' OR
       p_sucChua IS NULL OR p_sucChua <= 0 THEN
      SET p_thongBao = 'Thông tin phòng thi không hợp lệ.';
      LEAVE proc;
    END IF;

    -- Kiểm tra phòng tồn tại
    SELECT COUNT(*) INTO v_tonTai FROM PhongThi WHERE maPhong = p_maPhong;
    IF v_tonTai = 0 THEN
      SET p_thongBao = 'Phòng thi không tồn tại.';
      LEAVE proc;
    END IF;

    -- Kiểm tra trùng tên phòng khác tại cùng điểm thi
    SELECT COUNT(*) INTO v_tonTai
    FROM PhongThi
    WHERE maDiemThi = p_maDiemThi AND tenPhong = p_tenPhong AND maPhong <> p_maPhong;
    IF v_tonTai > 0 THEN
      SET p_thongBao = 'Thông tin phòng thi bị trùng.';
      LEAVE proc;
    END IF;

    START TRANSACTION;
    UPDATE PhongThi
    SET tenPhong = p_tenPhong, sucChua = p_sucChua
    WHERE maPhong = p_maPhong;
    COMMIT;
    SET p_thongBao = 'Cập nhật thông tin phòng thi thành công.';

  ELSEIF p_hanhDong = 'Xoa' THEN
    -- Không cho xóa nếu đã có thí sinh phân vào phòng
    SELECT COUNT(*) INTO v_tonTai FROM PhanPhongThi WHERE maPhong = p_maPhong;
    IF v_tonTai > 0 THEN
      SET p_thongBao = 'Không thể xóa phòng thi đã có thí sinh được phân phòng.';
      LEAVE proc;
    END IF;

    START TRANSACTION;
    DELETE FROM PhongThi WHERE maPhong = p_maPhong;
    COMMIT;
    SET p_thongBao = 'Xóa phòng thi thành công.';

  ELSE
    SET p_thongBao = 'Hành động không hợp lệ.';
  END IF;
END$$
DELIMITER ;

-- 5.15. sp_PhanPhongThiSinh (Nguyễn Thị Thúy Vy) – UC07.
-- Phân thí sinh vào phòng thi cụ thể, kiểm tra điều kiện dự thi và sức chứa phòng.
DROP PROCEDURE IF EXISTS sp_PhanPhongThiSinh;
DELIMITER $$
CREATE PROCEDURE sp_PhanPhongThiSinh(
  IN  p_maPhanPhong VARCHAR(20),
  IN  p_maThiSinh VARCHAR(20),
  IN  p_maPhong VARCHAR(20),
  OUT p_thongBao VARCHAR(200)
)
proc: BEGIN
  DECLARE v_sucChua INT DEFAULT 0;
  DECLARE v_daPhan INT DEFAULT 0;
  DECLARE v_dieuKien INT DEFAULT 0;
  DECLARE v_trangThaiDk VARCHAR(50);
  DECLARE v_daCoPhong INT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SET p_thongBao = 'Không thể lưu kết quả phân phòng.';
  END;

  SET p_thongBao = '';

  IF p_maPhanPhong IS NULL OR TRIM(p_maPhanPhong) = '' OR
     p_maThiSinh IS NULL OR TRIM(p_maThiSinh) = '' OR
     p_maPhong IS NULL OR TRIM(p_maPhong) = '' THEN
    SET p_thongBao = 'Thông tin phân phòng không hợp lệ.';
    LEAVE proc;
  END IF;

  -- Kiểm tra phòng thi và sức chứa
  SELECT sucChua INTO v_sucChua FROM PhongThi WHERE maPhong = p_maPhong;
  IF v_sucChua IS NULL OR v_sucChua <= 0 THEN
    SET p_thongBao = 'Phòng thi không tồn tại trong hệ thống.';
    LEAVE proc;
  END IF;

  -- Kiểm tra số thí sinh hiện tại trong phòng
  SELECT COUNT(*) INTO v_daPhan FROM PhanPhongThi WHERE maPhong = p_maPhong AND trangThai = 'Đã phân';
  IF v_daPhan >= v_sucChua THEN
    SET p_thongBao = 'Sức chứa phòng thi không đủ.';
    LEAVE proc;
  END IF;

  -- Kiểm tra thí sinh
  SELECT dieuKienDuThi, trangThaiDangKyDuThi INTO v_dieuKien, v_trangThaiDk
  FROM ThiSinh WHERE maThiSinh = p_maThiSinh;
  IF v_dieuKien IS NULL THEN
    SET p_thongBao = 'Thí sinh không tồn tại trong hệ thống.';
    LEAVE proc;
  END IF;

  IF v_dieuKien <> 1 OR v_trangThaiDk <> 'Đã duyệt' THEN
    SET p_thongBao = 'Thí sinh chưa đủ điều kiện dự thi.';
    LEAVE proc;
  END IF;

  -- Kiểm tra thí sinh đã được phân phòng chưa
  SELECT COUNT(*) INTO v_daCoPhong
  FROM PhanPhongThi
  WHERE maThiSinh = p_maThiSinh AND trangThai = 'Đã phân';
  IF v_daCoPhong > 0 THEN
    SET p_thongBao = 'Thí sinh đã được phân phòng thi trước đó.';
    LEAVE proc;
  END IF;

  START TRANSACTION;
  INSERT INTO PhanPhongThi (maPhanPhong, maThiSinh, maPhong, ngayPhan, trangThai)
  VALUES (p_maPhanPhong, p_maThiSinh, p_maPhong, NOW(), 'Đã phân');
  COMMIT;

  SET p_thongBao = 'Phân phòng thi thành công.';
END$$
DELIMITER ;

-- 5.16. sp_ChuyenPhongThiSinh (Nguyễn Thị Thúy Vy) – UC07.
-- Chuyển thí sinh đã phân sang phòng mới, kiểm tra sức chứa phòng mới.
DROP PROCEDURE IF EXISTS sp_ChuyenPhongThiSinh;
DELIMITER $$
CREATE PROCEDURE sp_ChuyenPhongThiSinh(
  IN  p_maPhanPhong VARCHAR(20),
  IN  p_maPhongMoi VARCHAR(20),
  IN  p_lyDo VARCHAR(255),
  OUT p_thongBao VARCHAR(200)
)
proc: BEGIN
  DECLARE v_phongCu VARCHAR(20);
  DECLARE v_sucChua INT DEFAULT 0;
  DECLARE v_daPhan INT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SET p_thongBao = 'Không thể chuyển phòng thi cho thí sinh.';
  END;

  SET p_thongBao = '';

  IF p_maPhanPhong IS NULL OR TRIM(p_maPhanPhong) = '' OR
     p_maPhongMoi IS NULL OR TRIM(p_maPhongMoi) = '' THEN
    SET p_thongBao = 'Thông tin chuyển phòng không hợp lệ.';
    LEAVE proc;
  END IF;

  -- Kiểm tra bản ghi phân phòng
  SELECT maPhong INTO v_phongCu
  FROM PhanPhongThi
  WHERE maPhanPhong = p_maPhanPhong AND trangThai = 'Đã phân';
  IF v_phongCu IS NULL THEN
    SET p_thongBao = 'Không tìm thấy thông tin phân phòng hợp lệ.';
    LEAVE proc;
  END IF;

  IF v_phongCu = p_maPhongMoi THEN
    SET p_thongBao = 'Phòng thi mới trùng với phòng thi hiện tại.';
    LEAVE proc;
  END IF;

  -- Kiểm tra phòng mới và sức chứa
  SELECT sucChua INTO v_sucChua FROM PhongThi WHERE maPhong = p_maPhongMoi;
  IF v_sucChua IS NULL OR v_sucChua <= 0 THEN
    SET p_thongBao = 'Phòng thi mới không tồn tại.';
    LEAVE proc;
  END IF;

  SELECT COUNT(*) INTO v_daPhan
  FROM PhanPhongThi
  WHERE maPhong = p_maPhongMoi AND trangThai = 'Đã phân';
  IF v_daPhan >= v_sucChua THEN
    SET p_thongBao = 'Sức chứa phòng thi không đủ.';
    LEAVE proc;
  END IF;

  START TRANSACTION;
  UPDATE PhanPhongThi
  SET maPhong = p_maPhongMoi, ngayPhan = NOW(), trangThai = 'Đã phân'
  WHERE maPhanPhong = p_maPhanPhong;
  COMMIT;

  SET p_thongBao = 'Chuyển phòng thi cho thí sinh thành công.';
END$$
DELIMITER ;

-- 5.17. sp_LayDanhSachPhanPhong (Nguyễn Thị Thúy Vy) – UC07.
-- Lấy danh sách thí sinh đã phân phòng theo kỳ thi, điểm thi hoặc phòng thi.
DROP PROCEDURE IF EXISTS sp_LayDanhSachPhanPhong;
DELIMITER $$
CREATE PROCEDURE sp_LayDanhSachPhanPhong(
  IN p_maKyThi VARCHAR(20),
  IN p_maDiemThi VARCHAR(20),
  IN p_maPhong VARCHAR(20)
)
BEGIN
  SELECT
    pp.maPhanPhong,
    t.maThiSinh,
    t.hoTen,
    t.soBaoDanh,
    p.maPhong,
    p.tenPhong,
    d.maDiemThi,
    d.tenDiemThi,
    pp.ngayPhan,
    pp.trangThai
  FROM PhanPhongThi pp
  JOIN ThiSinh t ON t.maThiSinh = pp.maThiSinh
  JOIN PhongThi p ON p.maPhong = pp.maPhong
  JOIN DiemThi d ON d.maDiemThi = p.maDiemThi
  WHERE (p_maKyThi IS NULL OR p_maKyThi = '' OR d.maKyThi = p_maKyThi)
    AND (p_maDiemThi IS NULL OR p_maDiemThi = '' OR d.maDiemThi = p_maDiemThi)
    AND (p_maPhong IS NULL OR p_maPhong = '' OR p.maPhong = p_maPhong)
    AND pp.trangThai = 'Đã phân'
  ORDER BY d.maDiemThi, p.maPhong, t.soBaoDanh, t.maThiSinh;
END$$
DELIMITER ;

-- 5.21. sp_PhanCongRaDe (Nguyễn Thị Thúy Vy) – UC08.
-- Phân công cán bộ ra đề thi theo môn thi và kỳ thi.
DROP PROCEDURE IF EXISTS sp_PhanCongRaDe;
DELIMITER $$
CREATE PROCEDURE sp_PhanCongRaDe(
  IN  p_maPhanCong VARCHAR(20),
  IN  p_maCanBo VARCHAR(20),
  IN  p_maMonThi VARCHAR(20),
  IN  p_maKyThi VARCHAR(20),
  IN  p_thongTinNhiemVu VARCHAR(255),
  OUT p_thongBao VARCHAR(200)
)
proc: BEGIN
  DECLARE v_tonTai INT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SET p_thongBao = 'Không thể lưu phân công ra đề thi.';
  END;

  SET p_thongBao = '';

  IF p_maPhanCong IS NULL OR TRIM(p_maPhanCong) = '' OR
     p_maCanBo IS NULL OR TRIM(p_maCanBo) = '' OR
     p_maMonThi IS NULL OR TRIM(p_maMonThi) = '' OR
     p_maKyThi IS NULL OR TRIM(p_maKyThi) = '' OR
     p_thongTinNhiemVu IS NULL OR TRIM(p_thongTinNhiemVu) = '' THEN
    SET p_thongBao = 'Thông tin phân công ra đề không hợp lệ.';
    LEAVE proc;
  END IF;

  -- Kiểm tra kỳ thi
  SELECT COUNT(*) INTO v_tonTai FROM KyThi WHERE maKyThi = p_maKyThi;
  IF v_tonTai = 0 THEN
    SET p_thongBao = 'Kỳ thi không tồn tại trong hệ thống.';
    LEAVE proc;
  END IF;

  -- Kiểm tra môn thi
  SELECT COUNT(*) INTO v_tonTai FROM MonThi WHERE maMonThi = p_maMonThi;
  IF v_tonTai = 0 THEN
    SET p_thongBao = 'Môn thi không tồn tại trong hệ thống.';
    LEAVE proc;
  END IF;

  -- Kiểm tra cán bộ
  SELECT COUNT(*) INTO v_tonTai FROM CanBo WHERE maCanBo = p_maCanBo;
  IF v_tonTai = 0 THEN
    SET p_thongBao = 'Cán bộ không tồn tại trong hệ thống.';
    LEAVE proc;
  END IF;

  START TRANSACTION;
  INSERT INTO PhanCongRaDe (maPhanCong, maKyThi, maMonThi, maCanBo, thoiGianThucHien, thongTinNhiemVu)
  VALUES (p_maPhanCong, p_maKyThi, p_maMonThi, p_maCanBo, NOW(), p_thongTinNhiemVu)
  ON DUPLICATE KEY UPDATE
    thongTinNhiemVu = p_thongTinNhiemVu,
    thoiGianThucHien = NOW();
  COMMIT;

  SET p_thongBao = 'Phân công ra đề thi thành công.';
END$$
DELIMITER ;

-- 5.25. sp_NhapKetQuaChamThi (Nguyễn Thị Thúy Vy) – UC05.
-- Nhập điểm lần đầu cho bài thi theo mã phách/mã bài thi. Cập nhật trangThaiCham = 'Đã chấm'.
DROP PROCEDURE IF EXISTS sp_NhapKetQuaChamThi;
DELIMITER $$
CREATE PROCEDURE sp_NhapKetQuaChamThi(
  IN  p_maKetQuaChamThi VARCHAR(20),
  IN  p_maBaiThi VARCHAR(20),
  IN  p_diemThi DECIMAL(5,2),
  IN  p_nguoiNhap VARCHAR(100),
  OUT p_thongBao VARCHAR(200)
)
proc: BEGIN
  DECLARE v_thangDiem DECIMAL(5,2) DEFAULT NULL;
  DECLARE v_trangThaiCham VARCHAR(50) DEFAULT NULL;
  DECLARE v_daCoDiem INT DEFAULT 0;
  DECLARE v_trangThaiCongBo VARCHAR(50) DEFAULT NULL;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SET p_thongBao = 'Không thể lưu điểm thi.';
  END;

  SET p_thongBao = '';

  IF p_maBaiThi IS NULL OR TRIM(p_maBaiThi) = '' OR
     p_nguoiNhap IS NULL OR TRIM(p_nguoiNhap) = '' THEN
    SET p_thongBao = 'Thông tin bài thi hoặc người nhập không hợp lệ.';
    LEAVE proc;
  END IF;

  START TRANSACTION;
  -- Khóa dòng bài thi để kiểm tra
  SELECT b.trangThaiCham, m.thangDiem INTO v_trangThaiCham, v_thangDiem
  FROM BaiThi b
  JOIN MonThi m ON m.maMonThi = b.maMonThi
  WHERE b.maBaiThi = p_maBaiThi
  FOR UPDATE;

  IF v_thangDiem IS NULL THEN
    ROLLBACK;
    SET p_thongBao = 'Không tìm thấy bài thi cần nhập điểm.';
    LEAVE proc;
  END IF;

  -- Kiểm tra điểm hợp lệ: 0 <= điểm <= thangDiem và chia hết cho 0.25
  IF p_diemThi IS NULL OR p_diemThi < 0 OR p_diemThi > v_thangDiem OR MOD(p_diemThi * 100, 25) <> 0 THEN
    ROLLBACK;
    SET p_thongBao = 'Điểm thi không hợp lệ.';
    LEAVE proc;
  END IF;

  -- Kiểm tra nếu bài thi đã có kết quả
  SELECT COUNT(*), MAX(trangThaiCongBo) INTO v_daCoDiem, v_trangThaiCongBo
  FROM KetQuaChamThi
  WHERE maBaiThi = p_maBaiThi;

  IF v_daCoDiem > 0 THEN
    ROLLBACK;
    SET p_thongBao = 'Điểm bài thi đã được xác nhận chính thức.';
    LEAVE proc;
  END IF;

  -- Thêm kết quả chấm thi
  INSERT INTO KetQuaChamThi (maKetQuaChamThi, diemThi, nguoiNhap, thoiDiemNhap, trangThaiCongBo, maBaiThi)
  VALUES (p_maKetQuaChamThi, p_diemThi, p_nguoiNhap, NOW(), 'Chưa công bố', p_maBaiThi);

  -- Cập nhật trạng thái bài thi thành 'Đã chấm'
  UPDATE BaiThi
  SET trangThaiCham = 'Đã chấm'
  WHERE maBaiThi = p_maBaiThi;

  COMMIT;
  SET p_thongBao = 'Nhập điểm thi thành công.';
END$$
DELIMITER ;

-- 5.26. sp_CapNhatDiemThi (Nguyễn Thị Ngọc Linh) – UC13.
-- Sửa điểm và ghi LichSuChinhSuaDiem trong cùng một transaction. Lỗi thì hoàn tác, điểm cũ giữ nguyên.
DROP PROCEDURE IF EXISTS sp_CapNhatDiemThi;
DELIMITER $$
CREATE PROCEDURE sp_CapNhatDiemThi(
  IN  p_maKetQuaChamThi VARCHAR(20),
  IN  p_diemMoi DECIMAL(5,2),
  IN  p_lyDoChinhSua VARCHAR(255),
  IN  p_nguoiThucHien VARCHAR(100),
  OUT p_diemTruocChinhSua DECIMAL(5,2),
  OUT p_diemSauChinhSua DECIMAL(5,2),
  OUT p_thongBao VARCHAR(200)
)
proc: BEGIN
  DECLARE v_diemCu DECIMAL(5,2) DEFAULT NULL;
  DECLARE v_thangDiem DECIMAL(5,2) DEFAULT NULL;
  DECLARE v_soLichSu INT DEFAULT 0;
  DECLARE v_dangGhiLichSu BOOL DEFAULT FALSE;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SET p_diemTruocChinhSua = NULL, p_diemSauChinhSua = NULL;
    SET p_thongBao = IF(v_dangGhiLichSu,
      'Không thể ghi nhận lịch sử chỉnh sửa điểm. Kết quả chấm thi chưa được cập nhật.',
      'Không thể cập nhật kết quả chấm thi. Vui lòng thực hiện lại.');
  END;

  SET p_diemTruocChinhSua = NULL, p_diemSauChinhSua = NULL;
  IF p_lyDoChinhSua IS NULL OR TRIM(p_lyDoChinhSua) = '' THEN
    SET p_thongBao = 'Vui lòng nhập lý do chỉnh sửa điểm.';
    LEAVE proc;
  END IF;

  START TRANSACTION;
  SELECT k.diemThi, m.thangDiem INTO v_diemCu, v_thangDiem
  FROM KetQuaChamThi k
  JOIN BaiThi b ON b.maBaiThi = k.maBaiThi
  JOIN MonThi m ON m.maMonThi = b.maMonThi
  WHERE k.maKetQuaChamThi = p_maKetQuaChamThi
  FOR UPDATE;

  IF v_thangDiem IS NULL THEN
    ROLLBACK;
    SET p_thongBao = 'Không tìm thấy kết quả chấm thi phù hợp.';
    LEAVE proc;
  END IF;
  -- 9.1: điểm âm, vượt thang điểm hoặc sai bước nhảy 0.25
  IF p_diemMoi IS NULL OR p_diemMoi < 0 OR p_diemMoi > v_thangDiem OR MOD(p_diemMoi * 100, 25) <> 0 THEN
    ROLLBACK;
    SET p_thongBao = 'Điểm cập nhật không hợp lệ.';
    LEAVE proc;
  END IF;
  IF p_diemMoi = v_diemCu THEN
    ROLLBACK;
    SET p_thongBao = 'Điểm cập nhật không hợp lệ.';
    LEAVE proc;
  END IF;

  UPDATE KetQuaChamThi SET diemThi = p_diemMoi WHERE maKetQuaChamThi = p_maKetQuaChamThi;

  SET v_dangGhiLichSu = TRUE;
  SELECT COALESCE(MAX(CAST(SUBSTRING(maLichSu, 3) AS UNSIGNED)), 0) INTO v_soLichSu
  FROM LichSuChinhSuaDiem WHERE maLichSu LIKE 'LS%' FOR UPDATE;
  INSERT INTO LichSuChinhSuaDiem (maLichSu, diemTruocChinhSua, diemSauChinhSua, thoiGianChinhSua, nguoiThucHien, lyDoChinhSua, maKetQuaChamThi)
  VALUES (CONCAT('LS', LPAD(v_soLichSu + 1, 3, '0')), v_diemCu, p_diemMoi, NOW(),
          COALESCE(NULLIF(TRIM(p_nguoiThucHien), ''), 'Hội đồng chấm thi'), TRIM(p_lyDoChinhSua), p_maKetQuaChamThi);
  COMMIT;

  SET p_diemTruocChinhSua = v_diemCu, p_diemSauChinhSua = p_diemMoi;
  SET p_thongBao = 'Cập nhật kết quả chấm thi thành công.';
END$$
DELIMITER ;

-- 5.27. sp_CongBoKetQuaChamThi (Nguyễn Thị Ngọc Linh) – UC15.
-- Đổi trạng thái công bố các kết quả chấm của một kỳ thi. Điểm thi không thay đổi.
-- p_maMonThi: thêm so với báo cáo để công bố theo từng môn; truyền NULL để áp dụng cho cả kỳ thi.
-- Khi công bố: chặn nếu đã công bố hết hoặc còn bài chưa chấm.
DROP PROCEDURE IF EXISTS sp_CongBoKetQuaChamThi;
DELIMITER $$
CREATE PROCEDURE sp_CongBoKetQuaChamThi(
  IN  p_maKyThi VARCHAR(20),
  IN  p_maMonThi VARCHAR(20),
  IN  p_trangThaiMoi VARCHAR(50),
  OUT p_soBaiDuocCapNhat INT,
  OUT p_thongBao VARCHAR(200)
)
proc: BEGIN
  DECLARE v_daCham INT DEFAULT 0;
  DECLARE v_chuaCham INT DEFAULT 0;
  DECLARE v_canDoi INT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SET p_soBaiDuocCapNhat = 0;
    SET p_thongBao = 'Không thể công bố điểm thi tại thời điểm hiện tại. Vui lòng thử lại sau.';
  END;

  SET p_soBaiDuocCapNhat = 0;
  IF p_trangThaiMoi IS NULL OR p_trangThaiMoi NOT IN ('Đã công bố', 'Chưa công bố') THEN
    SET p_thongBao = 'Trạng thái công bố không hợp lệ.';
    LEAVE proc;
  END IF;

  START TRANSACTION;
  SELECT COALESCE(SUM(k.maKetQuaChamThi IS NOT NULL AND b.trangThaiCham = 'Đã chấm'), 0),
         COALESCE(SUM(k.maKetQuaChamThi IS NULL OR b.trangThaiCham <> 'Đã chấm'), 0),
         COALESCE(SUM(k.maKetQuaChamThi IS NOT NULL AND k.trangThaiCongBo <> p_trangThaiMoi), 0)
    INTO v_daCham, v_chuaCham, v_canDoi
  FROM BaiThi b LEFT JOIN KetQuaChamThi k ON k.maBaiThi = b.maBaiThi
  WHERE b.maKyThi = p_maKyThi AND (p_maMonThi IS NULL OR b.maMonThi = p_maMonThi)
  FOR UPDATE;

  IF v_daCham = 0 THEN
    ROLLBACK;
    SET p_thongBao = 'Kết quả chấm thi chưa đủ điều kiện công bố.';
    LEAVE proc;
  END IF;
  IF v_canDoi = 0 THEN
    ROLLBACK;
    SET p_thongBao = IF(p_trangThaiMoi = 'Đã công bố', 'Kết quả chấm thi đã được công bố.', 'Kết quả chấm thi chưa được công bố.');
    LEAVE proc;
  END IF;
  IF p_trangThaiMoi = 'Đã công bố' AND v_chuaCham > 0 THEN
    ROLLBACK;
    SET p_thongBao = 'Kết quả chấm thi chưa đủ điều kiện công bố.';
    LEAVE proc;
  END IF;

  UPDATE KetQuaChamThi k JOIN BaiThi b ON b.maBaiThi = k.maBaiThi
  SET k.trangThaiCongBo = p_trangThaiMoi
  WHERE b.maKyThi = p_maKyThi AND (p_maMonThi IS NULL OR b.maMonThi = p_maMonThi)
    AND k.trangThaiCongBo <> p_trangThaiMoi;
  SET p_soBaiDuocCapNhat = ROW_COUNT();
  COMMIT;

  SET p_thongBao = IF(p_trangThaiMoi = 'Đã công bố', 'Công bố điểm thi thành công.', 'Đã chuyển kết quả thi về trạng thái chưa công bố.');
END$$
DELIMITER ;

-- 5.28. sp_XemLichSuChinhSuaDiem (Nguyễn Thị Ngọc Linh) – UC12.
-- Nhật ký thay đổi điểm của một bài thi, mới nhất trước. Không có dòng nào nghĩa là chưa có lịch sử chỉnh sửa.
DROP PROCEDURE IF EXISTS sp_XemLichSuChinhSuaDiem;
DELIMITER $$
CREATE PROCEDURE sp_XemLichSuChinhSuaDiem(IN p_maBaiThi VARCHAR(20))
BEGIN
  SELECT l.thoiGianChinhSua, l.diemTruocChinhSua, l.diemSauChinhSua, l.nguoiThucHien, l.lyDoChinhSua
  FROM LichSuChinhSuaDiem l
  JOIN KetQuaChamThi k ON k.maKetQuaChamThi = l.maKetQuaChamThi
  WHERE k.maBaiThi = p_maBaiThi
  ORDER BY l.thoiGianChinhSua DESC, l.maLichSu DESC;
END$$
DELIMITER ;

SET FOREIGN_KEY_CHECKS=1;
