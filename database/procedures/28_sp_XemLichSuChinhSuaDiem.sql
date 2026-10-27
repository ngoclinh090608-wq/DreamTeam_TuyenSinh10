-- 5.28. sp_XemLichSuChinhSuaDiem (Nguyễn Thị Ngọc Linh) – UC12.
-- Nhật ký thay đổi điểm của một bài thi, mới nhất trước. Không có dòng nào nghĩa là chưa có lịch sử chỉnh sửa.
DROP PROCEDURE IF EXISTS sp_XemLichSuChinhSuaDiem;
DELIMITER $$
CREATE PROCEDURE sp_XemLichSuChinhSuaDiem(IN p_maBaiThi VARCHAR(20))
BEGIN
  SELECT l.thoiGianChinhSua, l.diemTruocChinhSua, l.diemSauChinhSua, l.nguoiThucHien, l.lyDoChinhSua
  FROM LichSuChinhSuaDiem l
  JOIN KetQuaChamThi k ON k.maKetQuaChamThi = l.maKetQuaChamThi
  WHERE k.maBaiThi = p_maBaiThi
  ORDER BY l.thoiGianChinhSua DESC, l.maLichSu DESC;
END$$
DELIMITER ;
