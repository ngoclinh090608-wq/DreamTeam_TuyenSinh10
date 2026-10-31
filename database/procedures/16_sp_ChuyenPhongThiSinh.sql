-- 5.16. sp_ChuyenPhongThiSinh (Nguyễn Thị Thúy Vy) – UC07.
-- Chuyển thí sinh đã phân sang phòng mới, kiểm tra sức chứa phòng mới.
DROP PROCEDURE IF EXISTS sp_ChuyenPhongThiSinh;
DELIMITER $$
CREATE PROCEDURE sp_ChuyenPhongThiSinh(
  IN  p_maPhanPhong VARCHAR(20),
  IN  p_maPhongMoi VARCHAR(20),
  IN  p_lyDo VARCHAR(255),
  OUT p_thongBao VARCHAR(200)
)
proc: BEGIN
  DECLARE v_phongCu VARCHAR(20);
  DECLARE v_sucChua INT DEFAULT 0;
  DECLARE v_daPhan INT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SET p_thongBao = 'Không thể chuyển phòng thi cho thí sinh.';
  END;

  SET p_thongBao = '';

  IF p_maPhanPhong IS NULL OR TRIM(p_maPhanPhong) = '' OR
     p_maPhongMoi IS NULL OR TRIM(p_maPhongMoi) = '' THEN
    SET p_thongBao = 'Thông tin chuyển phòng không hợp lệ.';
    LEAVE proc;
  END IF;

  -- Kiểm tra bản ghi phân phòng
  SELECT maPhong INTO v_phongCu
  FROM PhanPhongThi
  WHERE maPhanPhong = p_maPhanPhong AND trangThai = 'Đã phân';
  IF v_phongCu IS NULL THEN
    SET p_thongBao = 'Không tìm thấy thông tin phân phòng hợp lệ.';
    LEAVE proc;
  END IF;

  IF v_phongCu = p_maPhongMoi THEN
    SET p_thongBao = 'Phòng thi mới trùng với phòng thi hiện tại.';
    LEAVE proc;
  END IF;

  -- Kiểm tra phòng mới và sức chứa
  SELECT sucChua INTO v_sucChua FROM PhongThi WHERE maPhong = p_maPhongMoi;
  IF v_sucChua IS NULL OR v_sucChua <= 0 THEN
    SET p_thongBao = 'Phòng thi mới không tồn tại.';
    LEAVE proc;
  END IF;

  SELECT COUNT(*) INTO v_daPhan
  FROM PhanPhongThi
  WHERE maPhong = p_maPhongMoi AND trangThai = 'Đã phân';
  IF v_daPhan >= v_sucChua THEN
    SET p_thongBao = 'Sức chứa phòng thi không đủ.';
    LEAVE proc;
  END IF;

  START TRANSACTION;
  UPDATE PhanPhongThi
  SET maPhong = p_maPhongMoi, ngayPhan = NOW(), trangThai = 'Đã phân'
  WHERE maPhanPhong = p_maPhanPhong;
  COMMIT;

  SET p_thongBao = 'Chuyển phòng thi cho thí sinh thành công.';
END$$
DELIMITER ;

