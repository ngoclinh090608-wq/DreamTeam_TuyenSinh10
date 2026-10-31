-- 4.7. vw_PhanCongRaDe (theo báo cáo Tuần 08)
-- Tra cứu nhiệm vụ ra đề thi của cán bộ theo môn và kỳ thi.
CREATE OR REPLACE VIEW vw_PhanCongRaDe AS
SELECT
  pc.maPhanCong,
  c.hoTen,
  k.tenKyThi,
  m.tenMonThi,
  pc.thoiGianThucHien,
  pc.thongTinNhiemVu,
  pc.maKyThi,
  pc.maMonThi,
  pc.maCanBo
FROM PhanCongRaDe pc
JOIN CanBo c ON c.maCanBo = pc.maCanBo
JOIN KyThi k ON k.maKyThi = pc.maKyThi
JOIN MonThi m ON m.maMonThi = pc.maMonThi;

