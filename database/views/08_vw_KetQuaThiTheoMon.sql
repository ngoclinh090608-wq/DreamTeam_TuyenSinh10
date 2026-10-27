-- 4.8. vw_KetQuaThiTheoMon (Nguyễn Thị Ngọc Linh)
-- Điểm từng môn của thí sinh và trạng thái công bố. Bài chưa chấm có diemThi = NULL.
CREATE OR REPLACE VIEW vw_KetQuaThiTheoMon AS
SELECT t.maThiSinh, t.hoTen, b.maKyThi, m.tenMonThi, b.maBaiThi,
       k.diemThi, b.trangThaiCham, k.trangThaiCongBo
FROM BaiThi b
JOIN ThiSinh t ON t.maThiSinh = b.maThiSinh
JOIN MonThi m ON m.maMonThi = b.maMonThi
LEFT JOIN KetQuaChamThi k ON k.maBaiThi = b.maBaiThi;
