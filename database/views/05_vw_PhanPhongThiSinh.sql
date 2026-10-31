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

