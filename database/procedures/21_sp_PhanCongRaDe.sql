-- 5.21. sp_PhanCongRaDe (Nguyễn Thị Thúy Vy) – UC08.
-- Phân công cán bộ ra đề thi theo môn thi và kỳ thi.
DROP PROCEDURE IF EXISTS sp_PhanCongRaDe;
DELIMITER $$
CREATE PROCEDURE sp_PhanCongRaDe(
  IN  p_maPhanCong VARCHAR(20),
  IN  p_maCanBo VARCHAR(20),
  IN  p_maMonThi VARCHAR(20),
  IN  p_maKyThi VARCHAR(20),
  IN  p_thongTinNhiemVu VARCHAR(255),
  OUT p_thongBao VARCHAR(200)
)
proc: BEGIN
  DECLARE v_tonTai INT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SET p_thongBao = 'Không thể lưu phân công ra đề thi.';
  END;

  SET p_thongBao = '';

  IF p_maPhanCong IS NULL OR TRIM(p_maPhanCong) = '' OR
     p_maCanBo IS NULL OR TRIM(p_maCanBo) = '' OR
     p_maMonThi IS NULL OR TRIM(p_maMonThi) = '' OR
     p_maKyThi IS NULL OR TRIM(p_maKyThi) = '' OR
     p_thongTinNhiemVu IS NULL OR TRIM(p_thongTinNhiemVu) = '' THEN
    SET p_thongBao = 'Thông tin phân công ra đề không hợp lệ.';
    LEAVE proc;
  END IF;

  -- Kiểm tra kỳ thi
  SELECT COUNT(*) INTO v_tonTai FROM KyThi WHERE maKyThi = p_maKyThi;
  IF v_tonTai = 0 THEN
    SET p_thongBao = 'Kỳ thi không tồn tại trong hệ thống.';
    LEAVE proc;
  END IF;

  -- Kiểm tra môn thi
  SELECT COUNT(*) INTO v_tonTai FROM MonThi WHERE maMonThi = p_maMonThi;
  IF v_tonTai = 0 THEN
    SET p_thongBao = 'Môn thi không tồn tại trong hệ thống.';
    LEAVE proc;
  END IF;

  -- Kiểm tra cán bộ
  SELECT COUNT(*) INTO v_tonTai FROM CanBo WHERE maCanBo = p_maCanBo;
  IF v_tonTai = 0 THEN
    SET p_thongBao = 'Cán bộ không tồn tại trong hệ thống.';
    LEAVE proc;
  END IF;

  START TRANSACTION;
  INSERT INTO PhanCongRaDe (maPhanCong, maKyThi, maMonThi, maCanBo, thoiGianThucHien, thongTinNhiemVu)
  VALUES (p_maPhanCong, p_maKyThi, p_maMonThi, p_maCanBo, NOW(), p_thongTinNhiemVu)
  ON DUPLICATE KEY UPDATE
    thongTinNhiemVu = p_thongTinNhiemVu,
    thoiGianThucHien = NOW();
  COMMIT;

  SET p_thongBao = 'Phân công ra đề thi thành công.';
END$$
DELIMITER ;

