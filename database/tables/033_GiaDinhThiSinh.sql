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
