-- DEMO ONLY. Export dreamteam_tuyensinh10_demo before importing this file.
-- Run after 00, 01 and BoSung_5250_BaiThi_4870_KetQua_DEMO.sql.
-- Removes TS001..TS020 and their dependent records, not the new TSD students.
-- Preserves staff accounts, roles, councils, quotas, and all new exam scores/history.
SET NAMES utf8mb4;
USE dreamteam_tuyensinh10_demo;
DELIMITER $$
DROP PROCEDURE IF EXISTS sp_demo_remove_old_seed $$
CREATE PROCEDURE sp_demo_remove_old_seed()
BEGIN
 DECLARE n INT DEFAULT 0;
 DECLARE score_count INT;
 DECLARE history_count INT;
 DECLARE account_count INT;
 DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
 START TRANSACTION;
 SELECT COUNT(*) INTO n FROM KyThi WHERE maKyThi='KTDEMO26' AND maDot='DOT2026';
 IF n<>1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Thieu KTDEMO26. Import file BoSung truoc; chua xoa du lieu.'; END IF;
 SELECT COUNT(*) INTO n FROM ThiSinh WHERE maThiSinh REGEXP '^TSD[0-9]{6}$' AND CAST(SUBSTRING(maThiSinh,4) AS UNSIGNED) BETWEEN 1 AND 1750;
 IF n<>1750 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Thieu 1750 hoc sinh moi; dung don du lieu.'; END IF;
 SELECT COUNT(*) INTO n FROM NguyenVong WHERE maThiSinh REGEXP '^TSD[0-9]{6}$';
 IF n<>5250 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Chua du 5250 nguyen vong moi.'; END IF;
 SELECT COUNT(*) INTO n FROM BaiThi WHERE maKyThi='KTDEMO26';
 IF n<>5250 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Chua du 5250 bai thi moi.'; END IF;
 SELECT COUNT(*) INTO score_count FROM KetQuaChamThi k JOIN BaiThi b ON b.maBaiThi=k.maBaiThi WHERE b.maKyThi='KTDEMO26';
 IF score_count<4870 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Chua du 4870 ket qua moi.'; END IF;
 SELECT COUNT(*) INTO history_count FROM LichSuChinhSuaDiem l JOIN KetQuaChamThi k ON k.maKetQuaChamThi=l.maKetQuaChamThi JOIN BaiThi b ON b.maBaiThi=k.maBaiThi WHERE b.maKyThi='KTDEMO26';
 SELECT COUNT(*) INTO account_count FROM TaiKhoan;
 SELECT COUNT(*) INTO n FROM BaiThi WHERE maKyThi='KT2026' AND maThiSinh NOT IN (SELECT maThiSinh FROM ThiSinh WHERE maThiSinh IN ('TS001','TS002','TS003','TS004','TS005','TS006','TS007','TS008','TS009','TS010','TS011','TS012','TS013','TS014','TS015','TS016','TS017','TS018','TS019','TS020'));
 IF n<>0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='KT2026 co bai thi ngoai 20 hoc sinh cu; can kiem tra rieng.'; END IF;
 DELETE FROM MinhChungPhucKhao WHERE maKetQua IN (SELECT maKetQua FROM KetQuaPhucKhao WHERE maYeuCau IN (SELECT maYeuCau FROM YeuCauPhucKhao WHERE maThiSinh IN (SELECT maThiSinh FROM ThiSinh WHERE maThiSinh IN ('TS001','TS002','TS003','TS004','TS005','TS006','TS007','TS008','TS009','TS010','TS011','TS012','TS013','TS014','TS015','TS016','TS017','TS018','TS019','TS020')) OR maBaiThi IN (SELECT maBaiThi FROM BaiThi WHERE maKyThi='KT2026')));
 DELETE FROM BienBanPhucKhao WHERE maKetQua IN (SELECT maKetQua FROM KetQuaPhucKhao WHERE maYeuCau IN (SELECT maYeuCau FROM YeuCauPhucKhao WHERE maThiSinh IN (SELECT maThiSinh FROM ThiSinh WHERE maThiSinh IN ('TS001','TS002','TS003','TS004','TS005','TS006','TS007','TS008','TS009','TS010','TS011','TS012','TS013','TS014','TS015','TS016','TS017','TS018','TS019','TS020')) OR maBaiThi IN (SELECT maBaiThi FROM BaiThi WHERE maKyThi='KT2026')));
DELETE FROM KetQuaPhucKhao WHERE maYeuCau IN (SELECT maYeuCau FROM YeuCauPhucKhao WHERE maThiSinh IN (SELECT maThiSinh FROM ThiSinh WHERE maThiSinh IN ('TS001','TS002','TS003','TS004','TS005','TS006','TS007','TS008','TS009','TS010','TS011','TS012','TS013','TS014','TS015','TS016','TS017','TS018','TS019','TS020')) OR maBaiThi IN (SELECT maBaiThi FROM BaiThi WHERE maKyThi='KT2026'));
DELETE FROM YeuCauPhucKhao WHERE maThiSinh IN (SELECT maThiSinh FROM ThiSinh WHERE maThiSinh IN ('TS001','TS002','TS003','TS004','TS005','TS006','TS007','TS008','TS009','TS010','TS011','TS012','TS013','TS014','TS015','TS016','TS017','TS018','TS019','TS020')) OR maBaiThi IN (SELECT maBaiThi FROM BaiThi WHERE maKyThi='KT2026');
DELETE FROM LichSuChinhSuaDiem WHERE maKetQuaChamThi IN (SELECT maKetQuaChamThi FROM KetQuaChamThi WHERE maBaiThi IN (SELECT maBaiThi FROM BaiThi WHERE maKyThi='KT2026'));
DELETE FROM KetQuaChamThi WHERE maBaiThi IN (SELECT maBaiThi FROM BaiThi WHERE maKyThi='KT2026');
DELETE FROM BaiThi WHERE maKyThi='KT2026';
DELETE FROM LichSuCapNhatNhapHoc WHERE maTinhTrang IN (SELECT maTinhTrang FROM TinhTrangNhapHoc WHERE maKetQua IN (SELECT maKetQua FROM KetQuaXetTuyen WHERE maThiSinh IN (SELECT maThiSinh FROM ThiSinh WHERE maThiSinh IN ('TS001','TS002','TS003','TS004','TS005','TS006','TS007','TS008','TS009','TS010','TS011','TS012','TS013','TS014','TS015','TS016','TS017','TS018','TS019','TS020'))));
DELETE FROM TinhTrangNhapHoc WHERE maKetQua IN (SELECT maKetQua FROM KetQuaXetTuyen WHERE maThiSinh IN (SELECT maThiSinh FROM ThiSinh WHERE maThiSinh IN ('TS001','TS002','TS003','TS004','TS005','TS006','TS007','TS008','TS009','TS010','TS011','TS012','TS013','TS014','TS015','TS016','TS017','TS018','TS019','TS020')));
DELETE FROM KetQuaTuyenSinh WHERE maKetQuaXetTuyen IN (SELECT maKetQua FROM KetQuaXetTuyen WHERE maThiSinh IN (SELECT maThiSinh FROM ThiSinh WHERE maThiSinh IN ('TS001','TS002','TS003','TS004','TS005','TS006','TS007','TS008','TS009','TS010','TS011','TS012','TS013','TS014','TS015','TS016','TS017','TS018','TS019','TS020')));
DELETE FROM KetQuaXetTuyen WHERE maThiSinh IN (SELECT maThiSinh FROM ThiSinh WHERE maThiSinh IN ('TS001','TS002','TS003','TS004','TS005','TS006','TS007','TS008','TS009','TS010','TS011','TS012','TS013','TS014','TS015','TS016','TS017','TS018','TS019','TS020'));
DELETE FROM NguyenVong WHERE maThiSinh IN (SELECT maThiSinh FROM ThiSinh WHERE maThiSinh IN ('TS001','TS002','TS003','TS004','TS005','TS006','TS007','TS008','TS009','TS010','TS011','TS012','TS013','TS014','TS015','TS016','TS017','TS018','TS019','TS020'));
DELETE FROM PhanPhongThi WHERE maThiSinh IN (SELECT maThiSinh FROM ThiSinh WHERE maThiSinh IN ('TS001','TS002','TS003','TS004','TS005','TS006','TS007','TS008','TS009','TS010','TS011','TS012','TS013','TS014','TS015','TS016','TS017','TS018','TS019','TS020'));
-- Keep account IDs, usernames, passwords and roles. Bind old student logins to new students.
 UPDATE TaiKhoan a JOIN ThiSinh t ON t.maThiSinh=CONCAT('TSD',LPAD(CAST(SUBSTRING(a.maThiSinh,3) AS UNSIGNED),6,'0'))
 SET a.maThiSinh=t.maThiSinh, a.hoTen=t.hoTen
 WHERE a.maThiSinh REGEXP '^TS00[1-9]$|^TS01[0-9]$|^TS020$';
DELETE FROM ThiSinh WHERE maThiSinh IN ('TS001','TS002','TS003','TS004','TS005','TS006','TS007','TS008','TS009','TS010','TS011','TS012','TS013','TS014','TS015','TS016','TS017','TS018','TS019','TS020');
-- Remove rooms/batches from the retired exam; retain staff and councils for their roles.
 DELETE FROM PhanCongCoiThi WHERE maPhong IN (SELECT r.maPhong FROM PhongThi r JOIN DiemThi d ON d.maDiemThi=r.maDiemThi WHERE d.maKyThi='KT2026');
 DELETE FROM LoBaiThi WHERE maPhong IN (SELECT r.maPhong FROM PhongThi r JOIN DiemThi d ON d.maDiemThi=r.maDiemThi WHERE d.maKyThi='KT2026');
 DELETE FROM PhongThi WHERE maDiemThi IN (SELECT maDiemThi FROM DiemThi WHERE maKyThi='KT2026');
 DELETE FROM DiemThi WHERE maKyThi='KT2026';
 UPDATE HoiDongThi SET maKyThi='KTDEMO26' WHERE maKyThi='KT2026';
 UPDATE PhanCongRaDe SET maKyThi='KTDEMO26' WHERE maKyThi='KT2026';
 DELETE FROM KyThi WHERE maKyThi='KT2026';
 UPDATE KyThi SET tenKyThi='Kỳ thi tuyển sinh lớp 10 năm 2026' WHERE maKyThi='KTDEMO26';
 SELECT COUNT(*) INTO n FROM KetQuaChamThi k JOIN BaiThi b ON b.maBaiThi=k.maBaiThi WHERE b.maKyThi='KTDEMO26';
 IF n<>score_count THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Ket qua moi thay doi; rollback.'; END IF;
 SELECT COUNT(*) INTO n FROM LichSuChinhSuaDiem l JOIN KetQuaChamThi k ON k.maKetQuaChamThi=l.maKetQuaChamThi JOIN BaiThi b ON b.maBaiThi=k.maBaiThi WHERE b.maKyThi='KTDEMO26';
 IF n<>history_count THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Lich su diem moi thay doi; rollback.'; END IF;
 SELECT COUNT(*) INTO n FROM TaiKhoan;
 IF n<>account_count THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Tai khoan thay doi; rollback.'; END IF;
 COMMIT;
END $$
DELIMITER ;
CALL sp_demo_remove_old_seed();
DROP PROCEDURE IF EXISTS sp_demo_remove_old_seed;
SELECT COUNT(*) AS hocSinh FROM ThiSinh;
SELECT COUNT(*) AS nguyenVong FROM NguyenVong;
SELECT COUNT(*) AS baiThi FROM BaiThi;
SELECT COUNT(*) AS ketQuaCham FROM KetQuaChamThi;
SELECT maKyThi,tenKyThi FROM KyThi;
