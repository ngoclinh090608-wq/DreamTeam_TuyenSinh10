-- 6.13. fn_DiemTrungBinhTheoMon (Nguyễn Thị Ngọc Linh): điểm trung bình các bài đã chấm của một môn trong kỳ.
-- Trả về NULL nếu môn chưa có bài nào được chấm.
DROP FUNCTION IF EXISTS fn_DiemTrungBinhTheoMon;
DELIMITER $$
CREATE FUNCTION fn_DiemTrungBinhTheoMon(p_maKyThi VARCHAR(20), p_maMonThi VARCHAR(20))
RETURNS DECIMAL(9,2) READS SQL DATA
BEGIN
  RETURN (SELECT AVG(k.diemThi) FROM KetQuaChamThi k JOIN BaiThi b ON b.maBaiThi = k.maBaiThi
          WHERE b.maKyThi = p_maKyThi AND b.maMonThi = p_maMonThi);
END$$
DELIMITER ;
