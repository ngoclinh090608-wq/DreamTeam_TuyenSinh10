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
