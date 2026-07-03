<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Employee;
use Illuminate\Support\Collection;

class ActivityLogPresenter
{
    public function __construct(
        private ActivityLogMainFeatureResolver $mainFeatures,
    ) {}

    /** @var array<string, string> */
    private const ACTION_LABELS = [
        'LOGIN' => 'Đăng nhập',
        'LOGOUT' => 'Đăng xuất',
        'CREATE' => 'Thêm mới',
        'UPDATE' => 'Cập nhật',
        'DELETE' => 'Xóa',
        'VIEW' => 'Xem',
    ];

    /** @var array<string, string> */
    private const TARGET_TYPE_MAP = [
        'monitoring_in_person' => 'Lớp học trực tiếp',
        'monitoring_online' => 'Lớp học online',
        'monitoring_homeroom' => 'Cố vấn học tập',
        'monitoring_external_practice' => 'Thực hành ngoài',
        'monitoring_exams' => 'Thi kết thúc môn',
        'petitions_citizen' => 'Đơn từ - Tiếp công dân',
        'petitions_student' => 'Đơn từ - Yêu cầu sinh viên',
        'others_incidents' => 'Khác - Sự cố phòng học',
        'others_warnings' => 'Khác - Cảnh báo',
        'reports_daily' => 'Báo cáo - Trực ban',
        'reports_logs' => 'Báo cáo - Nhật ký',
        'reports_engagement' => 'Báo cáo - Mức độ tương tác',
        'settings_master_data' => 'Cài đặt - Danh mục',
        'settings_parameters' => 'Cài đặt - Tham số hệ thống',
        'settings_roles' => 'Cài đặt - Phân quyền',
        'settings_access_log' => 'Cài đặt - Nhật ký truy cập',
        'utilities_statistics' => 'Tiện ích - Thống kê',
        'utilities_asset_reception' => 'Tiện ích - Tiếp nhận tài sản',
        'utilities_asset_gratitude' => 'Tiện ích - Tri ân tài sản',
        'System' => 'Hệ thống',
        'Nhân viên' => 'Hồ sơ nhân viên',
        'Giảng viên' => 'Danh mục - Giảng viên',
        'Sinh viên' => 'Danh mục - Sinh viên',
        'Dãy nhà' => 'Danh mục - Dãy nhà',
        'Đơn vị' => 'Danh mục - Đơn vị',
        'Phòng học' => 'Danh mục - Phòng học',
        'Chức vụ' => 'Danh mục - Chức vụ',
        'Vai trò' => 'Danh mục - Vai trò',
        'good-deeds' => 'Người tốt việc tốt',
        'requests' => 'Tiếp nhận yêu cầu',
        'petitions' => 'Tiếp nhận đơn thư',
        'asset-check' => 'Nhận - Trả tài sản',
        'student-violations' => 'Sinh viên vi phạm',
        'document-records' => 'Quản lý hồ sơ',
        'DailySchedule' => 'Giám sát lịch học',
        'Employee' => 'Hồ sơ nhân viên',
        'Lecturer' => 'Danh mục - Giảng viên',
        'Student' => 'Danh mục - Sinh viên',
        'Department' => 'Danh mục - Đơn vị',
        'Classroom' => 'Danh mục - Phòng học',
        'BuildingBlock' => 'Danh mục - Dãy nhà',
        'Role' => 'Danh mục - Vai trò',
        'Message' => 'Hộp thư nội bộ',
        'Dashboard' => 'Bảng điều khiển',
    ];

    /** @param  Collection<int, Employee>  $employees */
    public function present(ActivityLog $log, Collection $employees): array
    {
        return $this->presentWithLookup($log, $this->buildEmployeeLookup($employees));
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return array{
     *   byUserId: array<string, Employee>,
     *   byId: array<string, Employee>,
     *   byEmployeeId: array<string, Employee>,
     *   byEmail: array<string, Employee>
     * }
     */
    public function buildEmployeeLookup(Collection $employees): array
    {
        $byUserId = [];
        $byId = [];
        $byEmployeeId = [];
        $byEmail = [];

        foreach ($employees as $employee) {
            if ($employee->user_id) {
                $byUserId[(string) $employee->user_id] = $employee;
            }
            if ($employee->id) {
                $byId[strtolower($employee->id)] = $employee;
            }
            if ($employee->employee_id) {
                $byEmployeeId[strtolower($employee->employee_id)] = $employee;
            }
            if ($employee->email) {
                $byEmail[strtolower($employee->email)] = $employee;
            }
        }

        return compact('byUserId', 'byId', 'byEmployeeId', 'byEmail');
    }

    /**
     * @param  array{
     *   byUserId: array<string, Employee>,
     *   byId: array<string, Employee>,
     *   byEmployeeId: array<string, Employee>,
     *   byEmail: array<string, Employee>
     * }  $lookup
     */
    public function presentWithLookup(ActivityLog $log, array $lookup): array
    {
        $loggedAt = $log->logged_at;
        $userId = strtolower(trim((string) ($log->user_id ?? '')));
        $email = strtolower(trim((string) ($log->user_email ?? '')));

        $employee = ($log->user_id ? ($lookup['byUserId'][(string) $log->user_id] ?? null) : null)
            ?? ($userId !== '' ? ($lookup['byId'][$userId] ?? null) : null)
            ?? ($userId !== '' ? ($lookup['byEmployeeId'][$userId] ?? null) : null)
            ?? ($email !== '' ? ($lookup['byEmail'][$email] ?? null) : null);

        $userName = $employee?->nickname ?: $employee?->name;
        if (! $userName) {
            $userName = $this->fallbackUserName($userId, $log->user_email);
        }

        $rawModule = $log->target_type ?: 'System';
        $module = self::TARGET_TYPE_MAP[$rawModule] ?? $rawModule;
        if (in_array($rawModule, ['Nhân viên', 'Employee', 'Hồ sơ nhân viên'], true)) {
            $module = 'Hồ sơ cá nhân';
        }

        $actionKey = $this->normalizeActionKey($log->action);

        $row = [
            'id' => $log->id,
            'formattedTime' => $loggedAt?->format('d/m/Y H:i:s') ?? '',
            'dateOnly' => $loggedAt?->format('d/m/Y') ?? '',
            'isoDate' => $loggedAt?->format('Y-m-d') ?? '',
            'userId' => $log->user_id,
            'userName' => $userName,
            'userEmail' => $log->user_email ?: $employee?->email ?: '',
            'action' => $actionKey,
            'actionRaw' => $log->action,
            'actionLabel' => $this->actionLabel($actionKey, $log->action),
            'targetType' => $rawModule,
            'module' => $module,
            'details' => $log->details ?? '',
            'ipAddress' => $log->ip_address ?? '',
            'previousData' => $log->previous_data,
            'newData' => $log->new_data,
            'employeeRefId' => $employee?->id ?: $employee?->email ?: $employee?->employee_id,
        ];

        $feature = $this->mainFeatures->resolve($row);
        if ($feature['key'] !== ActivityLogMainFeatureResolver::OTHER_KEY) {
            $row['module'] = $feature['label'];
        }
        $row['mainFeatureKey'] = $feature['key'];
        $row['mainFeatureLabel'] = $feature['label'];

        return $row;
    }

    /** @return list<array{id: string, name: string, email: string}> */
    public function userFilterOptions(Collection $employees): array
    {
        return $employees
            ->map(fn (Employee $emp) => [
                'id' => $emp->id ?: $emp->email ?: $emp->employee_id,
                'name' => $emp->nickname ?: $emp->name,
                'email' => $emp->email ?? '',
            ])
            ->filter(fn (array $row) => $row['id'] && $row['name'])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    private function fallbackUserName(string $userId, ?string $email): string
    {
        if ($email) {
            return $email;
        }

        if ($this->looksLikeUid($userId)) {
            return 'Quản trị viên';
        }

        if ($userId === '' || $userId === 'system') {
            return 'Hệ thống';
        }

        return $userId ?: 'Hệ thống';
    }

    private function looksLikeUid(?string $value): bool
    {
        if (! $value) {
            return false;
        }

        $v = trim($value);

        return strlen($v) >= 10
            && ! str_contains($v, ' ')
            && preg_match('/[0-9]/', $v)
            && preg_match('/[a-z]/i', $v);
    }

    private function normalizeActionKey(?string $action): string
    {
        $action = trim((string) $action);
        if ($action === '') {
            return 'VIEW';
        }

        $upper = mb_strtoupper($action, 'UTF-8');

        foreach (['LOGIN', 'LOGOUT', 'CREATE', 'UPDATE', 'DELETE', 'VIEW'] as $key) {
            if ($upper === $key || str_contains($upper, $key)) {
                return $key;
            }
        }

        if (preg_match('/đăng nhập|login/i', $action)) {
            return 'LOGIN';
        }
        if (preg_match('/đăng xuất|logout/i', $action)) {
            return 'LOGOUT';
        }
        if (preg_match('/thêm lịch|thêm mới|thêm|create|tạo mới/i', $action)) {
            return 'CREATE';
        }
        if (preg_match('/ghi nhận|cập nhật|update|sửa|đổi mật khẩu/i', $action)) {
            return 'UPDATE';
        }
        if (preg_match('/xóa lịch|xóa nhật ký|xóa|delete/i', $action)) {
            return 'DELETE';
        }
        if (preg_match('/xem|view/i', $action)) {
            return 'VIEW';
        }

        return $upper;
    }

    private function actionLabel(string $actionKey, ?string $rawAction): string
    {
        $raw = trim((string) $rawAction);

        if ($raw !== '' && ! preg_match('/^(LOGIN|LOGOUT|CREATE|UPDATE|DELETE|VIEW)$/i', $raw)) {
            return $raw;
        }

        return self::ACTION_LABELS[$actionKey] ?? ($raw !== '' ? $raw : 'Xem');
    }
}
