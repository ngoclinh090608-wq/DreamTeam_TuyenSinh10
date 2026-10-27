-- 6.11. fn_DemBaiThiChuaCham (Nguyễn Thị Ngọc Linh): số bài của kỳ thi chưa có kết quả chấm.
DROP FUNCTION IF EXISTS fn_DemBaiThiChuaCham;
DELIMITER $$
CREATE FUNCTION fn_DemBaiThiChuaCham(p_maKyThi VARCHAR(20))
RETURNS INT READS SQL DATA
BEGIN
  RETURN (SELECT COUNT(*) FROM BaiThi b
          LEFT JOIN KetQuaChamThi k ON k.maBaiThi = b.maBaiThi
          WHERE b.maKyThi = p_maKyThi AND (k.maKetQuaChamThi IS NULL OR b.trangThaiCham <> 'Đã chấm'));
END$$
DELIMITER ;
