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

-- DỮ LIỆU MẪU (mô phỏng) phục vụ demo. Không phải dữ liệu tuyển sinh chính thức.
-- Chạy sau khi đã tạo đủ 30 bảng (database/tables).
SET NAMES utf8mb4;

INSERT INTO KhuVuc (maKhuVuc, tenKhuVuc, quanHuyen) VALUES
  ('KV01', 'Khu vực 1', 'Quận 1, Quận 3'),
  ('KV02', 'Khu vực 2', 'Quận 5, Quận 10'),
  ('KV03', 'Khu vực 3', 'Bình Thạnh, Gò Vấp');

INSERT INTO TruongTHCS (maTruongTHCS, tenTruongTHCS, diaChi, quanHuyen, maKhuVuc) VALUES
  ('THCS_A', 'Trường THCS Mẫu A', 'Quận 1, TP.HCM (mô phỏng)', 'Quận 1', 'KV01'),
  ('THCS_B', 'Trường THCS Mẫu B', 'Quận 3, TP.HCM (mô phỏng)', 'Quận 3', 'KV01'),
  ('THCS_C', 'Trường THCS Mẫu C', 'Quận 5, TP.HCM (mô phỏng)', 'Quận 5', 'KV02'),
  ('THCS_D', 'Trường THCS Mẫu D', 'Bình Thạnh, TP.HCM (mô phỏng)', 'Bình Thạnh', 'KV03');

INSERT INTO TruongTHPT (maTruongTHPT, tenTruongTHPT, maKhuVuc) VALUES
  ('THPT_1', 'Trường THPT Mẫu 1', 'KV01'),
  ('THPT_2', 'Trường THPT Mẫu 2', 'KV01'),
  ('THPT_3', 'Trường THPT Mẫu 3', 'KV02'),
  ('THPT_4', 'Trường THPT Mẫu 4', 'KV02'),
  ('THPT_5', 'Trường THPT Mẫu 5', 'KV03');

INSERT INTO DotTuyenSinh (maDot, tenDot, namTuyenSinh) VALUES
  ('DOT2026', 'Tuyển sinh lớp 10 năm học 2026-2027', 2026);

INSERT INTO ThongTinTuyenSinh (maThongTin, chiTieuTuyenSinh, heDaoTao, phuongThucTuyenSinh, hocPhi, noiDungChiTiet, trangThaiCongBo, maTruongTHPT, maDot) VALUES
  ('TT_THPT_1', 2, 'Công lập', 'Thi tuyển', 0, 'Chỉ tiêu mô phỏng cho demo.', 'Đã công bố', 'THPT_1', 'DOT2026'),
  ('TT_THPT_2', 3, 'Công lập', 'Thi tuyển', 0, 'Chỉ tiêu mô phỏng cho demo.', 'Đã công bố', 'THPT_2', 'DOT2026'),
  ('TT_THPT_3', 3, 'Công lập', 'Thi tuyển', 0, 'Chỉ tiêu mô phỏng cho demo.', 'Đã công bố', 'THPT_3', 'DOT2026'),
  ('TT_THPT_4', 4, 'Công lập', 'Thi tuyển', 0, 'Chỉ tiêu mô phỏng cho demo.', 'Đã công bố', 'THPT_4', 'DOT2026'),
  ('TT_THPT_5', 3, 'Công lập', 'Thi tuyển', 0, 'Chỉ tiêu mô phỏng cho demo.', 'Đã công bố', 'THPT_5', 'DOT2026');

INSERT INTO KyThi (maKyThi, tenKyThi, thoiGianKetThuc, maDot) VALUES
  ('KT2026', 'Kỳ thi tuyển sinh lớp 10 năm 2026', '2026-06-12 12:00:00', 'DOT2026');

INSERT INTO MonThi (maMonThi, tenMonThi, thangDiem) VALUES
  ('TOAN', 'Toán', 10),
  ('VAN', 'Ngữ văn', 10),
  ('NN', 'Ngoại ngữ', 10);

INSERT INTO DiemThi (maDiemThi, tenDiemThi, maTruongTHCS, maTruongTHPT, maKyThi) VALUES
  ('DT01', 'Điểm thi Trường THCS Mẫu A', 'THCS_A', NULL, 'KT2026'),
  ('DT02', 'Điểm thi Trường THPT Mẫu 1', NULL, 'THPT_1', 'KT2026');

INSERT INTO PhongThi (maPhong, tenPhong, sucChua, maDiemThi) VALUES
  ('P101', 'Phòng 101', 12, 'DT01'),
  ('P102', 'Phòng 102', 12, 'DT01'),
  ('P201', 'Phòng 201', 12, 'DT02'),
  ('P202', 'Phòng 202', 12, 'DT02');

INSERT INTO ThiSinh (maThiSinh, maHoSo, hoTen, ngaySinh, soBaoDanh, dieuKienDuThi, trangThaiDangKyDuThi, maTruongTHCS) VALUES
  ('TS001', 'HS2026001', 'Trần Thị Hà', '2011-05-27', 'SBD0001', 1, 'Đã duyệt', 'THCS_A'),
  ('TS002', 'HS2026002', 'Trần Minh Chi', '2011-08-01', 'SBD0002', 1, 'Đã duyệt', 'THCS_B'),
  ('TS003', 'HS2026003', 'Bùi Ngọc An', '2011-11-11', 'SBD0003', 1, 'Đã duyệt', 'THCS_C'),
  ('TS004', 'HS2026004', 'Phạm Gia Bình', '2011-07-09', 'SBD0004', 1, 'Đã duyệt', 'THCS_D'),
  ('TS005', 'HS2026005', 'Võ Quốc Uyên', '2011-06-26', 'SBD0005', 1, 'Đã duyệt', 'THCS_A'),
  ('TS006', 'HS2026006', 'Đặng Thanh Giang', '2011-12-17', 'SBD0006', 1, 'Đã duyệt', 'THCS_B'),
  ('TS007', 'HS2026007', 'Trần Hoàng Sơn', '2011-12-11', 'SBD0007', 1, 'Đã duyệt', 'THCS_C'),
  ('TS008', 'HS2026008', 'Trần Văn Linh', '2011-09-18', 'SBD0008', 1, 'Đã duyệt', 'THCS_D'),
  ('TS009', 'HS2026009', 'Huỳnh Thị Lan', '2011-05-15', 'SBD0009', 1, 'Đã duyệt', 'THCS_A'),
  ('TS010', 'HS2026010', 'Lê Minh Khánh', '2011-11-23', 'SBD0010', 1, 'Đã duyệt', 'THCS_B'),
  ('TS011', 'HS2026011', 'Huỳnh Ngọc Nam', '2011-01-28', 'SBD0011', 1, 'Đã duyệt', 'THCS_C'),
  ('TS012', 'HS2026012', 'Võ Gia Trang', '2011-06-15', 'SBD0012', 1, 'Đã duyệt', 'THCS_D'),
  ('TS013', 'HS2026013', 'Đặng Quốc Phúc', '2011-02-13', 'SBD0013', 1, 'Đã duyệt', 'THCS_A'),
  ('TS014', 'HS2026014', 'Bùi Thanh Yến', '2011-02-14', 'SBD0014', 1, 'Đã duyệt', 'THCS_B'),
  ('TS015', 'HS2026015', 'Bùi Hoàng Vy', '2011-07-17', 'SBD0015', 1, 'Đã duyệt', 'THCS_C'),
  ('TS016', 'HS2026016', 'Huỳnh Văn Tâm', '2011-07-19', 'SBD0016', 1, 'Đã duyệt', 'THCS_D'),
  ('TS017', 'HS2026017', 'Bùi Thị Hải', '2011-09-17', 'SBD0017', 1, 'Đã duyệt', 'THCS_A'),
  ('TS018', 'HS2026018', 'Nguyễn Minh Thảo', '2011-10-08', 'SBD0018', 1, 'Đã duyệt', 'THCS_B'),
  ('TS019', 'HS2026019', 'Lê Ngọc Quân', '2011-01-23', NULL, 0, 'Chưa duyệt', 'THCS_C'),
  ('TS020', 'HS2026020', 'Trần Gia Dũng', '2011-12-20', NULL, 0, 'Từ chối', 'THCS_D');

INSERT INTO HoiDongThi (maHoiDongThi, tenHoiDongThi, thongTinHoiDong, loaiHoiDong, maKyThi) VALUES
  ('HD01', 'Hội đồng ra đề thi', 'Soạn đề thi các môn', 'Ra đề', 'KT2026'),
  ('HD02', 'Hội đồng coi thi', 'Tổ chức coi thi', 'Coi thi', 'KT2026'),
  ('HD03', 'Hội đồng chấm thi', 'Chấm bài thi theo số phách', 'Chấm thi', 'KT2026'),
  ('HD04', 'Hội đồng phúc khảo', 'Chấm phúc khảo', 'Phúc khảo', 'KT2026'),
  ('HD05', 'Hội đồng tuyển sinh', 'Xét tuyển và công bố kết quả', 'Tuyển sinh', 'KT2026');

INSERT INTO CanBo (maCanBo, hoTen, soDienThoai, email, chucVu, maTruongTHCS, maTruongTHPT, maHoiDongThi) VALUES
  ('CB01', 'Lê Minh Tuấn', '0900000001', 'cb01@example.com', 'Giáo viên', 'THCS_B', NULL, 'HD01'),
  ('CB02', 'Trần Thu Hà', '0900000002', 'cb02@example.com', 'Giáo viên', 'THCS_C', NULL, 'HD01'),
  ('CB03', 'Phạm Quốc Khang', '0900000003', 'cb03@example.com', 'Giáo viên', 'THCS_D', NULL, 'HD02'),
  ('CB04', 'Nguyễn Thanh Mai', '0900000004', 'cb04@example.com', 'Giáo viên', 'THCS_A', NULL, 'HD02'),
  ('CB05', 'Võ Hoàng Long', '0900000005', 'cb05@example.com', 'Giáo viên', 'THCS_B', NULL, 'HD02'),
  ('CB06', 'Đặng Thị Hương', '0900000006', 'cb06@example.com', 'Giáo viên', 'THCS_C', NULL, 'HD02'),
  ('CB07', 'Bùi Văn Hiếu', '0900000007', 'cb07@example.com', 'Giáo viên', 'THCS_D', NULL, 'HD02'),
  ('CB08', 'Huỳnh Ngọc Anh', '0900000008', 'cb08@example.com', 'Giáo viên', 'THCS_A', NULL, 'HD02'),
  ('CB09', 'Lý Gia Bảo', '0900000009', 'cb09@example.com', 'Giáo viên', 'THCS_B', NULL, 'HD03'),
  ('CB10', 'Phan Thùy Dương', '0900000010', 'cb10@example.com', 'Giáo viên', 'THCS_C', NULL, 'HD03'),
  ('CB11', 'Đỗ Anh Khoa', '0900000011', 'cb11@example.com', 'Giáo viên', 'THCS_D', NULL, 'HD04'),
  ('CB12', 'Ngô Bích Ngọc', '0900000012', 'cb12@example.com', 'Giáo viên', 'THCS_A', NULL, 'HD05');

INSERT INTO PhanCongCoiThi (maPhanCong, vaiTro, maPhong, maMonThi, maCanBo) VALUES
  ('PCT001', 'Giám thị 1', 'P101', 'TOAN', 'CB03'),
  ('PCT002', 'Giám thị 2', 'P101', 'TOAN', 'CB04'),
  ('PCT003', 'Giám thị 1', 'P101', 'VAN', 'CB04'),
  ('PCT004', 'Giám thị 2', 'P101', 'VAN', 'CB05'),
  ('PCT005', 'Giám thị 1', 'P101', 'NN', 'CB05'),
  ('PCT006', 'Giám thị 2', 'P101', 'NN', 'CB06'),
  ('PCT007', 'Giám thị 1', 'P102', 'TOAN', 'CB05'),
  ('PCT008', 'Giám thị 2', 'P102', 'TOAN', 'CB06'),
  ('PCT009', 'Giám thị 1', 'P102', 'VAN', 'CB06'),
  ('PCT010', 'Giám thị 2', 'P102', 'VAN', 'CB07'),
  ('PCT011', 'Giám thị 1', 'P102', 'NN', 'CB07'),
  ('PCT012', 'Giám thị 2', 'P102', 'NN', 'CB08'),
  ('PCT013', 'Giám thị 1', 'P201', 'TOAN', 'CB07'),
  ('PCT014', 'Giám thị 2', 'P201', 'TOAN', 'CB08'),
  ('PCT015', 'Giám thị 1', 'P201', 'VAN', 'CB08'),
  ('PCT016', 'Giám thị 2', 'P201', 'VAN', 'CB03'),
  ('PCT017', 'Giám thị 1', 'P201', 'NN', 'CB03'),
  ('PCT018', 'Giám thị 2', 'P201', 'NN', 'CB04'),
  ('PCT019', 'Giám thị 1', 'P202', 'TOAN', 'CB03'),
  ('PCT020', 'Giám thị 2', 'P202', 'TOAN', 'CB04'),
  ('PCT021', 'Giám thị 1', 'P202', 'VAN', 'CB04'),
  ('PCT022', 'Giám thị 2', 'P202', 'VAN', 'CB05'),
  ('PCT023', 'Giám thị 1', 'P202', 'NN', 'CB05'),
  ('PCT024', 'Giám thị 2', 'P202', 'NN', 'CB06');

INSERT INTO PhanCongRaDe (maPhanCong, maKyThi, maMonThi, maCanBo, thoiGianThucHien, thongTinNhiemVu) VALUES
  ('PCRD1', 'KT2026', 'TOAN', 'CB01', NULL, 'Ra đề thi môn Toán'),
  ('PCRD2', 'KT2026', 'VAN', 'CB02', NULL, 'Ra đề thi môn Ngữ văn'),
  ('PCRD3', 'KT2026', 'NN', 'CB02', '2026-05-30 09:00:00', 'Ra đề thi môn Ngoại ngữ');

INSERT INTO PhanPhongThi (maPhanPhong, maThiSinh, maPhong, ngayPhan, trangThai) VALUES
  ('PP001', 'TS001', 'P101', '2026-05-25 08:00:00', 'Đã phân'),
  ('PP002', 'TS002', 'P101', '2026-05-25 08:00:00', 'Đã phân'),
  ('PP003', 'TS003', 'P101', '2026-05-25 08:00:00', 'Đã phân'),
  ('PP004', 'TS004', 'P101', '2026-05-25 08:00:00', 'Đã phân'),
  ('PP005', 'TS005', 'P101', '2026-05-25 08:00:00', 'Đã phân'),
  ('PP006', 'TS006', 'P102', '2026-05-25 08:00:00', 'Đã phân'),
  ('PP007', 'TS007', 'P102', '2026-05-25 08:00:00', 'Đã phân'),
  ('PP008', 'TS008', 'P102', '2026-05-25 08:00:00', 'Đã phân'),
  ('PP009', 'TS009', 'P102', '2026-05-25 08:00:00', 'Đã phân'),
  ('PP010', 'TS010', 'P102', '2026-05-25 08:00:00', 'Đã phân'),
  ('PP011', 'TS011', 'P201', '2026-05-25 08:00:00', 'Đã phân'),
  ('PP012', 'TS012', 'P201', '2026-05-25 08:00:00', 'Đã phân'),
  ('PP013', 'TS013', 'P201', '2026-05-25 08:00:00', 'Đã phân'),
  ('PP014', 'TS014', 'P201', '2026-05-25 08:00:00', 'Đã phân'),
  ('PP015', 'TS015', 'P201', '2026-05-25 08:00:00', 'Đã phân'),
  ('PP016', 'TS016', 'P202', '2026-05-25 08:00:00', 'Đã phân'),
  ('PP017', 'TS017', 'P202', '2026-05-25 08:00:00', 'Đã phân'),
  ('PP018', 'TS018', 'P202', '2026-05-25 08:00:00', 'Đã phân');

INSERT INTO LoBaiThi (maLoBaiThi, thongTinLoBaiThi, maMonThi, maPhong) VALUES
  ('LO_P101_TOAN', 'Lô bài thi Toán - Phòng 101', 'TOAN', 'P101'),
  ('LO_P101_VAN', 'Lô bài thi Ngữ văn - Phòng 101', 'VAN', 'P101'),
  ('LO_P101_NN', 'Lô bài thi Ngoại ngữ - Phòng 101', 'NN', 'P101'),
  ('LO_P102_TOAN', 'Lô bài thi Toán - Phòng 102', 'TOAN', 'P102'),
  ('LO_P102_VAN', 'Lô bài thi Ngữ văn - Phòng 102', 'VAN', 'P102'),
  ('LO_P102_NN', 'Lô bài thi Ngoại ngữ - Phòng 102', 'NN', 'P102'),
  ('LO_P201_TOAN', 'Lô bài thi Toán - Phòng 201', 'TOAN', 'P201'),
  ('LO_P201_VAN', 'Lô bài thi Ngữ văn - Phòng 201', 'VAN', 'P201'),
  ('LO_P201_NN', 'Lô bài thi Ngoại ngữ - Phòng 201', 'NN', 'P201'),
  ('LO_P202_TOAN', 'Lô bài thi Toán - Phòng 202', 'TOAN', 'P202'),
  ('LO_P202_VAN', 'Lô bài thi Ngữ văn - Phòng 202', 'VAN', 'P202'),
  ('LO_P202_NN', 'Lô bài thi Ngoại ngữ - Phòng 202', 'NN', 'P202');

INSERT INTO BaiThi (maBaiThi, maKyThi, maMonThi, maThiSinh, maLoBaiThi, soPhach, trangThaiCham) VALUES
  ('BT001', 'KT2026', 'TOAN', 'TS001', 'LO_P101_TOAN', 'P0052', 'Đã chấm'),
  ('BT002', 'KT2026', 'VAN', 'TS001', 'LO_P101_VAN', 'P0023', 'Đã chấm'),
  ('BT003', 'KT2026', 'NN', 'TS001', 'LO_P101_NN', 'P0034', 'Đã chấm'),
  ('BT004', 'KT2026', 'TOAN', 'TS002', 'LO_P101_TOAN', 'P0003', 'Đã chấm'),
  ('BT005', 'KT2026', 'VAN', 'TS002', 'LO_P101_VAN', 'P0004', 'Đã chấm'),
  ('BT006', 'KT2026', 'NN', 'TS002', 'LO_P101_NN', 'P0043', 'Đã chấm'),
  ('BT007', 'KT2026', 'TOAN', 'TS003', 'LO_P101_TOAN', 'P0014', 'Đã chấm'),
  ('BT008', 'KT2026', 'VAN', 'TS003', 'LO_P101_VAN', 'P0049', 'Đã chấm'),
  ('BT009', 'KT2026', 'NN', 'TS003', 'LO_P101_NN', 'P0006', 'Đã chấm'),
  ('BT010', 'KT2026', 'TOAN', 'TS004', 'LO_P101_TOAN', 'P0038', 'Đã chấm'),
  ('BT011', 'KT2026', 'VAN', 'TS004', 'LO_P101_VAN', 'P0029', 'Đã chấm'),
  ('BT012', 'KT2026', 'NN', 'TS004', 'LO_P101_NN', 'P0011', 'Đã chấm'),
  ('BT013', 'KT2026', 'TOAN', 'TS005', 'LO_P101_TOAN', 'P0032', 'Đã chấm'),
  ('BT014', 'KT2026', 'VAN', 'TS005', 'LO_P101_VAN', 'P0025', 'Đã chấm'),
  ('BT015', 'KT2026', 'NN', 'TS005', 'LO_P101_NN', 'P0037', 'Đã chấm'),
  ('BT016', 'KT2026', 'TOAN', 'TS006', 'LO_P102_TOAN', 'P0018', 'Đã chấm'),
  ('BT017', 'KT2026', 'VAN', 'TS006', 'LO_P102_VAN', 'P0024', 'Đã chấm'),
  ('BT018', 'KT2026', 'NN', 'TS006', 'LO_P102_NN', 'P0022', 'Đã chấm'),
  ('BT019', 'KT2026', 'TOAN', 'TS007', 'LO_P102_TOAN', 'P0046', 'Đã chấm'),
  ('BT020', 'KT2026', 'VAN', 'TS007', 'LO_P102_VAN', 'P0007', 'Đã chấm'),
  ('BT021', 'KT2026', 'NN', 'TS007', 'LO_P102_NN', 'P0012', 'Đã chấm'),
  ('BT022', 'KT2026', 'TOAN', 'TS008', 'LO_P102_TOAN', 'P0016', 'Đã chấm'),
  ('BT023', 'KT2026', 'VAN', 'TS008', 'LO_P102_VAN', 'P0042', 'Đã chấm'),
  ('BT024', 'KT2026', 'NN', 'TS008', 'LO_P102_NN', 'P0050', 'Đã chấm'),
  ('BT025', 'KT2026', 'TOAN', 'TS009', 'LO_P102_TOAN', 'P0039', 'Đã chấm'),
  ('BT026', 'KT2026', 'VAN', 'TS009', 'LO_P102_VAN', 'P0021', 'Đã chấm'),
  ('BT027', 'KT2026', 'NN', 'TS009', 'LO_P102_NN', 'P0005', 'Đã chấm'),
  ('BT028', 'KT2026', 'TOAN', 'TS010', 'LO_P102_TOAN', 'P0002', 'Đã chấm'),
  ('BT029', 'KT2026', 'VAN', 'TS010', 'LO_P102_VAN', 'P0001', 'Đã chấm'),
  ('BT030', 'KT2026', 'NN', 'TS010', 'LO_P102_NN', 'P0009', 'Đã chấm'),
  ('BT031', 'KT2026', 'TOAN', 'TS011', 'LO_P201_TOAN', 'P0027', 'Đã chấm'),
  ('BT032', 'KT2026', 'VAN', 'TS011', 'LO_P201_VAN', 'P0019', 'Đã chấm'),
  ('BT033', 'KT2026', 'NN', 'TS011', 'LO_P201_NN', 'P0013', 'Đã chấm'),
  ('BT034', 'KT2026', 'TOAN', 'TS012', 'LO_P201_TOAN', 'P0054', 'Đã chấm'),
  ('BT035', 'KT2026', 'VAN', 'TS012', 'LO_P201_VAN', 'P0040', 'Đã chấm'),
  ('BT036', 'KT2026', 'NN', 'TS012', 'LO_P201_NN', 'P0047', 'Đã chấm'),
  ('BT037', 'KT2026', 'TOAN', 'TS013', 'LO_P201_TOAN', 'P0033', 'Đã chấm'),
  ('BT038', 'KT2026', 'VAN', 'TS013', 'LO_P201_VAN', 'P0048', 'Đã chấm'),
  ('BT039', 'KT2026', 'NN', 'TS013', 'LO_P201_NN', 'P0020', 'Đã chấm'),
  ('BT040', 'KT2026', 'TOAN', 'TS014', 'LO_P201_TOAN', 'P0026', 'Đã chấm'),
  ('BT041', 'KT2026', 'VAN', 'TS014', 'LO_P201_VAN', 'P0036', 'Đã chấm'),
  ('BT042', 'KT2026', 'NN', 'TS014', 'LO_P201_NN', 'P0017', 'Đã chấm'),
  ('BT043', 'KT2026', 'TOAN', 'TS015', 'LO_P201_TOAN', 'P0035', 'Đã chấm'),
  ('BT044', 'KT2026', 'VAN', 'TS015', 'LO_P201_VAN', 'P0051', 'Đã chấm'),
  ('BT045', 'KT2026', 'NN', 'TS015', 'LO_P201_NN', 'P0044', 'Đã chấm'),
  ('BT046', 'KT2026', 'TOAN', 'TS016', 'LO_P202_TOAN', 'P0030', 'Chưa chấm'),
  ('BT047', 'KT2026', 'VAN', 'TS016', 'LO_P202_VAN', 'P0045', 'Đã chấm'),
  ('BT048', 'KT2026', 'NN', 'TS016', 'LO_P202_NN', 'P0041', 'Đã chấm'),
  ('BT049', 'KT2026', 'TOAN', 'TS017', 'LO_P202_TOAN', 'P0008', 'Chưa chấm'),
  ('BT050', 'KT2026', 'VAN', 'TS017', 'LO_P202_VAN', 'P0015', 'Đã chấm'),
  ('BT051', 'KT2026', 'NN', 'TS017', 'LO_P202_NN', 'P0053', 'Đã chấm'),
  ('BT052', 'KT2026', 'TOAN', 'TS018', 'LO_P202_TOAN', 'P0010', 'Chưa chấm'),
  ('BT053', 'KT2026', 'VAN', 'TS018', 'LO_P202_VAN', 'P0031', 'Đã chấm'),
  ('BT054', 'KT2026', 'NN', 'TS018', 'LO_P202_NN', 'P0028', 'Đã chấm');

INSERT INTO KetQuaChamThi (maKetQuaChamThi, diemThi, nguoiNhap, thoiDiemNhap, trangThaiCongBo, maBaiThi) VALUES
  ('KQC001', 10, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT001'),
  ('KQC002', 9.0, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT002'),
  ('KQC003', 6.25, 'CB09', '2026-06-15 10:00:00', 'Chưa công bố', 'BT003'),
  ('KQC004', 3.5, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT004'),
  ('KQC005', 4.75, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT005'),
  ('KQC006', 6.5, 'CB09', '2026-06-15 10:00:00', 'Chưa công bố', 'BT006'),
  ('KQC007', 3.75, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT007'),
  ('KQC008', 8.25, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT008'),
  ('KQC009', 6.0, 'CB09', '2026-06-15 10:00:00', 'Chưa công bố', 'BT009'),
  ('KQC010', 6.25, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT010'),
  ('KQC011', 3.0, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT011'),
  ('KQC012', 3.0, 'CB09', '2026-06-15 10:00:00', 'Chưa công bố', 'BT012'),
  ('KQC013', 7.0, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT013'),
  ('KQC014', 9.5, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT014'),
  ('KQC015', 3.5, 'CB09', '2026-06-15 10:00:00', 'Chưa công bố', 'BT015'),
  ('KQC016', 8.5, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT016'),
  ('KQC017', 4.75, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT017'),
  ('KQC018', 7.25, 'CB09', '2026-06-15 10:00:00', 'Chưa công bố', 'BT018'),
  ('KQC019', 8.25, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT019'),
  ('KQC020', 9.75, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT020'),
  ('KQC021', 7.75, 'CB09', '2026-06-15 10:00:00', 'Chưa công bố', 'BT021'),
  ('KQC022', 7.5, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT022'),
  ('KQC023', 4.0, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT023'),
  ('KQC024', 5.25, 'CB09', '2026-06-15 10:00:00', 'Chưa công bố', 'BT024'),
  ('KQC025', 7.0, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT025'),
  ('KQC026', 8.5, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT026'),
  ('KQC027', 9.75, 'CB09', '2026-06-15 10:00:00', 'Chưa công bố', 'BT027'),
  ('KQC028', 6.5, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT028'),
  ('KQC029', 8.0, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT029'),
  ('KQC030', 4.0, 'CB09', '2026-06-15 10:00:00', 'Chưa công bố', 'BT030'),
  ('KQC031', 4.5, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT031'),
  ('KQC032', 7.0, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT032'),
  ('KQC033', 9.75, 'CB09', '2026-06-15 10:00:00', 'Chưa công bố', 'BT033'),
  ('KQC034', 9.25, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT034'),
  ('KQC035', 6.5, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT035'),
  ('KQC036', 3.5, 'CB09', '2026-06-15 10:00:00', 'Chưa công bố', 'BT036'),
  ('KQC037', 7.75, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT037'),
  ('KQC038', 9.5, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT038'),
  ('KQC039', 8.5, 'CB09', '2026-06-15 10:00:00', 'Chưa công bố', 'BT039'),
  ('KQC040', 9.5, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT040'),
  ('KQC041', 5.25, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT041'),
  ('KQC042', 6.0, 'CB09', '2026-06-15 10:00:00', 'Chưa công bố', 'BT042'),
  ('KQC043', 8.75, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT043'),
  ('KQC044', 6.75, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT044'),
  ('KQC045', 4.5, 'CB09', '2026-06-15 10:00:00', 'Chưa công bố', 'BT045'),
  ('KQC046', 3.0, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT047'),
  ('KQC047', 3.75, 'CB09', '2026-06-15 10:00:00', 'Chưa công bố', 'BT048'),
  ('KQC048', 9.25, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT050'),
  ('KQC049', 3.25, 'CB09', '2026-06-15 10:00:00', 'Chưa công bố', 'BT051'),
  ('KQC050', 5.5, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT053'),
  ('KQC051', 5.5, 'CB09', '2026-06-15 10:00:00', 'Chưa công bố', 'BT054');

INSERT INTO LichSuChinhSuaDiem (maLichSu, diemTruocChinhSua, diemSauChinhSua, thoiGianChinhSua, nguoiThucHien, lyDoChinhSua, maKetQuaChamThi) VALUES
  ('LS001', 9.75, 10, '2026-06-25 14:00:00', 'Phan Thùy Dương', 'Điều chỉnh theo kết quả phúc khảo', 'KQC001');

INSERT INTO YeuCauPhucKhao (maYeuCau, maThiSinh, maBaiThi, ngayYeuCau, lyDo, trangThai) VALUES
  ('YC001', 'TS001', 'BT001', '2026-06-20 09:00:00', 'Đề nghị xem lại điểm Toán', 'Đã xử lý'),
  ('YC002', 'TS002', 'BT004', '2026-06-21 10:30:00', 'Nghi ngờ sai sót khi cộng điểm', 'Chờ xử lý');

INSERT INTO KetQuaPhucKhao (maKetQua, maYeuCau, diemTruocPhucKhao, diemSauPhucKhao, ketLuan, ngayHoanThanh, maCanBo) VALUES
  ('KQPK001', 'YC001', 9.75, 10, 'Điều chỉnh điểm', '2026-06-25 13:30:00', 'CB11');

INSERT INTO MinhChungPhucKhao (maMinhChung, thongTinMinhChung, maKetQua) VALUES
  ('MC001', 'Bản chụp bài thi Toán đã đối chiếu', 'KQPK001');

INSERT INTO BienBanPhucKhao (maBienBan, maKetQua, noiDung, ngayLap, nguoiLap, duongDanTep) VALUES
  ('BB001', 'KQPK001', 'Hội đồng phúc khảo thống nhất điều chỉnh điểm bài thi Toán.', '2026-06-25 14:00:00', 'Phan Thùy Dương', NULL);

INSERT INTO QuyDinhNguyenVong (maQuyDinh, namTuyenSinh, soLuongNguyenVongToiDa, thoiGianBatDau, thoiGianKetThuc, ghiChu) VALUES
  ('QD2026', 2026, 3, '2026-05-01 08:00:00', '2026-05-20 17:00:00', 'Đăng ký tối đa 3 nguyện vọng');

INSERT INTO NguyenVong (maNguyenVong, maThiSinh, maTruongTHPT, maQuyDinh, thuTuNguyenVong, ngayDangKy, trangThaiNguyenVong) VALUES
  ('NV001', 'TS001', 'THPT_1', 'QD2026', 1, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV002', 'TS001', 'THPT_5', 'QD2026', 2, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV003', 'TS001', 'THPT_3', 'QD2026', 3, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV004', 'TS002', 'THPT_4', 'QD2026', 1, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV005', 'TS002', 'THPT_3', 'QD2026', 2, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV006', 'TS002', 'THPT_5', 'QD2026', 3, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV007', 'TS003', 'THPT_3', 'QD2026', 1, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV008', 'TS003', 'THPT_4', 'QD2026', 2, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV009', 'TS003', 'THPT_2', 'QD2026', 3, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV010', 'TS004', 'THPT_1', 'QD2026', 1, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV011', 'TS004', 'THPT_4', 'QD2026', 2, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV012', 'TS004', 'THPT_2', 'QD2026', 3, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV013', 'TS005', 'THPT_5', 'QD2026', 1, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV014', 'TS005', 'THPT_2', 'QD2026', 2, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV015', 'TS005', 'THPT_1', 'QD2026', 3, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV016', 'TS006', 'THPT_5', 'QD2026', 1, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV017', 'TS006', 'THPT_2', 'QD2026', 2, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV018', 'TS006', 'THPT_4', 'QD2026', 3, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV019', 'TS007', 'THPT_5', 'QD2026', 1, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV020', 'TS007', 'THPT_3', 'QD2026', 2, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV021', 'TS007', 'THPT_2', 'QD2026', 3, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV022', 'TS008', 'THPT_5', 'QD2026', 1, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV023', 'TS008', 'THPT_2', 'QD2026', 2, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV024', 'TS008', 'THPT_3', 'QD2026', 3, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV025', 'TS009', 'THPT_1', 'QD2026', 1, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV026', 'TS009', 'THPT_4', 'QD2026', 2, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV027', 'TS009', 'THPT_5', 'QD2026', 3, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV028', 'TS010', 'THPT_2', 'QD2026', 1, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV029', 'TS010', 'THPT_5', 'QD2026', 2, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV030', 'TS010', 'THPT_1', 'QD2026', 3, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV031', 'TS011', 'THPT_5', 'QD2026', 1, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV032', 'TS011', 'THPT_1', 'QD2026', 2, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV033', 'TS011', 'THPT_2', 'QD2026', 3, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV034', 'TS012', 'THPT_2', 'QD2026', 1, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV035', 'TS012', 'THPT_4', 'QD2026', 2, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV036', 'TS012', 'THPT_3', 'QD2026', 3, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV037', 'TS013', 'THPT_1', 'QD2026', 1, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV038', 'TS013', 'THPT_2', 'QD2026', 2, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV039', 'TS013', 'THPT_3', 'QD2026', 3, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV040', 'TS014', 'THPT_5', 'QD2026', 1, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV041', 'TS014', 'THPT_2', 'QD2026', 2, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV042', 'TS014', 'THPT_3', 'QD2026', 3, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV043', 'TS015', 'THPT_2', 'QD2026', 1, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV044', 'TS015', 'THPT_5', 'QD2026', 2, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV045', 'TS015', 'THPT_3', 'QD2026', 3, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV046', 'TS016', 'THPT_2', 'QD2026', 1, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV047', 'TS016', 'THPT_4', 'QD2026', 2, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV048', 'TS016', 'THPT_5', 'QD2026', 3, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV049', 'TS017', 'THPT_4', 'QD2026', 1, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV050', 'TS017', 'THPT_5', 'QD2026', 2, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV051', 'TS017', 'THPT_1', 'QD2026', 3, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV052', 'TS018', 'THPT_4', 'QD2026', 1, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV053', 'TS018', 'THPT_3', 'QD2026', 2, '2026-05-10 09:00:00', 'Chờ xử lý'),
  ('NV054', 'TS018', 'THPT_1', 'QD2026', 3, '2026-05-10 09:00:00', 'Chờ xử lý');

INSERT INTO KetQuaXetTuyen (maKetQua, maThiSinh, maNguyenVong, diemXetTuyen, trangThaiXetTuyen, maTruongTHPT) VALUES
  ('KQXT001', 'TS001', 'NV001', NULL, 'Chưa xét', NULL),
  ('KQXT002', 'TS001', 'NV002', NULL, 'Chưa xét', NULL),
  ('KQXT003', 'TS001', 'NV003', NULL, 'Chưa xét', NULL),
  ('KQXT004', 'TS002', 'NV004', NULL, 'Chưa xét', NULL),
  ('KQXT005', 'TS002', 'NV005', NULL, 'Chưa xét', NULL),
  ('KQXT006', 'TS002', 'NV006', NULL, 'Chưa xét', NULL),
  ('KQXT007', 'TS003', 'NV007', NULL, 'Chưa xét', NULL),
  ('KQXT008', 'TS003', 'NV008', NULL, 'Chưa xét', NULL),
  ('KQXT009', 'TS003', 'NV009', NULL, 'Chưa xét', NULL),
  ('KQXT010', 'TS004', 'NV010', NULL, 'Chưa xét', NULL),
  ('KQXT011', 'TS004', 'NV011', NULL, 'Chưa xét', NULL),
  ('KQXT012', 'TS004', 'NV012', NULL, 'Chưa xét', NULL),
  ('KQXT013', 'TS005', 'NV013', NULL, 'Chưa xét', NULL),
  ('KQXT014', 'TS005', 'NV014', NULL, 'Chưa xét', NULL),
  ('KQXT015', 'TS005', 'NV015', NULL, 'Chưa xét', NULL),
  ('KQXT016', 'TS006', 'NV016', NULL, 'Chưa xét', NULL),
  ('KQXT017', 'TS006', 'NV017', NULL, 'Chưa xét', NULL),
  ('KQXT018', 'TS006', 'NV018', NULL, 'Chưa xét', NULL),
  ('KQXT019', 'TS007', 'NV019', NULL, 'Chưa xét', NULL),
  ('KQXT020', 'TS007', 'NV020', NULL, 'Chưa xét', NULL),
  ('KQXT021', 'TS007', 'NV021', NULL, 'Chưa xét', NULL),
  ('KQXT022', 'TS008', 'NV022', NULL, 'Chưa xét', NULL),
  ('KQXT023', 'TS008', 'NV023', NULL, 'Chưa xét', NULL),
  ('KQXT024', 'TS008', 'NV024', NULL, 'Chưa xét', NULL),
  ('KQXT025', 'TS009', 'NV025', NULL, 'Chưa xét', NULL),
  ('KQXT026', 'TS009', 'NV026', NULL, 'Chưa xét', NULL),
  ('KQXT027', 'TS009', 'NV027', NULL, 'Chưa xét', NULL),
  ('KQXT028', 'TS010', 'NV028', NULL, 'Chưa xét', NULL),
  ('KQXT029', 'TS010', 'NV029', NULL, 'Chưa xét', NULL),
  ('KQXT030', 'TS010', 'NV030', NULL, 'Chưa xét', NULL),
  ('KQXT031', 'TS011', 'NV031', NULL, 'Chưa xét', NULL),
  ('KQXT032', 'TS011', 'NV032', NULL, 'Chưa xét', NULL),
  ('KQXT033', 'TS011', 'NV033', NULL, 'Chưa xét', NULL),
  ('KQXT034', 'TS012', 'NV034', NULL, 'Chưa xét', NULL),
  ('KQXT035', 'TS012', 'NV035', NULL, 'Chưa xét', NULL),
  ('KQXT036', 'TS012', 'NV036', NULL, 'Chưa xét', NULL),
  ('KQXT037', 'TS013', 'NV037', NULL, 'Chưa xét', NULL),
  ('KQXT038', 'TS013', 'NV038', NULL, 'Chưa xét', NULL),
  ('KQXT039', 'TS013', 'NV039', NULL, 'Chưa xét', NULL),
  ('KQXT040', 'TS014', 'NV040', NULL, 'Chưa xét', NULL),
  ('KQXT041', 'TS014', 'NV041', NULL, 'Chưa xét', NULL),
  ('KQXT042', 'TS014', 'NV042', NULL, 'Chưa xét', NULL),
  ('KQXT043', 'TS015', 'NV043', NULL, 'Chưa xét', NULL),
  ('KQXT044', 'TS015', 'NV044', NULL, 'Chưa xét', NULL),
  ('KQXT045', 'TS015', 'NV045', NULL, 'Chưa xét', NULL),
  ('KQXT046', 'TS016', 'NV046', NULL, 'Chưa xét', NULL),
  ('KQXT047', 'TS016', 'NV047', NULL, 'Chưa xét', NULL),
  ('KQXT048', 'TS016', 'NV048', NULL, 'Chưa xét', NULL),
  ('KQXT049', 'TS017', 'NV049', NULL, 'Chưa xét', NULL),
  ('KQXT050', 'TS017', 'NV050', NULL, 'Chưa xét', NULL),
  ('KQXT051', 'TS017', 'NV051', NULL, 'Chưa xét', NULL),
  ('KQXT052', 'TS018', 'NV052', NULL, 'Chưa xét', NULL),
  ('KQXT053', 'TS018', 'NV053', NULL, 'Chưa xét', NULL),
  ('KQXT054', 'TS018', 'NV054', NULL, 'Chưa xét', NULL);

INSERT INTO DanhSachKetQuaTuyenSinh (maDanhSach, namTuyenSinh, maDot, trangThaiPheDuyet, trangThaiCongBo) VALUES
  ('DS2026', 2026, 'DOT2026', 'Chưa phê duyệt', 'Chưa công bố');
