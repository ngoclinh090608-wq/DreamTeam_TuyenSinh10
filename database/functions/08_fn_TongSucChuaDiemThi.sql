-- 6.8. fn_TongSucChuaDiemThi (theo báo cáo Tuần 08)
-- Tính tổng sức chứa phòng tại một địa điểm thi.
DROP FUNCTION IF EXISTS fn_TongSucChuaDiemThi;
DELIMITER $$
CREATE FUNCTION fn_TongSucChuaDiemThi(p_maDiemThi VARCHAR(20))
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
  DECLARE v_sum INT;
  SELECT COALESCE(SUM(sucChua), 0) INTO v_sum FROM PhongThi WHERE maDiemThi = p_maDiemThi;
  RETURN v_sum;
END$$
DELIMITER ;

