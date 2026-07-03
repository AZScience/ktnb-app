<?php

namespace App\Services;

class ActivityLogMainFeatureResolver
{
    public const OTHER_KEY = 'other';

    public const OTHER_LABEL = 'Khác (hệ thống & danh mục)';

    /** @var array<string, string> */
    public const FEATURES = [
        'homeroom' => 'Cố vấn học tập',
        'online' => 'Lớp học online',
        'in-person' => 'Lớp học trực tiếp',
        'exams' => 'Thi kết thúc môn',
        'external-practice' => 'Thực hành ngoài',
        'student-violations' => 'Sinh viên vi phạm',
        'asset-check' => 'Nhận - Trả tài sản',
        'requests' => 'Tiếp nhận yêu cầu',
        'petitions' => 'Tiếp nhận đơn thư',
        'document-records' => 'Quản lý hồ sơ',
    ];

    /** @var list<string> */
    public const FEATURE_ORDER = [
        'homeroom',
        'online',
        'in-person',
        'exams',
        'external-practice',
        'student-violations',
        'asset-check',
        'requests',
        'petitions',
        'document-records',
        self::OTHER_KEY,
    ];

    /**
     * @param  array<string, mixed>  $log
     * @return array{key: string, label: string}
     */
    public function resolve(array $log): array
    {
        $key = $this->resolveKey($log);

        return [
            'key' => $key,
            'label' => $key === self::OTHER_KEY
                ? self::OTHER_LABEL
                : (self::FEATURES[$key] ?? self::OTHER_LABEL),
        ];
    }

    /** @param  array<string, mixed>  $log */
    private function resolveKey(array $log): string
    {
        $target = strtolower(trim((string) ($log['targetType'] ?? '')));
        $module = strtolower(trim((string) ($log['module'] ?? '')));
        $details = strtolower(trim((string) ($log['details'] ?? '')));
        $haystack = implode(' ', array_filter([$target, $module, $details]));

        if (preg_match('/module\s+(homeroom)\b/', $details, $match)) {
            return $match[1];
        }
        if (preg_match('/module\s+(online)\b/', $details, $match)) {
            return $match[1];
        }
        if (preg_match('/module\s+(in-person)\b/', $details, $match)) {
            return $match[1];
        }
        if (preg_match('/module\s+(exams)\b/', $details, $match)) {
            return $match[1];
        }
        if (preg_match('/module\s+(external-practice)\b/', $details, $match)) {
            return $match[1];
        }

        $rules = [
            'homeroom' => [
                'monitoring_homeroom', 'homeroom', 'cố vấn học tập', 'cố vấn', 'gvcn',
                'theo dõi - cố vấn',
            ],
            'online' => [
                'monitoring_online', 'online-classes', 'online', 'lớp học online',
                'giảng dạy trực tuyến', 'trực tuyến',
            ],
            'in-person' => [
                'monitoring_in_person', 'in-person', 'lớp học trực tiếp', 'giảng dạy trực tiếp',
            ],
            'exams' => [
                'monitoring_exams', 'exams', 'thi kết thúc môn', 'thi kết thúc',
            ],
            'external-practice' => [
                'monitoring_external_practice', 'external-practice', 'external-checkins',
                'thực hành ngoài', 'thực tập', 'thực tế',
            ],
            'student-violations' => [
                'student-violations', 'studentviolation', 'sinh viên vi phạm', 'vi phạm sinh viên',
            ],
            'asset-check' => [
                'asset-check', 'asset-reception', 'asset-return', 'asset-gratitude',
                'utilities_asset_reception', 'nhận - trả tài sản', 'tiếp nhận tài sản', 'trả tài sản',
            ],
            'requests' => [
                'requests', 'service-requests', 'tiếp nhận yêu cầu',
            ],
            'petitions' => [
                'petitions', 'petitions_citizen', 'petitions_student', 'tiếp nhận đơn thư', 'tiếp công dân', 'đơn thư',
            ],
            'document-records' => [
                'document-records', 'documentrecord', 'quản lý hồ sơ', 'hồ sơ văn bản',
            ],
        ];

        foreach ($rules as $featureKey => $needles) {
            foreach ($needles as $needle) {
                if ($needle !== '' && str_contains($haystack, $needle)) {
                    return $featureKey;
                }
            }
        }

        return self::OTHER_KEY;
    }
}
