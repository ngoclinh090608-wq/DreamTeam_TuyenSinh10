#!/usr/bin/env bash
# =============================================================================
# Kiểm thử đăng nhập, phân quyền và chức năng UC09 – UC15 (gọi trang thật qua HTTP, so câu thông báo theo báo cáo giữa kỳ).
#
# Chạy trên máy (Git Bash, XAMPP đang bật Apache + MySQL, đã nhập database/install.sql):
#     bash tests/kiem_thu_uc09_15.sh
# CI (GitHub Actions) chạy cùng script này với PHP built-in server + MariaDB.
#
# Biến môi trường (đều có mặc định cho XAMPP):
#     BASE_URL  (http://localhost/DreamTeam_TuyenSinh10)
#     DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME   — giống config/database.php
#     SAO_LUU=0   bỏ sao lưu/khôi phục (CI dùng CSDL mới mỗi lần chạy)
#
# Script sao lưu các bảng bị sửa lúc bắt đầu và tự khôi phục khi kết thúc (kể cả khi bị lỗi).
# Cần dữ liệu mẫu gốc (install.sql) để các ca kiểm thử đúng.
# Thoát mã 1 nếu có ca LỖI.
# =============================================================================
B="${BASE_URL:-http://localhost/DreamTeam_TuyenSinh10}"
DB_HOST="${DB_HOST:-127.0.0.1}"; DB_PORT="${DB_PORT:-3306}"; DB_USER="${DB_USER:-root}"; DB_PASS="${DB_PASS:-}"; DB_NAME="${DB_NAME:-dreamteam_tuyensinh10}"

# Tìm mysql / mysqldump: trong PATH, nếu không có thì thư mục XAMPP.
MYSQL=mysql; MYSQLDUMP=mysqldump
if ! command -v mysql >/dev/null 2>&1 && [ -x /c/xampp/mysql/bin/mysql.exe ]; then
  MYSQL=/c/xampp/mysql/bin/mysql; MYSQLDUMP=/c/xampp/mysql/bin/mysqldump
fi
DB_ARGS=(-h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" --default-character-set=utf8mb4)
[ -n "$DB_PASS" ] && DB_ARGS+=("-p$DB_PASS")

S="$(mktemp -d)"
# Git Bash trên Windows: curl.exe cần đường dẫn dạng C:/... thay vì /tmp/...
command -v cygpath >/dev/null 2>&1 && S="$(cygpath -m "$S")"
export MSYS_NO_PATHCONV=1   # Git Bash: không tự đổi tham số "/..." thành đường dẫn Windows
NUL="$S/bo_qua.out"   # thay cho /dev/null: curl.exe trên Windows không ghi được /dev/null
J="$S/cookie.txt"
V="$S/vals"; mkdir -p "$V"
OUT="$S/ketqua.tsv"; : > "$OUT"

# Mọi câu SQL đi qua file (tránh lỗi dấu tiếng Việt khi truyền qua dòng lệnh Windows).
db_file(){ "$MYSQL" "${DB_ARGS[@]}" "$DB_NAME" < "$1"; }
db_exec(){ printf 'SET NAMES utf8mb4;\n%s\n' "$1" > "$S/q.sql"; db_file "$S/q.sql"; }
sql(){ db_exec "$1"; }
gtri(){ printf 'SET NAMES utf8mb4;\n%s;\n' "$1" > "$S/q.sql"; "$MYSQL" "${DB_ARGS[@]}" -N "$DB_NAME" < "$S/q.sql"; }
trg(){ printf "SET NAMES utf8mb4;\nDROP TRIGGER IF EXISTS trg_kt;\nDELIMITER \$\$\nCREATE TRIGGER trg_kt BEFORE %s ON %s FOR EACH ROW BEGIN %s SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='loi gia lap'; %s END\$\$\nDELIMITER ;\n" "$1" "$2" "${3:-}" "${4:-}" > "$S/trg.sql"; db_file "$S/trg.sql"; }
boTrg(){ db_exec "DROP TRIGGER IF EXISTS trg_kt;"; }

# ---- Sao lưu và tự khôi phục ----
BANG="KetQuaChamThi LichSuChinhSuaDiem HoiDongThi PhanCongCoiThi CanBo MonThi TaiKhoan"
if ! "$MYSQL" "${DB_ARGS[@]}" -e "SELECT 1 FROM $DB_NAME.ThiSinh LIMIT 1" >/dev/null 2>&1; then
  echo "Không kết nối được CSDL $DB_NAME ($DB_HOST:$DB_PORT). Hãy bật MySQL và nhập database/install.sql." >&2; exit 2
fi
if [ "${SAO_LUU:-1}" != 0 ]; then
  "$MYSQLDUMP" "${DB_ARGS[@]}" "$DB_NAME" $BANG > "$S/saoluu.sql" || { echo "Không sao lưu được dữ liệu." >&2; exit 2; }
fi
khoi_phuc(){
  db_exec "DROP TRIGGER IF EXISTS trg_kt;" 2>/dev/null
  for t in LichSuChinhSuaDiem KetQuaChamThi; do db_exec "RENAME TABLE ${t}_kt TO ${t};" 2>/dev/null; done
  [ -f "$S/saoluu.sql" ] && db_file "$S/saoluu.sql" && echo "Đã khôi phục dữ liệu như trước khi kiểm thử."
  rm -rf "$S"
}
trap khoi_phuc EXIT

if ! curl -s -o "$NUL" "$B/"; then echo "Không mở được $B — hãy bật Apache (hoặc PHP server)." >&2; exit 2; fi

# ---- Hàm kiểm tra ----
v(){ printf '%s' "$2" > "$V/$1"; }   # lưu giá trị tiếng Việt ra file để gửi qua curl
v gt1 'Giám thị 1'; v gt2 'Giám thị 2'; v coithi 'Coi thi'; v tenhd2 'Hội đồng coi thi'; v trung 'Hội đồng chấm thi'
v tt 'Tổ chức coi thi tại 2 điểm thi'; v tenmoi 'Hội đồng thi thử nghiệm'; v lydo 'Cộng sót điểm câu 3'

ok(){ printf '%s\t%s\t%s\tĐẠT\n' "$1" "$2" "$3" >> "$OUT"; }
ko(){ printf '%s\t%s\t%s\tLỖI: %s\n' "$1" "$2" "$3" "$4" >> "$OUT"; }
# kiem MÃ_UC "mô tả" "chuỗi phải có trong trang"   (nội dung trang đọc từ stdin)
kiem(){ local html; html=$(cat); if grep -qF -- "$3" <<<"$html"; then ok "$1" "$2" "$3"; else ko "$1" "$2" "$3" "$(grep -o '<strong>[^<]*' <<<"$html" | sed 's/<strong>//' | grep -v '^$' | head -2 | tr '\n' '|')"; fi; }
khong(){ local html; html=$(cat); if grep -qF -- "$3" <<<"$html"; then ko "$1" "$2" "không được có: $3" "vẫn có"; else ok "$1" "$2" "không có: $3"; fi; }
dung(){ if [ "$3" = "$4" ]; then ok "$1" "$2" "$4"; else ko "$1" "$2" "$4" "thực tế: $3"; fi; }   # so giá trị từ CSDL
get(){ curl -s -c "$J" -b "$J" "$B/$1"; }
tok(){ get "$1" | grep -o 'name="csrf_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//'; }
post(){ local url="$1"; shift; curl -s -c "$J" -b "$J" -L "$B/$url" --data-urlencode "csrf_token=$T" "$@"; }
# Đăng nhập bằng tài khoản demo (mật khẩu chung dreamteam2026), phiên mới mỗi lần.
MAT_KHAU_DEMO="${MAT_KHAU_DEMO:-dreamteam2026}"
dang_nhap(){ rm -f "$J"; TK_HIEN_TAI="$1"; local t; t=$(tok "auth/login.php"); curl -s -o "$NUL" -c "$J" -b "$J" "$B/auth/login.php" --data-urlencode "csrf_token=$t" --data-urlencode "tenDangNhap=$1" --data-urlencode "matKhau=${2:-$MAT_KHAU_DEMO}"; }

# ================= Đăng nhập, đăng xuất, đổi mật khẩu, phân quyền =================
ma_http(){ curl -s -o "$NUL" -c "$J" -b "$J" -w '%{http_code}' "$B/$1"; }
vi_tri(){ curl -s -o "$NUL" -c "$J" -b "$J" -w '%{redirect_url}' "$B/$1"; }
rm -f "$J"
dung DN "Chưa đăng nhập → chuyển tới trang đăng nhập" "$(vi_tri grading/lich-su.php | grep -c 'auth/login.php?next=')" 1
dung DN "Chưa đăng nhập → không vào được UC10" "$(vi_tri councils/ | grep -c 'auth/login.php')" 1
get "auth/login.php" | kiem DN "Trang đăng nhập mở được" "Đăng nhập hệ thống"
T=$(tok "auth/login.php")
post "auth/login.php" -d "tenDangNhap=&matKhau=" | kiem DN "Bỏ trống" "Vui lòng nhập tên đăng nhập và mật khẩu."
post "auth/login.php" -d "tenDangNhap=hdchamthi&matKhau=sai" | kiem DN "Sai mật khẩu" "Tên đăng nhập hoặc mật khẩu không đúng."
post "auth/login.php" -d "tenDangNhap=khongcoai&matKhau=dreamteam2026" | kiem DN "Tài khoản không tồn tại" "Tên đăng nhập hoặc mật khẩu không đúng."
curl -s -c "$J" -b "$J" "$B/auth/login.php" -d "csrf_token=sai&tenDangNhap=hdchamthi&matKhau=dreamteam2026" | kiem DN "CSRF sai bị chặn" "Phiên làm việc đã hết hạn"
for i in 1 2 3 4; do post "auth/login.php" -d "tenDangNhap=gvcn&matKhau=sai$i" > /dev/null; done
post "auth/login.php" -d "tenDangNhap=gvcn&matKhau=sai5" | kiem DN "Sai 5 lần → khóa tạm" "Vui lòng thử lại sau 15 phút."
post "auth/login.php" -d "tenDangNhap=gvcn&matKhau=dreamteam2026" | kiem DN "Đang khóa tạm: đúng mật khẩu vẫn chặn" "Vui lòng thử lại sau 15 phút."
sql "UPDATE TaiKhoan SET khoaDen = NULL, soLanSai = 0 WHERE tenDangNhap = 'gvcn'; UPDATE TaiKhoan SET trangThai = 'Bị khóa' WHERE tenDangNhap = 'hdrade';"
post "auth/login.php" -d "tenDangNhap=hdrade&matKhau=dreamteam2026" | kiem DN "Tài khoản bị khóa" "Tài khoản đã bị khóa."
sql "UPDATE TaiKhoan SET trangThai = 'Hoạt động' WHERE tenDangNhap = 'hdrade';"
dung DN "Đăng nhập đúng → quay lại trang đang mở (next)" "$(curl -s -o "$NUL" -c "$J" -b "$J" -w '%{redirect_url}' "$B/auth/login.php" --data-urlencode "csrf_token=$T" -d "tenDangNhap=hdchamthi&matKhau=dreamteam2026" --data-urlencode "next=$(printf '%s' "$B" | sed -E 's#https?://[^/]+##')/grading/thong-ke.php" | grep -c 'grading/thong-ke.php')" 1
get "admin/" | kiem DN "Thanh trên hiện tên người đăng nhập" "Phan Thùy Dương"
get "admin/" | khong DN "Sidebar ẩn mục không có quyền (papers)" "/papers/\""
dung DN "Không có quyền → HTTP 403" "$(ma_http papers/)" 403
get "papers/" | kiem DN "Không có quyền → câu thông báo" "Bạn không có quyền thực hiện chức năng này."
T=$(tok "grading/thong-ke.php")
post "councils/?ma=HD02&sua=1" -d "action=cap_nhat&maKyThi=KT2026&tenHoiDongThi=Bi+sua" --data-urlencode "loaiHoiDong@$V/coithi" > /dev/null
dung DN "POST khi không có quyền không sửa được dữ liệu" "$(gtri "SELECT COUNT(*) FROM HoiDongThi WHERE tenHoiDongThi = 'Bi sua'")" 0
dung DN "Chặn chuyển hướng ra trang ngoài" "$(rm -f "$J"; T=$(tok auth/login.php); curl -s -o "$NUL" -c "$J" -b "$J" -w '%{redirect_url}' "$B/auth/login.php" --data-urlencode "csrf_token=$T" -d "tenDangNhap=quantri&matKhau=dreamteam2026" --data-urlencode "next=https://vi-du-ngoai.com" | grep -c 'vi-du-ngoai')" 0
# Đổi mật khẩu (tài khoản gvcn, khôi phục lại sau)
dang_nhap gvcn
T=$(tok "auth/doi-mat-khau.php")
post "auth/doi-mat-khau.php" -d "matKhauCu=sai&matKhauMoi=matkhaumoi1&nhapLai=matkhaumoi1" | kiem DMK "Sai mật khẩu hiện tại" "Mật khẩu hiện tại không đúng."
post "auth/doi-mat-khau.php" -d "matKhauCu=dreamteam2026&matKhauMoi=ngan&nhapLai=ngan" | kiem DMK "Mật khẩu mới quá ngắn" "ít nhất 8 ký tự"
post "auth/doi-mat-khau.php" -d "matKhauCu=dreamteam2026&matKhauMoi=matkhaumoi1&nhapLai=khac12345" | kiem DMK "Nhập lại không khớp" "Nhập lại mật khẩu mới không khớp."
post "auth/doi-mat-khau.php" -d "matKhauCu=dreamteam2026&matKhauMoi=matkhaumoi1&nhapLai=matkhaumoi1" | kiem DMK "Đổi thành công" "Đổi mật khẩu thành công."
rm -f "$J"; T=$(tok auth/login.php)
post "auth/login.php" -d "tenDangNhap=gvcn&matKhau=dreamteam2026" | kiem DMK "Mật khẩu cũ không dùng được nữa" "Tên đăng nhập hoặc mật khẩu không đúng."
dang_nhap gvcn matkhaumoi1
get "admin/" | kiem DMK "Đăng nhập bằng mật khẩu mới" "Giáo viên chủ nhiệm lớp 9"
sql "UPDATE TaiKhoan t JOIN TaiKhoan q ON q.tenDangNhap = 'quantri' SET t.matKhau = q.matKhau WHERE t.tenDangNhap = 'gvcn';"
# Đăng xuất
dang_nhap hdchamthi
T=$(tok "admin/")
post "auth/logout.php" | kiem DX "Đăng xuất → về trang đăng nhập" "Bạn đã đăng xuất."
dung DX "Sau khi đăng xuất không vào được trang" "$(vi_tri grading/thong-ke.php | grep -c 'auth/login.php')" 1
# Ma trận quyền UC09–UC15 (HTTP 200 = được vào, 403 = bị chặn)
quyen(){ dang_nhap "$1"; shift; while [ $# -gt 1 ]; do dung QUYEN "$TK_HIEN_TAI vào /$1" "$(ma_http "$1")" "$2"; shift 2; done; }
quyen catphach       papers/ 200 councils/ 403 assignments/ 403 grading/ 403 publication/diem-thi.php 403
quyen bantuyensinh   papers/ 403 councils/ 200 assignments/ 403 grading/ 403 publication/diem-thi.php 200 publication/tuyen-sinh.php 403
quyen hdcoithi       papers/ 403 councils/ 403 assignments/ 200 grading/ 403 publication/diem-thi.php 403
quyen hdchamthi      papers/ 403 councils/ 403 assignments/ 403 grading/lich-su.php 200 grading/cap-nhat.php 200 grading/thong-ke.php 200 publication/diem-thi.php 403
quyen hdtuyensinh    publication/ 200 publication/diem-thi.php 403 publication/tuyen-sinh.php 200 grading/ 403
quyen quantri        admin/ 200 papers/ 403 councils/ 403 grading/ 403 publication/ 403
quyen SBD0001        admin/ 200 aspirations/ 200 grading/ 403 councils/ 403

# Dashboard học sinh không dùng thống kê hoặc lối tắt quản trị.
dang_nhap SBD0001
HTML=$(get "admin/")
kiem HS "Dashboard học sinh có nội dung riêng" "Trang tuyển sinh của bạn" <<<"$HTML"
kiem HS "Hiển thị đúng học sinh đang đăng nhập" "Xin chào, Trần Thị Hà" <<<"$HTML"
for label in "Hồ sơ thí sinh" "Trường THPT tham gia" "Nguyện vọng đã lập" "Nguyện vọng 1 so với chỉ tiêu" "Thêm mới"; do
  khong HS "Học sinh không thấy $label" "$label" <<<"$HTML"
done
for module in exam assignments admission councils grading; do
  khong HS "Dashboard học sinh không có liên kết $module" "/$module/\"" <<<"$HTML"
done
for module in admission-info aspirations appeals enrollment; do
  kiem HS "Dashboard học sinh có liên kết $module" "/$module/\"" <<<"$HTML"
done
quyen SBD0001 exam/ 403 assignments/ 403 admission/ 403

dang_nhap SBD0002
HTML=$(get "admin/")
kiem HS "Dashboard hiển thị học sinh thứ hai" "Xin chào, Trần Minh Chi" <<<"$HTML"
khong HS "Dashboard không hiển thị tên học sinh khác" "Trần Thị Hà" <<<"$HTML"

dang_nhap hdchamthi
HTML=$(get "admin/")
kiem HS "Cán bộ chấm thi có lối tắt chấm thi" "/grading/\"" <<<"$HTML"
for module in aspirations exam assignments admission; do
  khong HS "Cán bộ chấm thi không có lối tắt $module" "/$module/\"" <<<"$HTML"
done

dang_nhap hdrade
get "admin/" | khong HS "Hội đồng ra đề không có lối tắt phân công coi thi" "Phân công coi thi"

# ================= UC09 Tra cứu thông tin lô bài thi =================
dang_nhap catphach
get "papers/?traCuu=1" | kiem UC09 "Không nhập điều kiện" "Điều kiện tra cứu không hợp lệ."
get "papers/?traCuu=1&maLo=LO%40%231" | kiem UC09 "Mã lô sai định dạng" "Điều kiện tra cứu không hợp lệ."
get "papers/?traCuu=1&diemThi=DT01&phong=P201" | kiem UC09 "Phòng không thuộc điểm thi" "Phòng thi đã chọn không thuộc điểm thi đã chọn."
get "papers/?traCuu=1&maLo=XYZ" | kiem UC09 "Không tìm thấy" "Không tìm thấy lô bài thi phù hợp."
get "papers/?traCuu=1&monThi=TOAN" | kiem UC09 "Lọc theo môn Toán" ">4 lô<"
get "papers/?traCuu=1&maLo=p101" | kiem UC09 "Tìm theo một phần mã lô" ">3 lô<"
get "papers/?traCuu=1&monThi=TOAN&lo=LO_P101_TOAN" | kiem UC09 "Xem chi tiết lô" "Số bài thi trong lô"
get "papers/?lo=KHONG_CO" | kiem UC09 "Lô không tồn tại" "Không thể truy xuất thông tin của lô bài thi được chọn."

# ================= UC10 Quản lý Hội đồng thi =================
dang_nhap bantuyensinh
get "councils/" | kiem UC10 "Danh sách hội đồng" "?ma=HD05"
get "councils/?ma=HD02" | kiem UC10 "Xem chi tiết HD02" "6 cán bộ"
get "councils/?ma=HD99" | kiem UC10 "Mã không tồn tại" "Không tìm thấy Hội đồng thi được chọn."
T=$(tok "councils/?ma=HD02&sua=1")
post "councils/?ma=HD02&sua=1" -d "action=cap_nhat&maKyThi=KT2026&tenHoiDongThi=" --data-urlencode "loaiHoiDong@$V/coithi" | kiem UC10 "Sửa: tên trống" "Thông tin cập nhật không hợp lệ."
post "councils/?ma=HD02&sua=1" -d "action=cap_nhat&maKyThi=KT2026" --data-urlencode "tenHoiDongThi@$V/trung" --data-urlencode "loaiHoiDong@$V/coithi" | kiem UC10 "Sửa: trùng tên HD03" "đã được dùng cho hội đồng HD03"
post "councils/?ma=HD02&sua=1" -d "action=cap_nhat&maKyThi=KT1999&loaiHoiDong=abc" --data-urlencode "tenHoiDongThi@$V/tenhd2" | kiem UC10 "Sửa: loại + kỳ thi sai" "Kỳ thi đã chọn không tồn tại."
curl -s -c "$J" -b "$J" -L "$B/councils/?ma=HD02&sua=1" -d "csrf_token=sai&action=cap_nhat&maKyThi=KT2026" --data-urlencode "tenHoiDongThi@$V/tenhd2" --data-urlencode "loaiHoiDong@$V/coithi" | kiem UC10 "CSRF sai bị chặn" "Phiên làm việc đã hết hạn"
post "councils/?ma=HD02&sua=1" -d "action=cap_nhat&maKyThi=KT2026" --data-urlencode "tenHoiDongThi@$V/tenhd2" --data-urlencode "loaiHoiDong@$V/coithi" --data-urlencode "thongTinHoiDong@$V/tt" | kiem UC10 "Sửa hợp lệ" "Cập nhật thành công."
[ "$(gtri "SELECT thongTinHoiDong FROM HoiDongThi WHERE maHoiDongThi='HD02'" | wc -c)" -gt 20 ] && ok UC10 "DB đã lưu thông tin mới" "thongTinHoiDong đổi" || ko UC10 "DB đã lưu thông tin mới" "thongTinHoiDong đổi" "không đổi"
get "councils/?ma=HD02&huy=1" | kiem UC10 "Hủy bỏ" "Thông tin Hội đồng thi được giữ nguyên."
post "councils/?them=1" -d "action=them&maKyThi=KT2026" --data-urlencode "tenHoiDongThi@$V/tenmoi" --data-urlencode "loaiHoiDong@$V/coithi" | kiem UC10 "Thêm hội đồng" "Thêm Hội đồng thi HD06 thành công."
post "councils/?ma=HD02&xoa=1" -d "action=xoa" | kiem UC10 "Xóa HD02 (còn cán bộ) bị chặn" "Không thể xóa Hội đồng thi vì đang có 6 cán bộ"
post "councils/?ma=HD06&xoa=1" -d "action=xoa" | kiem UC10 "Xóa HD06" "Xóa Hội đồng thi HD06 thành công."
trg UPDATE HoiDongThi
post "councils/?ma=HD05&sua=1" -d "action=cap_nhat&maKyThi=KT2026&tenHoiDongThi=HD+test" --data-urlencode "loaiHoiDong@$V/coithi" | kiem UC10 "Lỗi CSDL khi lưu (10.1)" "Không thể lưu thông tin Hội đồng thi."
boTrg

# ================= UC11 Phân công coi thi =================
dang_nhap hdcoithi
SEL="diemThi=DT01&phong=P101&monThi=TOAN"
get "assignments/" | kiem UC11 "Bước 1: danh sách điểm thi" "Điểm thi Trường THCS Mẫu A"
get "assignments/?diemThi=DT01" | kiem UC11 "Bước 3: phòng theo điểm thi" "Phòng 102 (P102)"
get "assignments/?diemThi=DT01" | khong UC11 "Không hiện phòng điểm thi khác" "Phòng 201 (P201)"
get "assignments/?diemThi=DT01&phong=P201&monThi=TOAN" | kiem UC11 "Phòng không thuộc điểm thi" "Phòng thi đã chọn không thuộc điểm thi đã chọn."
get "assignments/?$SEL" | kiem UC11 "Bước 8: danh sách cán bộ" "Đang coi phòng P102"
T=$(tok "assignments/?$SEL")
post "assignments/?$SEL" -d "$SEL&action=kiem_tra" | kiem UC11 "Không chọn cán bộ" "Vui lòng chọn cán bộ/giám thị cần phân công."
post "assignments/?$SEL" -d "$SEL&action=kiem_tra&chon[]=CB03" --data-urlencode "vaiTro[CB03]@$V/gt2" | kiem UC11 "Trùng phân công cùng phòng" "(trùng phân công)"
post "assignments/?$SEL" -d "$SEL&action=kiem_tra&chon[]=CB05" --data-urlencode "vaiTro[CB05]@$V/gt1" | kiem UC11 "Trùng lịch phòng khác" "(trùng lịch coi thi)"
post "assignments/?$SEL" -d "$SEL&action=kiem_tra&chon[]=CB05" --data-urlencode "vaiTro[CB05]@$V/gt1" | kiem UC11 "Trùng vai trò Giám thị 1" "chỉ được phân công 1 người"
post "assignments/?$SEL" -d "$SEL&action=kiem_tra&chon[]=CB01" --data-urlencode "vaiTro[CB01]@$V/gt1" | kiem UC11 "Cán bộ không thuộc HĐ coi thi" "không thuộc Hội đồng coi thi"
post "assignments/?$SEL" -d "$SEL&action=huy&maPhanCong=PCT002" | kiem UC11 "Hủy phân công → cảnh báo thiếu" "chưa đủ 2 giám thị cho môn này"
sql "INSERT INTO CanBo (maCanBo, hoTen, chucVu, maHoiDongThi) VALUES ('CB13','Cán bộ thử 13','Giáo viên','HD02'),('CB14','Cán bộ thử 14','Giáo viên','HD02');"
post "assignments/?$SEL" -d "$SEL&action=kiem_tra&chon[]=CB13&vaiTro[CB13]=" | kiem UC11 "Chưa chọn vai trò" "Chưa chọn vai trò hợp lệ"
post "assignments/?$SEL" -d "$SEL&action=kiem_tra&chon[]=CB13&vaiTro[CB13]=" | kiem UC11 "Chưa đủ 2 giám thị" "Thông tin phân công không hợp lệ hoặc chưa đủ 2 giám thị."
post "assignments/?$SEL" -d "$SEL&action=kiem_tra&chon[]=CB13" --data-urlencode "vaiTro[CB13]@$V/gt2" | kiem UC11 "Bước 10: hộp xác nhận" "Xác nhận phân công"
get "assignments/?$SEL&huyBo=1" | kiem UC11 "Hủy bỏ" "Thông tin phân công trước đó không thay đổi."
curl -s -c "$J" -b "$J" -L "$B/assignments/?$SEL" -d "csrf_token=sai&$SEL&action=luu&chon[]=CB13" --data-urlencode "vaiTro[CB13]@$V/gt2" | kiem UC11 "CSRF sai bị chặn" "Phiên làm việc đã hết hạn"
post "assignments/?$SEL" -d "$SEL&action=luu&chon[]=CB13" --data-urlencode "vaiTro[CB13]@$V/gt2" | kiem UC11 "Lưu thành công" "Phân công coi thi thành công"
[ "$(gtri "SELECT COUNT(*) FROM PhanCongCoiThi WHERE maPhong='P101' AND maMonThi='TOAN'")" = 2 ] && ok UC11 "DB có đủ 2 giám thị" "2 dòng" || ko UC11 "DB có đủ 2 giám thị" "2 dòng" "sai"
sql "DELETE FROM PhanCongCoiThi WHERE maPhong='P101' AND maMonThi='TOAN';"
trg INSERT PhanCongCoiThi "IF NEW.maCanBo='CB14' THEN" "END IF;"
post "assignments/?$SEL" -d "$SEL&action=luu&chon[]=CB13&chon[]=CB14" --data-urlencode "vaiTro[CB13]@$V/gt1" --data-urlencode "vaiTro[CB14]@$V/gt2" | kiem UC11 "Lỗi CSDL khi lưu (12.1)" "Không thể lưu thông tin phân công coi thi."
boTrg
[ "$(gtri "SELECT COUNT(*) FROM PhanCongCoiThi WHERE maPhong='P101' AND maMonThi='TOAN'")" = 0 ] && ok UC11 "Transaction hoàn tác cả dòng 1" "0 dòng" || ko UC11 "Transaction hoàn tác cả dòng 1" "0 dòng" "còn dữ liệu"

# ================= UC12 Xem lịch sử chỉnh sửa điểm =================
dang_nhap hdchamthi
G="grading/lich-su.php?traCuu=1&kyThi=KT2026"
get "$G&monThi=TOAN&soPhach=" | kiem UC12 "Thiếu điều kiện (4.1)" "Vui lòng nhập đầy đủ điều kiện tra cứu."
get "$G&monThi=&soPhach=P0052" | kiem UC12 "Thiếu môn thi (4.1)" "Vui lòng nhập đầy đủ điều kiện tra cứu."
get "$G&monThi=TOAN&soPhach=P0023" | kiem UC12 "Không tìm thấy (5.1)" "Không tìm thấy kết quả chấm thi phù hợp."
get "$G&monThi=TOAN&soPhach=p0052" | kiem UC12 "Bước 7: hiện kết quả (mã phách chữ thường)" "Xem lịch sử chỉnh sửa</a>"
get "$G&monThi=TOAN&soPhach=P0052&xemLichSu=1" | kiem UC12 "Bước 10: có lịch sử" "Người chỉnh sửa"
get "$G&monThi=VAN&soPhach=P0023&xemLichSu=1" | kiem UC12 "Chưa có lịch sử (9.1)" "Kết quả chấm thi này chưa có lịch sử chỉnh sửa điểm."
db_exec "RENAME TABLE LichSuChinhSuaDiem TO LichSuChinhSuaDiem_kt;"
get "$G&monThi=TOAN&soPhach=P0052&xemLichSu=1" | kiem UC12 "Lỗi CSDL (9.2)" "Không thể truy xuất lịch sử chỉnh sửa điểm. Vui lòng thử lại sau."
db_exec "RENAME TABLE LichSuChinhSuaDiem_kt TO LichSuChinhSuaDiem;"

# ================= UC13 Cập nhật kết quả chấm thi =================
C="grading/cap-nhat.php"
get "$C?traCuu=1&soPhach=" | kiem UC13 "Mã phách trống (4.1)" "Mã phách không hợp lệ. Vui lòng kiểm tra lại."
get "$C?traCuu=1&soPhach=P%400" | kiem UC13 "Mã phách sai định dạng (4.1)" "Mã phách không hợp lệ. Vui lòng kiểm tra lại."
get "$C?traCuu=1&soPhach=P9999" | kiem UC13 "Không tìm thấy bài (5.1)" "Không tìm thấy bài thi tương ứng với mã phách."
sql "DELETE FROM LichSuChinhSuaDiem WHERE maKetQuaChamThi='KQC002'; DELETE FROM KetQuaChamThi WHERE maBaiThi='BT002';"
get "$C?traCuu=1&soPhach=P0023" | kiem UC13 "Bài chưa có kết quả (6.1)" "Bài thi chưa có kết quả chấm thi để cập nhật."
sql "INSERT INTO KetQuaChamThi VALUES ('KQC002', 9.0, 'CB09', '2026-06-15 10:00:00', 'Đã công bố', 'BT002');"
T=$(tok "$C?soPhach=P0023")
post "$C" -d "soPhach=P0023&action=kiem_tra&diemMoi=11" --data-urlencode "lyDo@$V/lydo" | kiem UC13 "Điểm > 10 (9.1)" "Điểm cập nhật không hợp lệ."
post "$C" -d "soPhach=P0023&action=kiem_tra&diemMoi=-1" --data-urlencode "lyDo@$V/lydo" | kiem UC13 "Điểm âm (9.1)" "Điểm cập nhật không hợp lệ."
post "$C" -d "soPhach=P0023&action=kiem_tra&diemMoi=8.3" --data-urlencode "lyDo@$V/lydo" | kiem UC13 "Sai bước nhảy 0.25 (9.1)" "Điểm cập nhật không hợp lệ."
post "$C" -d "soPhach=P0023&action=kiem_tra&diemMoi=9" --data-urlencode "lyDo@$V/lydo" | kiem UC13 "Trùng điểm hiện tại (9.1)" "Điểm mới trùng với điểm hiện tại."
post "$C" -d "soPhach=P0023&action=kiem_tra&diemMoi=8.75&lyDo=" | kiem UC13 "Lý do trống (9.2)" "Vui lòng nhập lý do chỉnh sửa điểm."
post "$C" -d "soPhach=P0023&action=kiem_tra&diemMoi=8,75" --data-urlencode "lyDo@$V/lydo" | kiem UC13 "Bước 10: xác nhận (dấu phẩy 8,75)" "Xác nhận cập nhật"
get "$C?huy=1" | kiem UC13 "Hủy bỏ (10.1)" "không tạo lịch sử chỉnh sửa"
curl -s -c "$J" -b "$J" -L "$B/$C" -d "csrf_token=sai&soPhach=P0023&action=luu&diemMoi=8.75" --data-urlencode "lyDo@$V/lydo" | kiem UC13 "CSRF sai bị chặn" "Phiên làm việc đã hết hạn"
LS0=$(gtri "SELECT COUNT(*) FROM LichSuChinhSuaDiem")
post "$C" -d "soPhach=P0023&action=luu&diemMoi=8.75" --data-urlencode "lyDo@$V/lydo" | kiem UC13 "Lưu thành công (13)" "Cập nhật kết quả chấm thi thành công."
[ "$(gtri "SELECT diemThi FROM KetQuaChamThi WHERE maBaiThi='BT002'")" = 8.75 ] && [ "$(gtri "SELECT COUNT(*) FROM LichSuChinhSuaDiem")" = $((LS0+1)) ] && ok UC13 "DB: điểm 8.75 + thêm 1 lịch sử" "đúng" || ko UC13 "DB: điểm 8.75 + thêm 1 lịch sử" "đúng" "sai"
dung UC13 "Lịch sử ghi tên người đăng nhập" "$(gtri "SELECT nguoiThucHien FROM LichSuChinhSuaDiem ORDER BY thoiGianChinhSua DESC, maLichSu DESC LIMIT 1")" "Phan Thùy Dương"
trg UPDATE KetQuaChamThi
post "$C" -d "soPhach=P0004&action=luu&diemMoi=5" --data-urlencode "lyDo@$V/lydo" | kiem UC13 "Lỗi cập nhật điểm (11.1)" "Không thể cập nhật kết quả chấm thi. Vui lòng thực hiện lại."
trg INSERT LichSuChinhSuaDiem
post "$C" -d "soPhach=P0004&action=luu&diemMoi=5" --data-urlencode "lyDo@$V/lydo" | kiem UC13 "Lỗi ghi lịch sử (12.1)" "Không thể ghi nhận lịch sử chỉnh sửa điểm. Kết quả chấm thi chưa được cập nhật."
boTrg
[ "$(gtri "SELECT diemThi FROM KetQuaChamThi WHERE maBaiThi='BT005'")" = 4.75 ] && ok UC13 "Điểm cũ giữ nguyên sau lỗi" "4.75" || ko UC13 "Điểm cũ giữ nguyên sau lỗi" "4.75" "đã đổi"

# ================= UC14 Thống kê kết quả chấm thi =================
K="grading/thong-ke.php?thongKe=1&kyThi=KT2026"
get "$K&monThi=" | kiem UC14 "Thiếu môn thi (4.1)" "Vui lòng chọn đầy đủ Kỳ thi và Môn thi."
get "grading/thong-ke.php?thongKe=1&kyThi=&monThi=TOAN" | kiem UC14 "Thiếu kỳ thi (4.1)" "Vui lòng chọn đầy đủ Kỳ thi và Môn thi."
sql "INSERT INTO MonThi VALUES ('SU','Lịch sử',10);"
get "$K&monThi=SU" | kiem UC14 "Chưa có dữ liệu (5.1)" "Chưa có dữ liệu kết quả chấm thi cho Kỳ thi và Môn thi được chọn."
sql "DELETE FROM MonThi WHERE maMonThi='SU';"
HTML=$(get "$K&monThi=TOAN")
grep -qF ">15<" <<<"$HTML" && grep -qF "còn 3 bài chưa chấm" <<<"$HTML" && ok UC14 "Toán: 15 bài đã chấm, còn 3" "15 / còn 3" || ko UC14 "Toán: 15 bài đã chấm, còn 3" "15 / còn 3" "sai số"
TB=$(gtri "SELECT FORMAT(AVG(k.diemThi),2) FROM KetQuaChamThi k JOIN BaiThi b USING(maBaiThi) WHERE b.maMonThi='TOAN'"); grep -qF ">$TB<" <<<"$HTML" && ok UC14 "Điểm TB khớp CSDL" "$TB" || ko UC14 "Điểm TB khớp CSDL" "$TB" "không khớp"
db_exec "RENAME TABLE KetQuaChamThi TO KetQuaChamThi_kt;"
get "$K&monThi=TOAN" | kiem UC14 "Lỗi CSDL (5.2)" "Không thể thực hiện thống kê tại thời điểm hiện tại. Vui lòng thử lại sau."
db_exec "RENAME TABLE KetQuaChamThi_kt TO KetQuaChamThi;"

# ================= UC15 Công bố điểm thi =================
dang_nhap bantuyensinh
U="publication/diem-thi.php?xem=1&kyThi=KT2026"
get "$U&monThi=" | kiem UC15 "Thiếu môn thi" "Vui lòng chọn đầy đủ Kỳ thi và Môn thi."
get "$U&monThi=NN" | kiem UC15 "Bước 4: danh sách + nút Công bố" "Công bố điểm thi</a>"
get "$U&monThi=VAN&buoc=xac-nhan" | kiem UC15 "Đã công bố (6.1)" "Kết quả chấm thi đã được công bố."
sql "UPDATE KetQuaChamThi SET trangThaiCongBo='Chưa công bố' WHERE maBaiThi='BT001';"
get "$U&monThi=TOAN&buoc=xac-nhan" | kiem UC15 "Chưa đủ điều kiện (6.2)" "Kết quả chấm thi chưa đủ điều kiện công bố."
get "$U&monThi=NN&buoc=xac-nhan" | kiem UC15 "Bước 6: hộp xác nhận" "Xác nhận công bố điểm thi"
get "$U&monThi=NN&huy=1" | kiem UC15 "Hủy bỏ (7.1)" "Trạng thái kết quả chấm thi được giữ nguyên."
T=$(tok "$U&monThi=NN&buoc=xac-nhan")
curl -s -c "$J" -b "$J" -L "$B/$U&monThi=NN" -d "csrf_token=sai&action=cong_bo&kyThi=KT2026&monThi=NN" | kiem UC15 "CSRF sai bị chặn" "Phiên làm việc đã hết hạn"
trg UPDATE KetQuaChamThi
post "$U&monThi=NN" -d "action=cong_bo&kyThi=KT2026&monThi=NN" | kiem UC15 "Lỗi CSDL (8.1)" "Không thể công bố điểm thi tại thời điểm hiện tại. Vui lòng thử lại sau."
boTrg
[ "$(gtri "SELECT COUNT(*) FROM KetQuaChamThi k JOIN BaiThi b USING(maBaiThi) WHERE b.maMonThi='NN' AND k.trangThaiCongBo='Đã công bố'")" = 0 ] && ok UC15 "Trạng thái giữ nguyên sau lỗi" "0 bài" || ko UC15 "Trạng thái giữ nguyên sau lỗi" "0 bài" "đã đổi"
post "$U&monThi=NN" -d "action=cong_bo&kyThi=KT2026&monThi=NN" | kiem UC15 "Công bố thành công (9)" "Công bố điểm thi thành công."
post "$U&monThi=NN" -d "action=cong_bo&kyThi=KT2026&monThi=NN" | kiem UC15 "Bấm công bố lần 2 bị chặn" "Kết quả chấm thi đã được công bố."
[ "$(gtri "SELECT COUNT(*) FROM KetQuaChamThi k JOIN BaiThi b USING(maBaiThi) WHERE b.maMonThi='NN' AND k.trangThaiCongBo='Đã công bố'")" = 18 ] && ok UC15 "DB: 18 bài NN đã công bố, điểm không đổi" "18" || ko UC15 "DB: 18 bài NN đã công bố" "18" "sai"

# ================= Chung: trang mở được, không lỗi PHP, chặn thư mục nội bộ =================
for cap in "-:" "-:auth/login.php" "-:results/" "quantri:admin/" "catphach:papers/" "bantuyensinh:councils/" "hdcoithi:assignments/" "hdchamthi:grading/" "hdchamthi:grading/lich-su.php" "hdchamthi:grading/cap-nhat.php" "hdchamthi:grading/thong-ke.php" "bantuyensinh:publication/" "bantuyensinh:publication/diem-thi.php" "hdtuyensinh:publication/tuyen-sinh.php" "gvcn:auth/doi-mat-khau.php"; do
  tk="${cap%%:*}"; p="${cap#*:}"
  if [ "$tk" = "-" ]; then rm -f "$J"; TK_HIEN_TAI=""; elif [ "$tk" != "${TK_HIEN_TAI:-}" ]; then dang_nhap "$tk"; fi
  code=$(curl -s -c "$J" -b "$J" -o "$S/page.html" -w '%{http_code}' "$B/$p")
  if [ "$code" = 200 ] && ! grep -qi "fatal error\|warning:\|notice:\|deprecated:" "$S/page.html"; then ok CHUNG "Trang /$p ($tk)" "200, không lỗi PHP"; else ko CHUNG "Trang /$p ($tk)" "200, không lỗi PHP" "HTTP $code hoặc có lỗi PHP"; fi
done
dung CHUNG "Chặn truy cập services/" "$(curl -s -o "$NUL" -w '%{http_code}' "$B/services/grading_service.php")" 403
dung CHUNG "Chặn tải database/install.sql" "$(curl -s -o "$NUL" -w '%{http_code}' "$B/database/install.sql")" 403

# ================= Tổng kết =================
TONG=$(grep -c '' "$OUT"); LOI=$(grep -c $'\tLỖI' "$OUT")
echo
echo "KẾT QUẢ KIỂM THỬ: đăng nhập / phân quyền + UC09–UC15 ($B)"
awk -F'\t' '{ printf "%-6s %-4s %s\n", $1, (index($4,"ĐẠT")==1 ? "ĐẠT" : "LỖI"), $2 }' "$OUT"
echo
for uc in DN DMK DX QUYEN HS UC09 UC10 UC11 UC12 UC13 UC14 UC15 CHUNG; do
  printf '  %-6s %3s ca, %s lỗi\n' "$uc" "$(grep -c "^$uc"$'\t' "$OUT")" "$(grep "^$uc"$'\t' "$OUT" | grep -c $'\tLỖI')"
done
echo "TỔNG: $TONG ca — ĐẠT $((TONG-LOI)), LỖI $LOI"
if [ "$LOI" -gt 0 ]; then echo; echo "Chi tiết ca lỗi:"; grep $'\tLỖI' "$OUT" | awk -F'\t' '{ print "  - " $1 " | " $2 " | mong đợi: " $3 " | " $4 }'; exit 1; fi
exit 0
