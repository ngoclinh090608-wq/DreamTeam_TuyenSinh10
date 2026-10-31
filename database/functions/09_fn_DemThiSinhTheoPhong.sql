-- 6.9. fn_DemThiSinhTheoPhong (theo báo cáo Tuần 08)
-- Đếm số thí sinh đang được phân vào một phòng thi (trạng thái 'Đã phân').
DROP FUNCTION IF EXISTS fn_DemThiSinhTheoPhong;
DELIMITER $$
CREATE FUNCTION fn_DemThiSinhTheoPhong(p_maPhong VARCHAR(20))
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
  DECLARE v_count INT;
  SELECT COUNT(*) INTO v_count
  FROM PhanPhongThi
  WHERE maPhong = p_maPhong AND trangThai = 'Đã phân';
  RETURN v_count;
END$$
DELIMITER ;

