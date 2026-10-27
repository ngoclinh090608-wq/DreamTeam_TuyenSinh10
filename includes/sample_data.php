<?php
/** 100% dữ liệu giả lập minh họa. Không dùng để xét tuyển thật. */
return [
  'schools' => [
    ['name' => 'THPT Bình Minh', 'district' => 'Khu vực 1', 'quota' => 540, 'code' => 'MH-01', 'type'=>'THPT'],
    ['name' => 'THPT Hoa Sen', 'district' => 'Khu vực 2', 'quota' => 450, 'code' => 'MH-02', 'type'=>'THPT'],
    ['name' => 'THPT Hướng Dương', 'district' => 'Khu vực 3', 'quota' => 480, 'code' => 'MH-03', 'type'=>'THPT'],
    ['name' => 'THPT Ánh Dương', 'district' => 'Khu vực 1', 'quota' => 420, 'code' => 'MH-04', 'type'=>'THPT'],
  ],
  'candidates' => [
    ['id'=>'TS-D001','name'=>'Nguyễn Văn A','school'=>'THCS Mẫu A','status'=>'Đủ điều kiện','date'=>'12/10/2026'],
    ['id'=>'TS-D002','name'=>'Trần Thị B','school'=>'THCS Mẫu B','status'=>'Đã duyệt','date'=>'12/10/2026'],
    ['id'=>'TS-D003','name'=>'Lê Văn C','school'=>'THCS Mẫu A','status'=>'Chờ duyệt','date'=>'13/10/2026'],
    ['id'=>'TS-D004','name'=>'Phạm Thị D','school'=>'THCS Mẫu C','status'=>'Đã duyệt','date'=>'13/10/2026'],
  ],
  'staff' => [
    ['id'=>'CB001','name'=>'Nguyễn Văn M','school'=>'THPT Mẫu 01','role'=>'Cán bộ coi thi','status'=>'Sẵn sàng'],
    ['id'=>'CB002','name'=>'Trần Thị N','school'=>'THPT Mẫu 02','role'=>'Giám thị 1','status'=>'Đã phân công'],
    ['id'=>'CB003','name'=>'Lê Văn P','school'=>'THPT Mẫu 01','role'=>'Giám thị 2','status'=>'Sẵn sàng'],
    ['id'=>'CB004','name'=>'Phạm Thị Q','school'=>'THPT Mẫu 03','role'=>'Cán bộ ra đề','status'=>'Đã phân công'],
  ],
  'assignments' => [
    ['id'=>'PC001','staff'=>'Trần Thị N','subject'=>'Toán','room'=>'P.101','role'=>'Giám thị 1','status'=>'Đã phân công'],
    ['id'=>'PC002','staff'=>'Lê Văn P','subject'=>'Ngữ văn','room'=>'P.102','role'=>'Giám thị 2','status'=>'Đã phân công'],
    ['id'=>'PC003','staff'=>'Nguyễn Văn M','subject'=>'Ngoại ngữ','room'=>'P.103','role'=>'Giám thị 1','status'=>'Chờ xác nhận'],
  ],
];
