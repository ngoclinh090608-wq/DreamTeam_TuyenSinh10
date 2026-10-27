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
