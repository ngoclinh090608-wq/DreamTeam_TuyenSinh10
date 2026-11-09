-- Additive migration only. Do not infer publication time from the time a score was entered.
-- Select the intended application database explicitly before running this file.
ALTER TABLE KetQuaChamThi ADD COLUMN IF NOT EXISTS thoiDiemCongBo DATETIME NULL AFTER trangThaiCongBo;
