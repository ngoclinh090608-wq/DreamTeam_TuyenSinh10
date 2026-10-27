-- 6.10. fn_DemBaiThiTheoMon (Nguyễn Thị Ngọc Linh): số bài thi theo kỳ thi và môn.
DROP FUNCTION IF EXISTS fn_DemBaiThiTheoMon;
DELIMITER $$
CREATE FUNCTION fn_DemBaiThiTheoMon(p_maKyThi VARCHAR(20), p_maMonThi VARCHAR(20))
RETURNS INT READS SQL DATA
BEGIN
  RETURN (SELECT COUNT(*) FROM BaiThi WHERE maKyThi = p_maKyThi AND maMonThi = p_maMonThi);
END$$
DELIMITER ;
