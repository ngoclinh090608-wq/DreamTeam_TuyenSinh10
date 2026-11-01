-- Chọn đúng database trước khi chạy; không reset hoặc thay đổi dữ liệu cũ.
SET NAMES utf8mb4;
-- UC17: lưu vết cấu hình, xử lý bằng điểm và người xác nhận từng lần xét.
CREATE TABLE IF NOT EXISTS XetTuyenLanChay (
  maLan VARCHAR(20) NOT NULL PRIMARY KEY,
  maKyThi VARCHAR(20) NOT NULL,
  maDanhSach VARCHAR(20) NOT NULL,
  nguoiThucHien VARCHAR(100) NOT NULL,
  thoiGian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  thongSo LONGTEXT NOT NULL,
  tongTrungTuyen INT NOT NULL,
  CONSTRAINT fk_xtlc_ky FOREIGN KEY (maKyThi) REFERENCES KyThi(maKyThi),
  CONSTRAINT fk_xtlc_ds FOREIGN KEY (maDanhSach) REFERENCES DanhSachKetQuaTuyenSinh(maDanhSach)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
