-- Read-only verification, ONLY the isolated demo database.
USE dreamteam_tuyensinh10_demo;
SELECT 'THCS_moi' AS kiemTra,5 AS kyVong,COUNT(*) AS thucTe FROM TruongTHCS WHERE maTruongTHCS IN('THCS_DEMO1','THCS_DEMO2','THCS_DEMO3','THCS_DEMO4','THCS_DEMO5')
UNION ALL SELECT 'ThiSinh_moi',1750,COUNT(*) FROM ThiSinh WHERE maThiSinh BETWEEN 'TSD000001' AND 'TSD001750'
UNION ALL SELECT 'NguyenVong_moi',5250,COUNT(*) FROM NguyenVong WHERE maNguyenVong BETWEEN 'NVD0000001' AND 'NVD0005250';
SELECT c.maTruongTHCS,c.tenTruongTHCS,COUNT(t.maThiSinh) AS soHocSinh FROM TruongTHCS c LEFT JOIN ThiSinh t ON t.maTruongTHCS=c.maTruongTHCS WHERE c.maTruongTHCS IN('THCS_DEMO1','THCS_DEMO2','THCS_DEMO3','THCS_DEMO4','THCS_DEMO5') GROUP BY c.maTruongTHCS,c.tenTruongTHCS ORDER BY c.maTruongTHCS;
SELECT maTruongTHPT,chiTieuTuyenSinh FROM ThongTinTuyenSinh WHERE maDot='DOT2026' AND maThongTin IN('TT_THPT_1','TT_THPT_2','TT_THPT_3','TT_THPT_4','TT_THPT_5') ORDER BY maTruongTHPT;
SELECT 'NV_khong_du_3' AS loi,COUNT(*) AS soLoi FROM (SELECT t.maThiSinh FROM ThiSinh t LEFT JOIN NguyenVong n ON n.maThiSinh=t.maThiSinh WHERE t.maThiSinh BETWEEN 'TSD000001' AND 'TSD001750' GROUP BY t.maThiSinh HAVING COUNT(n.maNguyenVong)<>3 OR COUNT(DISTINCT n.maTruongTHPT)<>3) x
UNION ALL SELECT 'FK_THCS',COUNT(*) FROM ThiSinh t LEFT JOIN TruongTHCS c ON c.maTruongTHCS=t.maTruongTHCS WHERE t.maThiSinh BETWEEN 'TSD000001' AND 'TSD001750' AND c.maTruongTHCS IS NULL
UNION ALL SELECT 'FK_NV',COUNT(*) FROM NguyenVong n LEFT JOIN ThiSinh t ON t.maThiSinh=n.maThiSinh LEFT JOIN TruongTHPT p ON p.maTruongTHPT=n.maTruongTHPT LEFT JOIN QuyDinhNguyenVong q ON q.maQuyDinh=n.maQuyDinh WHERE n.maNguyenVong BETWEEN 'NVD0000001' AND 'NVD0005250' AND(t.maThiSinh IS NULL OR p.maTruongTHPT IS NULL OR q.maQuyDinh IS NULL)
UNION ALL SELECT 'Trung_SBD',COUNT(*) FROM(SELECT soBaoDanh FROM ThiSinh WHERE soBaoDanh IS NOT NULL GROUP BY soBaoDanh HAVING COUNT(*)>1)x
UNION ALL SELECT 'Trung_thu_tu_NV',COUNT(*) FROM(SELECT maThiSinh,thuTuNguyenVong FROM NguyenVong GROUP BY maThiSinh,thuTuNguyenVong HAVING COUNT(*)>1)x;
SELECT (SELECT COUNT(*) FROM TruongTHCS) AS tongTHCS,(SELECT COUNT(*) FROM ThiSinh) AS tongThiSinh,(SELECT COUNT(*) FROM NguyenVong) AS tongNguyenVong,(SELECT COUNT(*) FROM TaiKhoan) AS taiKhoanGiuLai,(SELECT COUNT(*) FROM BaiThi) AS baiThiGiuLai,(SELECT COUNT(*) FROM KetQuaChamThi) AS ketQuaChamGiuLai;
