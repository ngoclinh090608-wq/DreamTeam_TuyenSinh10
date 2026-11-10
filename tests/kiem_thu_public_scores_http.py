import os,re,html,http.cookiejar,urllib.request,urllib.parse,subprocess
base=os.environ.get('BASE_URL','http://127.0.0.1:8080').rstrip('/')
name=os.environ.get('DB_NAME','');assert re.fullmatch(r'qa_dt_[a-z0-9_]+',name)
env=os.environ.copy();env['MYSQL_PWD']=env.get('DB_PASS','')
cmd=['mysql','-h',env.get('DB_HOST','127.0.0.1'),'-P',env.get('DB_PORT','3306'),'-u',env.get('DB_USER','root'),'--default-character-set=utf8mb4','-N',name]
q="SELECT t.soBaoDanh,t.ngaySinh FROM ThiSinh t JOIN BaiThi b ON b.maThiSinh=t.maThiSinh JOIN KetQuaChamThi g ON g.maBaiThi=b.maBaiThi WHERE t.soBaoDanh IS NOT NULL AND g.trangThaiCongBo='Đã công bố' LIMIT 1;"
number,birth=subprocess.check_output(cmd,input=q,text=True,env=env).strip().split('\t')
c=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def request(path,data=None):
    r=c.open(base+path,None if data is None else urllib.parse.urlencode(data).encode());return r.read().decode(),r.url,r.headers
page,url,headers=request('/tra-cuu-diem/')
assert '/auth/login.php' not in url and 'Không cần đăng nhập' in request('/')[0]
assert headers['Cache-Control']=='no-store'
token=html.unescape(re.search(r'name="csrf_token" value="([^"]+)"',page)[1])
form={'sbd':number,'ngaySinh':birth,'csrf_token':'bad'}
assert 'Phiên yêu cầu không hợp lệ' in request('/tra-cuu-diem/',form)[0]
form['csrf_token']=token
result,_,_=request('/tra-cuu-diem/',form)
assert '<th>Điểm</th>' in result and '<tbody>' in result
assert 'maThiSinh' not in result and 'maBaiThi' not in result
form['ngaySinh']='1900-01-01'
assert 'Không tìm thấy điểm đã công bố' in request('/tra-cuu-diem/',form)[0]
assert '/auth/login.php' in request('/results/')[1]
assert '/auth/login.php' in request('/aspirations/')[1]
print('PASS public HTTP: anonymous entry, cache, CSRF, verified lookup, wrong DOB and private routes')
