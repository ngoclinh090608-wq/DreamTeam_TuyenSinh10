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

SET FOREIGN_KEY_CHECKS=1;
