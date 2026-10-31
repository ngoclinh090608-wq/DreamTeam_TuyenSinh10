#!/usr/bin/env bash
# =============================================================================
# Kiểm thử chức năng UC05 – UC08 (Nguyễn Thị Thúy Vy).
# Chạy HTTP test so khớp câu thông báo nguyên văn theo Test Case Tuần 04.
# =============================================================================
B="${BASE_URL:-http://127.0.0.1:8080}"
DB_HOST="${DB_HOST:-127.0.0.1}"; DB_PORT="${DB_PORT:-3306}"; DB_USER="${DB_USER:-root}"; DB_PASS="${DB_PASS:-}"; DB_NAME="${DB_NAME:-dreamteam_tuyensinh10}"

MYSQL=mysql; MYSQLDUMP=mysqldump
if ! command -v mysql >/dev/null 2>&1 && [ -x /d/xamp/mysql/bin/mysql.exe ]; then
  MYSQL=/d/xamp/mysql/bin/mysql; MYSQLDUMP=/d/xamp/mysql/bin/mysqldump
elif ! command -v mysql >/dev/null 2>&1 && [ -x /c/xampp/mysql/bin/mysql.exe ]; then
  MYSQL=/c/xampp/mysql/bin/mysql; MYSQLDUMP=/c/xampp/mysql/bin/mysqldump
fi

DB_ARGS=(-h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" --default-character-set=utf8mb4)
[ -n "$DB_PASS" ] && DB_ARGS+=("-p$DB_PASS")

S="$(mktemp -d)"
command -v cygpath >/dev/null 2>&1 && S="$(cygpath -m "$S")"
export MSYS_NO_PATHCONV=1
NUL="$S/bo_qua.out"
J="$S/cookie.txt"
V="$S/vals"; mkdir -p "$V"
OUT="$S/ketqua.tsv"; : > "$OUT"

db_file(){ "$MYSQL" "${DB_ARGS[@]}" "$DB_NAME" < "$1"; }
db_exec(){ printf 'SET NAMES utf8mb4;\n%s\n' "$1" > "$S/q.sql"; db_file "$S/q.sql"; }
sql(){ db_exec "$1"; }
gtri(){ printf 'SET NAMES utf8mb4;\n%s;\n' "$1" > "$S/q.sql"; "$MYSQL" "${DB_ARGS[@]}" -N "$DB_NAME" < "$S/q.sql"; }

# Sao lưu các bảng liên quan đến UC05-08
BANG="PhongThi PhanPhongThi BaiThi KetQuaChamThi PhanCongRaDe"
if [ "${SAO_LUU:-1}" != 0 ]; then
  "$MYSQLDUMP" "${DB_ARGS[@]}" "$DB_NAME" $BANG > "$S/saoluu.sql" 2>/dev/null || true
fi

khoi_phuc(){
  [ -f "$S/saoluu.sql" ] && db_file "$S/saoluu.sql" 2>/dev/null && echo "Đã khôi phục dữ liệu UC05-08."
  rm -rf "$S"
}
trap khoi_phuc EXIT

SO_CA=0; SO_DUNG=0; SO_SAI=0
ok(){ printf '%s\t%s\t%s\tĐẠT\n' "$1" "$2" "$3" >> "$OUT"; SO_CA=$((SO_CA+1)); SO_DUNG=$((SO_DUNG+1)); printf '  \033[32m✓\033[0m %-6s %s\n' "$1" "$2"; }
ko(){ printf '%s\t%s\t%s\tLỖI: %s\n' "$1" "$2" "$3" "$4" >> "$OUT"; SO_CA=$((SO_CA+1)); SO_SAI=$((SO_SAI+1)); printf '  \033[31m✗\033[0m %-6s %s (%s)\n' "$1" "$2" "$4"; }

kiem(){ local html; html=$(cat); if grep -qF -- "$3" <<<"$html"; then ok "$1" "$2" "$3"; else ko "$1" "$2" "$3" "$(grep -o '<strong>[^<]*' <<<"$html" | sed 's/<strong>//' | grep -v '^$' | head -2 | tr '\n' '|')"; fi; }
khong(){ local html; html=$(cat); if grep -qF -- "$3" <<<"$html"; then ko "$1" "$2" "không được có: $3" "vẫn có"; else ok "$1" "$2" "không có: $3"; fi; }
dung(){ if [ "$3" = "$4" ]; then ok "$1" "$2" "$4"; else ko "$1" "$2" "$4" "thực tế: $3"; fi; }
ma_http(){ curl -s -o "$NUL" -c "$J" -b "$J" -w '%{http_code}' "$B/$1"; }

get(){ curl -s -c "$J" -b "$J" "$B/$1"; }
tok(){ get "$1" | grep -o 'name="csrf_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//'; }
post(){ local url="$1"; shift; curl -s -c "$J" -b "$J" -L "$B/$url" --data-urlencode "csrf_token=$T" "$@"; }

MAT_KHAU_DEMO="${MAT_KHAU_DEMO:-1234}"
dang_nhap(){
  rm -f "$J"
  local t; t=$(tok "auth/login.php")
  curl -s -o "$NUL" -c "$J" -b "$J" "$B/auth/login.php" \
    --data-urlencode "csrf_token=$t" \
    --data-urlencode "tenDangNhap=$1" \
    --data-urlencode "matKhau=${2:-$MAT_KHAU_DEMO}"
}

echo "=== KIỂM THỬ UC05 – UC08 (Nguyễn Thị Thúy Vy) ==="

# =============================================================================
# UC05: NHẬP ĐIỂM THI (Actor: HD_CHAM_THI)
# =============================================================================
dang_nhap "bantuyensinh"
dung UC05 "Tài khoản không phải HD_CHAM_THI bị chặn (HTTP 403)" "$(ma_http grading/nhap-diem.php)" 403
get "grading/nhap-diem.php" | kiem UC05 "Thông báo chặn quyền truy cập" "Bạn không có quyền thực hiện chức năng này."

dang_nhap "hdchamthi"
get "grading/nhap-diem.php?kyThi=KT2026&monThi=TOAN" | kiem UC05 "Mở trang nhập điểm thành công" "Nhập điểm thi"
get "grading/nhap-diem.php?kyThi=KT2026&monThi=TOAN" | kiem UC05 "Thấy bài thi chờ chấm P0030" "P0030"

# Nhập điểm sai bước nhảy (8.3)
T=$(tok "grading/nhap-diem.php?kyThi=KT2026&monThi=TOAN&baiThi=BT046")
post "grading/nhap-diem.php" -d "action=nhap_diem&maBaiThi=BT046&kyThi=KT2026&monThi=TOAN&diemThi=8.3" | kiem UC05 "Điểm sai bước 0.25 (8.3) bị báo lỗi" "Điểm thi không hợp lệ."

# Nhập điểm quá thang điểm (11)
T=$(tok "grading/nhap-diem.php?kyThi=KT2026&monThi=TOAN&baiThi=BT046")
post "grading/nhap-diem.php" -d "action=nhap_diem&maBaiThi=BT046&kyThi=KT2026&monThi=TOAN&diemThi=11" | kiem UC05 "Điểm vượt thang điểm (>10) bị báo lỗi" "Điểm thi không hợp lệ."

# Nhập điểm thành công cho BT046 (8.75)
T=$(tok "grading/nhap-diem.php?kyThi=KT2026&monThi=TOAN&baiThi=BT046")
post "grading/nhap-diem.php" -d "action=nhap_diem&maBaiThi=BT046&kyThi=KT2026&monThi=TOAN&diemThi=8.75" | kiem UC05 "Nhập điểm thành công cho BT046" "Nhập điểm thi thành công."
dung UC05 "CSDL ghi nhận kết quả điểm 8.75" "$(gtri "SELECT diemThi FROM KetQuaChamThi WHERE maBaiThi = 'BT046'")" "8.75"
dung UC05 "Trạng thái bài thi chuyển sang Đã chấm" "$(gtri "SELECT trangThaiCham FROM BaiThi WHERE maBaiThi = 'BT046'")" "Đã chấm"

# Thử nhập đè lên bài vừa có kết quả -> báo đã xác nhận
T=$(tok "grading/nhap-diem.php?kyThi=KT2026&monThi=TOAN&baiThi=BT046")
post "grading/nhap-diem.php" -d "action=nhap_diem&maBaiThi=BT046&kyThi=KT2026&monThi=TOAN&diemThi=9.0" | kiem UC05 "Chặn nhập đè bài đã có kết quả" "Điểm bài thi đã được xác nhận chính thức."

# =============================================================================
# UC06: TẠO THÔNG TIN PHÒNG THI (Actor: BAN_TUYEN_SINH)
# =============================================================================
dang_nhap "hdchamthi"
dung UC06 "Tài khoản không phải BAN_TUYEN_SINH bị chặn tạo phòng (HTTP 403)" "$(ma_http exam/tao-phong.php)" 403
get "exam/tao-phong.php" | kiem UC06 "Thông báo chặn quyền tạo phòng" "Bạn không có quyền thực hiện chức năng này."

dang_nhap "bantuyensinh"
get "exam/tao-phong.php?kyThi=KT2026&diemThi=DT01" | kiem UC06 "Mở trang tạo phòng thi thành công" "Tạo thông tin phòng thi"

# Tạo phòng thiếu mã phòng
T=$(tok "exam/tao-phong.php?kyThi=KT2026&diemThi=DT01")
post "exam/tao-phong.php" -d "action=tao_phong&kyThi=KT2026&maDiemThi=DT01&maPhong=&tenPhong=Phong+Moi&sucChua=12" | kiem UC06 "Để trống mã phòng bị báo lỗi" "Thông tin phòng thi không hợp lệ."

# Tạo phòng trùng mã phòng P101
T=$(tok "exam/tao-phong.php?kyThi=KT2026&diemThi=DT01")
post "exam/tao-phong.php" -d "action=tao_phong&kyThi=KT2026&maDiemThi=DT01&maPhong=P101&tenPhong=Phong+Khac&sucChua=12" | kiem UC06 "Trùng mã phòng P101 bị báo lỗi" "Thông tin phòng thi bị trùng."

# Tạo phòng mới P103 thành công
T=$(tok "exam/tao-phong.php?kyThi=KT2026&diemThi=DT01")
post "exam/tao-phong.php" -d "action=tao_phong&kyThi=KT2026&maDiemThi=DT01&maPhong=P103&tenPhong=Phòng+103&sucChua=12" | kiem UC06 "Tạo phòng thi P103 thành công" "Tạo thông tin phòng thi thành công."
dung UC06 "CSDL có phòng P103 sức chứa 12" "$(gtri "SELECT COUNT(*) FROM PhongThi WHERE maPhong = 'P103'")" 1

# Xóa phòng thi vừa tạo P103 (chưa có thí sinh)
T=$(tok "exam/tao-phong.php?kyThi=KT2026&diemThi=DT01")
post "exam/tao-phong.php" -d "action=xoa_phong&kyThi=KT2026&diemThi=DT01&maPhongXoa=P103" | kiem UC06 "Xóa phòng thi P103 thành công" "Xóa phòng thi thành công."
dung UC06 "CSDL đã xóa phòng P103" "$(gtri "SELECT COUNT(*) FROM PhongThi WHERE maPhong = 'P103'")" 0

# Chặn xóa phòng P101 đang có thí sinh
T=$(tok "exam/tao-phong.php?kyThi=KT2026&diemThi=DT01")
post "exam/tao-phong.php" -d "action=xoa_phong&kyThi=KT2026&diemThi=DT01&maPhongXoa=P101" | kiem UC06 "Chặn xóa phòng P101 đã có thí sinh" "Không thể xóa phòng thi đã có thí sinh được phân phòng."

# =============================================================================
# UC07: PHÂN PHÒNG THI CHO THÍ SINH (Actor: BAN_TUYEN_SINH)
# =============================================================================
dang_nhap "hdcoithi"
dung UC07 "Tài khoản không phải BAN_TUYEN_SINH bị chặn phân phòng (HTTP 403)" "$(ma_http exam/phan-phong.php)" 403

dang_nhap "bantuyensinh"
get "exam/phan-phong.php?kyThi=KT2026&diemThi=DT01" | kiem UC07 "Mở trang phân phòng thi thành công" "Phân phòng thi cho thí sinh"

# Xem trước phân phòng tự động
T=$(tok "exam/phan-phong.php?kyThi=KT2026&diemThi=DT01")
post "exam/phan-phong.php" -d "action=xem_truoc&kyThi=KT2026&diemThi=DT01" | kiem UC07 "Xem trước phân phòng tự động" "Kết quả phân phòng dự kiến (Xem trước)"

# Chuyển phòng thí sinh TS001 từ P101 sang P102
T=$(tok "exam/phan-phong.php?kyThi=KT2026&diemThi=DT01")
post "exam/phan-phong.php" -d "action=chuyen_phong&kyThi=KT2026&diemThi=DT01&maPhanPhong=PP001&maPhongMoi=P102" | kiem UC07 "Chuyển phòng thí sinh sang P102 thành công" "Chuyển phòng thi cho thí sinh thành công."
dung UC07 "CSDL ghi nhận TS001 ở phòng P102" "$(gtri "SELECT maPhong FROM PhanPhongThi WHERE maPhanPhong = 'PP001'")" "P102"

# Chuyển lại về P101
T=$(tok "exam/phan-phong.php?kyThi=KT2026&diemThi=DT01")
post "exam/phan-phong.php" -d "action=chuyen_phong&kyThi=KT2026&diemThi=DT01&maPhanPhong=PP001&maPhongMoi=P101" | kiem UC07 "Chuyển lại TS001 về phòng P101" "Chuyển phòng thi cho thí sinh thành công."

# =============================================================================
# UC08: XEM PHÂN CÔNG RA ĐỀ THI (Actor: HD_RA_DE)
# =============================================================================
dang_nhap "catphach"
dung UC08 "Tài khoản không phải HD_RA_DE bị chặn xem phân công (HTTP 403)" "$(ma_http exam/phan-cong-ra-de.php)" 403

dang_nhap "hdrade"
get "exam/phan-cong-ra-de.php?kyThi=KT2026" | kiem UC08 "Hội đồng ra đề truy cập thành công" "Phân công ra đề thi"
get "exam/phan-cong-ra-de.php?kyThi=KT2026" | kiem UC08 "Hiển thị phân công môn Toán (Lê Minh Tuấn)" "Lê Minh Tuấn"
get "exam/phan-cong-ra-de.php?kyThi=KT2026" | kiem UC08 "Hiển thị phân công môn Ngữ văn (Trần Thu Hà)" "Trần Thu Hà"
get "exam/phan-cong-ra-de.php?kyThi=KT2026&monThi=TOAN" | kiem UC08 "Lọc theo môn Toán" "PCRD1"

echo "=========================================="
echo "Tổng số test UC05–UC08: $SO_CA | Đúng: $SO_DUNG | Sai: $SO_SAI"
if [ "$SO_SAI" -eq 0 ]; then
  echo -e "\033[32mTẤT CẢ TEST ĐỀU ĐẠT (PASS)!\033[0m"
  exit 0
else
  echo -e "\033[31mCÓ $SO_SAI TEST THẤT BẠI!\033[0m"
  exit 1
fi
