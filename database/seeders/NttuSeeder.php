<?php

namespace Database\Seeders;

use App\Models\BuildingBlock;
use App\Models\Classroom;
use App\Models\DailySchedule;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Gift;
use App\Models\IncidentCategory;
use App\Models\Lecturer;
use App\Models\Position;
use App\Models\Recognition;
use App\Models\Role;
use App\Models\SystemParameter;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class NttuSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['id' => 'system', 'name' => 'Hệ thống', 'note' => 'Quản trị viên cao nhất', 'permissions' => []],
            ['id' => 'controller', 'name' => 'Kiểm soát viên', 'note' => 'Kiểm soát viên phòng ban', 'permissions' => []],
            ['id' => 'staff', 'name' => 'Nhân viên', 'note' => 'Nhân viên phòng ban', 'permissions' => []],
        ];

        foreach ($roles as $role) {
            $perms = app(PermissionService::class)->defaultPermissionsForRole($role['id']);
            Role::updateOrCreate(['id' => $role['id']], array_merge($role, ['permissions' => $perms]));
        }

        $departments = [
            ['id' => 'K-CNTT', 'department_id' => 'K-CNTT', 'name' => 'Khoa Công nghệ thông tin', 'head' => 'Nguyễn Văn A'],
            ['id' => 'K-QTKD', 'department_id' => 'K-QTKD', 'name' => 'Khoa Quản trị kinh doanh', 'head' => 'Lê Văn C'],
            ['id' => 'PKTNB', 'department_id' => 'PKTNB', 'name' => 'Phòng kiểm tra nội bộ', 'head' => 'Nguyễn Vĩnh Phúc'],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(['id' => $dept['id']], array_merge([
                'deputy_head' => '', 'secretary' => '', 'spokesperson' => '',
                'phone' => '', 'email' => '', 'note' => '',
            ], $dept));
        }

        $admin = User::updateOrCreate(
            ['email' => 'ngviphuc@gmail.com'],
            [
                'name' => 'Nguyễn Vĩnh Phúc',
                'password' => Hash::make('Nttu@2026'),
                'email_verified_at' => now(),
            ]
        );

        foreach ([
            ['id' => 'chuyen-vien', 'name' => 'Chuyên viên'],
            ['id' => 'truong-phong', 'name' => 'Trưởng phòng'],
        ] as $pos) {
            Position::updateOrCreate(['id' => $pos['id']], ['name' => $pos['name'], 'note' => '']);
        }

        foreach ([
            ['id' => 'GV001', 'name' => 'GS. TS. Nguyễn Văn A', 'department' => 'Khoa Công nghệ thông tin', 'email' => 'a.nv@ntt.edu.vn'],
            ['id' => 'GV002', 'name' => 'PGS. TS. Trần Thị B', 'department' => 'Khoa Quản trị kinh doanh', 'email' => 'b.tt@ntt.edu.vn'],
        ] as $lec) {
            Lecturer::updateOrCreate(['id' => $lec['id']], $lec);
        }

        foreach ([
            ['id' => 'block-a', 'code' => 'A', 'name' => 'Dãy nhà A'],
            ['id' => 'block-l', 'code' => 'L', 'name' => 'Dãy nhà L'],
        ] as $block) {
            BuildingBlock::updateOrCreate(['id' => $block['id']], array_merge($block, ['is_inactive' => false, 'note' => '']));
        }

        foreach ([
            ['id' => 'A.801', 'name' => 'A.801', 'building_block_id' => 'block-a', 'seating_capacity' => 60, 'room_type' => 'Lý thuyết', 'has_projector' => true],
            ['id' => 'L.101', 'name' => 'L.101', 'building_block_id' => 'block-l', 'seating_capacity' => 80, 'room_type' => 'Lý thuyết', 'has_projector' => true],
        ] as $room) {
            Classroom::updateOrCreate(['id' => $room['id']], array_merge([
                'table_count' => null, 'exam_capacity' => null, 'is_inactive' => false, 'note' => '',
            ], $room));
        }

        foreach ([
            ['id' => 'good-deed', 'name' => 'Việc tốt', 'note' => 'Ghi nhận các hành động tốt'],
            ['id' => 'violation', 'name' => 'Vi phạm', 'note' => 'Ghi nhận các hành vi vi phạm'],
            ['id' => 'external-practice', 'name' => 'Thực hành ngoài', 'note' => 'Thực hành ngoài trường'],
        ] as $rec) {
            Recognition::updateOrCreate(['id' => $rec['id']], $rec);
        }

        foreach ([
            ['id' => 'cheating', 'recognition_id' => 'violation', 'name' => 'Gian lận thi cử', 'note' => ''],
            ['id' => 'asset-return', 'recognition_id' => 'good-deed', 'name' => 'Nhặt của rơi trả người mất', 'note' => ''],
        ] as $cat) {
            IncidentCategory::updateOrCreate(['id' => $cat['id']], $cat);
        }

        foreach ([
            ['id' => 'thu-khen', 'name' => 'Thư khen', 'note' => ''],
            ['id' => 'giay-khen', 'name' => 'Giấy khen', 'note' => ''],
        ] as $gift) {
            Gift::updateOrCreate(['id' => $gift['id']], $gift);
        }

        Employee::updateOrCreate(
            ['id' => 'NTT-02715-UID'],
            [
                'user_id' => $admin->id,
                'employee_id' => 'NTT-02715',
                'name' => 'Nguyễn Vĩnh Phúc',
                'nickname' => 'Phúc',
                'position' => 'Chuyên viên',
                'birth_date' => '15/07/1992',
                'address' => 'Quận 12, TP.HCM',
                'phone' => '0937382399',
                'role_id' => 'system',
                'email' => 'ngviphuc@gmail.com',
            ]
        );

        $today = date('d/m/Y');

        foreach ([
            [
                'id' => 'sched-online-1',
                'date' => $today,
                'building' => 'Trực tuyến',
                'room' => 'Zoom',
                'period' => '1',
                'department' => 'Khoa Công nghệ thông tin',
                'class' => 'CNTT01',
                'student_count' => 45,
                'lecturer' => 'GS. TS. Nguyễn Văn A',
                'content' => 'Lập trình Web',
                'status' => 'Học bình thường',
            ],
            [
                'id' => 'sched-inperson-1',
                'date' => $today,
                'building' => 'A',
                'room' => 'A.801',
                'period' => '2',
                'department' => 'Khoa Công nghệ thông tin',
                'class' => 'CNTT02',
                'student_count' => 50,
                'lecturer' => 'GS. TS. Nguyễn Văn A',
                'content' => 'Cơ sở dữ liệu',
                'status' => 'Học bình thường',
            ],
            [
                'id' => 'sched-exam-1',
                'date' => $today,
                'building' => 'L',
                'room' => 'L.101',
                'period' => '3',
                'department' => 'Khoa Quản trị kinh doanh',
                'class' => 'QTKD01',
                'student_count' => 40,
                'lecturer' => 'PGS. TS. Trần Thị B',
                'content' => 'Thi môn Marketing',
                'status' => 'Phòng thi',
            ],
            [
                'id' => 'sched-external-1',
                'date' => $today,
                'building' => 'Ngoài trường',
                'room' => 'Bệnh viện',
                'period' => '4',
                'department' => 'Khoa Y',
                'class' => 'Y01',
                'student_count' => 30,
                'lecturer' => 'ThS. Lê Văn D',
                'content' => 'Thực hành lâm sàng',
                'status' => 'Học bình thường',
            ],
            [
                'id' => 'sched-homeroom-1',
                'date' => $today,
                'building' => 'A',
                'room' => 'A.801',
                'period' => '5',
                'department' => 'Khoa Công nghệ thông tin',
                'class' => 'CNTT01',
                'student_count' => 45,
                'lecturer' => 'CVHT Nguyễn Văn E',
                'content' => 'SHCN - Sinh hoạt chủ nhiệm',
                'status' => 'SHCN',
            ],
        ] as $sched) {
            DailySchedule::updateOrCreate(['id' => $sched['id']], $sched);
        }

        foreach ([
            ['key' => 'app_name', 'value' => 'Phòng Kiểm tra Nội bộ - NTTU'],
            ['key' => 'academic_year', 'value' => '2025-2026'],
        ] as $param) {
            SystemParameter::updateOrCreate(['key' => $param['key']], $param);
        }
    }
}
