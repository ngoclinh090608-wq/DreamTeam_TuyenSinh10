-- 5.27. sp_CongBoKetQuaChamThi (Nguyễn Thị Ngọc Linh) – UC15.
-- Đổi trạng thái công bố các kết quả chấm của một kỳ thi. Điểm thi không thay đổi.
-- p_maMonThi: thêm so với báo cáo để công bố theo từng môn; truyền NULL để áp dụng cho cả kỳ thi.
-- Khi công bố: chặn nếu đã công bố hết hoặc còn bài chưa chấm.
DROP PROCEDURE IF EXISTS sp_CongBoKetQuaChamThi;
DELIMITER $$
CREATE PROCEDURE sp_CongBoKetQuaChamThi(
  IN  p_maKyThi VARCHAR(20),
  IN  p_maMonThi VARCHAR(20),
  IN  p_trangThaiMoi VARCHAR(50),
  OUT p_soBaiDuocCapNhat INT,
  OUT p_thongBao VARCHAR(200)
)
proc: BEGIN
  DECLARE v_daCham INT DEFAULT 0;
  DECLARE v_chuaCham INT DEFAULT 0;
  DECLARE v_canDoi INT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SET p_soBaiDuocCapNhat = 0;
    SET p_thongBao = 'Không thể công bố điểm thi tại thời điểm hiện tại. Vui lòng thử lại sau.';
  END;

  SET p_soBaiDuocCapNhat = 0;
  IF p_trangThaiMoi IS NULL OR p_trangThaiMoi NOT IN ('Đã công bố', 'Chưa công bố') THEN
    SET p_thongBao = 'Trạng thái công bố không hợp lệ.';
    LEAVE proc;
  END IF;

  START TRANSACTION;
  SELECT COALESCE(SUM(k.maKetQuaChamThi IS NOT NULL AND b.trangThaiCham = 'Đã chấm'), 0),
         COALESCE(SUM(k.maKetQuaChamThi IS NULL OR b.trangThaiCham <> 'Đã chấm'), 0),
         COALESCE(SUM(k.maKetQuaChamThi IS NOT NULL AND k.trangThaiCongBo <> p_trangThaiMoi), 0)
    INTO v_daCham, v_chuaCham, v_canDoi
  FROM BaiThi b LEFT JOIN KetQuaChamThi k ON k.maBaiThi = b.maBaiThi
  WHERE b.maKyThi = p_maKyThi AND (p_maMonThi IS NULL OR b.maMonThi = p_maMonThi)
  FOR UPDATE;

  IF v_daCham = 0 THEN
    ROLLBACK;
    SET p_thongBao = 'Kết quả chấm thi chưa đủ điều kiện công bố.';
    LEAVE proc;
  END IF;
  IF v_canDoi = 0 THEN
    ROLLBACK;
    SET p_thongBao = IF(p_trangThaiMoi = 'Đã công bố', 'Kết quả chấm thi đã được công bố.', 'Kết quả chấm thi chưa được công bố.');
    LEAVE proc;
  END IF;
  IF p_trangThaiMoi = 'Đã công bố' AND v_chuaCham > 0 THEN
    ROLLBACK;
    SET p_thongBao = 'Kết quả chấm thi chưa đủ điều kiện công bố.';
    LEAVE proc;
  END IF;

  UPDATE KetQuaChamThi k JOIN BaiThi b ON b.maBaiThi = k.maBaiThi
  SET k.trangThaiCongBo = p_trangThaiMoi
  WHERE b.maKyThi = p_maKyThi AND (p_maMonThi IS NULL OR b.maMonThi = p_maMonThi)
    AND k.trangThaiCongBo <> p_trangThaiMoi;
  SET p_soBaiDuocCapNhat = ROW_COUNT();
  COMMIT;

  SET p_thongBao = IF(p_trangThaiMoi = 'Đã công bố', 'Công bố điểm thi thành công.', 'Đã chuyển kết quả thi về trạng thái chưa công bố.');
END$$
DELIMITER ;
