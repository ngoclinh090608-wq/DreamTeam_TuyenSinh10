-- Supplementary FICTIONAL profiles for the local demonstration dataset.
-- Existing nonblank values and all official candidate/exam/score/account data are preserved.
-- 900... identifiers and 000... phone numbers are synthetic, not issued identification/contact data.
-- Email uses the reserved example.test domain. No messages or notifications are sent.
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dreamteam_tuyensinh10_demo;
DELIMITER $$
DROP PROCEDURE IF EXISTS sp_fill_student_profiles $$
CREATE PROCEDURE sp_fill_student_profiles()
BEGIN
 DECLARE n INT DEFAULT 0;
 DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; DROP TEMPORARY TABLE IF EXISTS profile_seed_source; RESIGNAL; END;
 SELECT COUNT(*) INTO n FROM information_schema.tables WHERE table_schema=DATABASE() AND LOWER(table_name) IN ('hosobosung','giadinhthisinh','hoctapthcs');
 IF n<>3 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Can nhap 20261031_student_profile.sql truoc. Chua bo sung du lieu.'; END IF;
 START TRANSACTION;
 DROP TEMPORARY TABLE IF EXISTS profile_seed_source;
 CREATE TEMPORARY TABLE profile_seed_source ENGINE=InnoDB AS
 SELECT t.maThiSinh,t.hoTen,t.ngaySinh,c.quanHuyen,
        CAST(CRC32(t.maThiSinh) AS UNSIGNED) AS seed,
        ROW_NUMBER() OVER (PARTITION BY t.maTruongTHCS ORDER BY t.maThiSinh) AS schoolOrder,
        CAST(900000000000+CRC32(t.maThiSinh) AS CHAR(12)) AS syntheticId
 FROM ThiSinh t JOIN TruongTHCS c ON c.maTruongTHCS=t.maTruongTHCS;
 SELECT COUNT(*)-COUNT(DISTINCT syntheticId) INTO n FROM profile_seed_source;
 IF n>0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Trung ma dinh danh sinh tu dong. Chua thay doi du lieu.'; END IF;
 SELECT COUNT(*) INTO n FROM profile_seed_source s JOIN HoSoBoSung other ON other.soDinhDanh=s.syntheticId AND other.maThiSinh<>s.maThiSinh
 LEFT JOIN HoSoBoSung own ON own.maThiSinh=s.maThiSinh WHERE own.soDinhDanh IS NULL OR TRIM(own.soDinhDanh)='';
 IF n>0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Ma dinh danh du kien da duoc su dung. Chua thay doi du lieu.'; END IF;
 INSERT INTO HoSoBoSung (maThiSinh,gioiTinh,soDinhDanh,noiSinh,danToc,quocTich,tonGiao,dienThoai,email,lop,diaChiThuongTru,choOHienNay,doiTuongUuTien)
 SELECT s.maThiSinh,IF(MOD(s.seed,2)=0,'Nam','Nữ'),s.syntheticId,CONCAT(s.quanHuyen,', TP. Hồ Chí Minh'),'Kinh','Việt Nam','Không',CONCAT('000',LPAD(MOD(s.seed,10000000),7,'0')),CONCAT(LOWER(s.maThiSinh),'@example.test'),CONCAT('9A',CEIL(s.schoolOrder/30)),CONCAT('Số ',MOD(s.seed,180)+1,', đường ',ELT(MOD(s.seed,5)+1,'Hòa Bình','Thanh Niên','Bình Minh','Ánh Dương','Hoa Sen'),', ',s.quanHuyen),CONCAT('Số ',MOD(s.seed,180)+1,', đường ',ELT(MOD(s.seed,5)+1,'Hòa Bình','Thanh Niên','Bình Minh','Ánh Dương','Hoa Sen'),', ',s.quanHuyen),'Không' FROM profile_seed_source s WHERE 1=1
 ON DUPLICATE KEY UPDATE gioiTinh=IF(gioiTinh IS NULL OR TRIM(gioiTinh)='',VALUES(gioiTinh),gioiTinh),
 soDinhDanh=IF(soDinhDanh IS NULL OR TRIM(soDinhDanh)='',VALUES(soDinhDanh),soDinhDanh),
 noiSinh=IF(noiSinh IS NULL OR TRIM(noiSinh)='',VALUES(noiSinh),noiSinh),
 danToc=IF(danToc IS NULL OR TRIM(danToc)='',VALUES(danToc),danToc),
 quocTich=IF(quocTich IS NULL OR TRIM(quocTich)='',VALUES(quocTich),quocTich),
 tonGiao=IF(tonGiao IS NULL OR TRIM(tonGiao)='',VALUES(tonGiao),tonGiao),
 dienThoai=IF(dienThoai IS NULL OR TRIM(dienThoai)='',VALUES(dienThoai),dienThoai),
 email=IF(email IS NULL OR TRIM(email)='',VALUES(email),email),
 lop=IF(lop IS NULL OR TRIM(lop)='',VALUES(lop),lop),
 diaChiThuongTru=IF(diaChiThuongTru IS NULL OR TRIM(diaChiThuongTru)='',VALUES(diaChiThuongTru),diaChiThuongTru),
 choOHienNay=IF(choOHienNay IS NULL OR TRIM(choOHienNay)='',VALUES(choOHienNay),choOHienNay),
 doiTuongUuTien=IF(doiTuongUuTien IS NULL OR TRIM(doiTuongUuTien)='',VALUES(doiTuongUuTien),doiTuongUuTien);
 INSERT INTO GiaDinhThiSinh (maThiSinh,quanHe,hoTen,namSinh,dienThoai)
 SELECT s.maThiSinh,r.quanHe,
 CONCAT(SUBSTRING_INDEX(s.hoTen,' ',1),' ',CASE r.quanHe WHEN 'Me' THEN ELT(MOD(s.seed,5)+1,'Thị Thanh','Thị Lan','Thị Hương','Thị Ngọc','Thị Hoa') WHEN 'Cha' THEN ELT(MOD(s.seed,5)+1,'Văn Hùng','Văn Minh','Văn Hải','Văn Tuấn','Văn Bình') ELSE ELT(MOD(s.seed,5)+1,'Văn Thành','Thị Hạnh','Văn Phúc','Thị Thu','Văn Sơn') END),
 YEAR(s.ngaySinh)-r.ageGap,
 CONCAT('000',LPAD(MOD(s.seed+r.ageGap*7919,10000000),7,'0'))
 FROM profile_seed_source s CROSS JOIN (SELECT 'Me' AS quanHe,26 AS ageGap UNION ALL SELECT 'Cha',29 UNION ALL SELECT 'GiamHo',32) r WHERE 1=1
 ON DUPLICATE KEY UPDATE hoTen=IF(TRIM(GiaDinhThiSinh.hoTen)='',VALUES(hoTen),GiaDinhThiSinh.hoTen),namSinh=COALESCE(GiaDinhThiSinh.namSinh,VALUES(namSinh)),dienThoai=IF(TRIM(GiaDinhThiSinh.dienThoai)='',VALUES(dienThoai),GiaDinhThiSinh.dienThoai);
 INSERT INTO HocTapTHCS (maThiSinh,lop,diemTrungBinh,hocLuc,hanhKiem)
 SELECT s.maThiSinh,g.lop,(55+MOD(s.seed+g.lop*17,41))/10,
 CASE WHEN (55+MOD(s.seed+g.lop*17,41))/10>=8 THEN 'Tốt' WHEN (55+MOD(s.seed+g.lop*17,41))/10>=6.5 THEN 'Khá' ELSE 'Đạt' END,
 IF(MOD(s.seed+g.lop,5)=0,'Khá','Tốt')
 FROM profile_seed_source s CROSS JOIN (SELECT 6 AS lop UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) g WHERE 1=1
 ON DUPLICATE KEY UPDATE diemTrungBinh=COALESCE(diemTrungBinh,VALUES(diemTrungBinh)),hocLuc=IF(TRIM(hocLuc)='',VALUES(hocLuc),hocLuc),hanhKiem=IF(TRIM(hanhKiem)='',VALUES(hanhKiem),hanhKiem);
 DROP TEMPORARY TABLE profile_seed_source;
 COMMIT;
END $$
DELIMITER ;
CALL sp_fill_student_profiles();
DROP PROCEDURE IF EXISTS sp_fill_student_profiles;
SELECT (SELECT COUNT(*) FROM ThiSinh) AS hocSinh,(SELECT COUNT(*) FROM HoSoBoSung) AS hoSoBoSung,(SELECT COUNT(*) FROM GiaDinhThiSinh) AS nguoiThan,(SELECT COUNT(*) FROM HocTapTHCS) AS namHoc;
