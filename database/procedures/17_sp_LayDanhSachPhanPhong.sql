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

