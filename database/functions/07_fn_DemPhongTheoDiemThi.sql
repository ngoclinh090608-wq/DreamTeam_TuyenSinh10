-- 6.7. fn_DemPhongTheoDiemThi (theo báo cáo Tuần 08)
-- Đếm số phòng thuộc địa điểm thi.
DROP FUNCTION IF EXISTS fn_DemPhongTheoDiemThi;
DELIMITER $$
CREATE FUNCTION fn_DemPhongTheoDiemThi(p_maDiemThi VARCHAR(20))
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
  DECLARE v_count INT;
  SELECT COUNT(*) INTO v_count FROM PhongThi WHERE maDiemThi = p_maDiemThi;
  RETURN v_count;
END$$
DELIMITER ;

