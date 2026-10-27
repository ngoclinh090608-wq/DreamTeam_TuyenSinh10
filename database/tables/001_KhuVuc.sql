-- Bảng KhuVuc (theo báo cáo Tuần 08)
CREATE TABLE KhuVuc (
  maKhuVuc VARCHAR(20) NOT NULL,
  tenKhuVuc VARCHAR(100) NOT NULL,
  quanHuyen VARCHAR(100) NULL,
  PRIMARY KEY (maKhuVuc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
