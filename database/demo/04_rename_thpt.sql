-- Only the local demo database; does not change IDs, quotas, wishes or scores.
SET NAMES utf8mb4;
USE dreamteam_tuyensinh10_demo;
DELIMITER $$
DROP PROCEDURE IF EXISTS sp_demo_rename_thpt $$
CREATE PROCEDURE sp_demo_rename_thpt()
BEGIN
 DECLARE n INT;
 DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
 START TRANSACTION;
 SELECT COUNT(*) INTO n FROM TruongTHPT WHERE maTruongTHPT IN ('THPT_1','THPT_2','THPT_3','THPT_4','THPT_5');
 IF n<>5 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Thieu ma THPT_1..THPT_5; khong doi ten.'; END IF;
 UPDATE TruongTHPT SET tenTruongTHPT=CASE maTruongTHPT
  WHEN 'THPT_1' THEN 'Trường THPT Sao Mai'
  WHEN 'THPT_2' THEN 'Trường THPT Bình Minh'
  WHEN 'THPT_3' THEN 'Trường THPT Hòa An'
  WHEN 'THPT_4' THEN 'Trường THPT Ánh Dương'
  WHEN 'THPT_5' THEN 'Trường THPT Trần Quốc Khải'
 END WHERE maTruongTHPT IN ('THPT_1','THPT_2','THPT_3','THPT_4','THPT_5');
 COMMIT;
END $$
DELIMITER ;
CALL sp_demo_rename_thpt();
DROP PROCEDURE IF EXISTS sp_demo_rename_thpt;
SELECT maTruongTHPT,tenTruongTHPT FROM TruongTHPT ORDER BY maTruongTHPT;
