-- Additive migration: existing students, exam data and accounts are unchanged.
SET NAMES utf8mb4;
USE dreamteam_tuyensinh10_demo;
CREATE TABLE IF NOT EXISTS HoSoBoSung (
 maThiSinh VARCHAR(20) NOT NULL PRIMARY KEY,
 gioiTinh VARCHAR(10) NULL,
 soDinhDanh VARCHAR(12) NULL,
 noiSinh VARCHAR(150) NOT NULL DEFAULT '',
 danToc VARCHAR(50) NOT NULL DEFAULT '',
 quocTich VARCHAR(50) NOT NULL DEFAULT '',
 tonGiao VARCHAR(50) NOT NULL DEFAULT '',
 dienThoai VARCHAR(20) NOT NULL DEFAULT '',
 email VARCHAR(150) NOT NULL DEFAULT '',
 lop VARCHAR(20) NOT NULL DEFAULT '',
 diaChiThuongTru VARCHAR(500) NOT NULL DEFAULT '',
 choOHienNay VARCHAR(500) NOT NULL DEFAULT '',
 doiTuongUuTien VARCHAR(250) NOT NULL DEFAULT '',
 anhHoSo VARCHAR(80) NULL,
 capNhatLuc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_hsbosung_dinhdanh (soDinhDanh),
 CONSTRAINT fk_hsbosung_thisinh FOREIGN KEY (maThiSinh) REFERENCES ThiSinh(maThiSinh)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS GiaDinhThiSinh (
 maThiSinh VARCHAR(20) NOT NULL,
 quanHe VARCHAR(20) NOT NULL,
 hoTen VARCHAR(100) NOT NULL DEFAULT '',
 namSinh SMALLINT NULL,
 dienThoai VARCHAR(20) NOT NULL DEFAULT '',
 PRIMARY KEY (maThiSinh,quanHe),
 CONSTRAINT ck_giadinh_quanhe CHECK (quanHe IN ('Me','Cha','GiamHo')),
 CONSTRAINT fk_giadinh_thisinh FOREIGN KEY (maThiSinh) REFERENCES ThiSinh(maThiSinh)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS HocTapTHCS (
 maThiSinh VARCHAR(20) NOT NULL,
 lop TINYINT NOT NULL,
 diemTrungBinh DECIMAL(4,2) NULL,
 hocLuc VARCHAR(30) NOT NULL DEFAULT '',
 hanhKiem VARCHAR(30) NOT NULL DEFAULT '',
 PRIMARY KEY (maThiSinh,lop),
 CONSTRAINT ck_hoctap_lop CHECK (lop BETWEEN 6 AND 9),
 CONSTRAINT ck_hoctap_diem CHECK (diemTrungBinh IS NULL OR diemTrungBinh BETWEEN 0 AND 10),
 CONSTRAINT fk_hoctap_thisinh FOREIGN KEY (maThiSinh) REFERENCES ThiSinh(maThiSinh)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
