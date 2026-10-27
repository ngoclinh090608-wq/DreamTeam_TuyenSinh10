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
