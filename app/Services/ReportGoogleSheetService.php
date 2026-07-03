<?php

namespace App\Services;

use App\Models\Employee;

class ReportGoogleSheetService
{
    /**
     * @return list<array{key: string, label: string}>
     */
    public function pushFieldDefinitions(string $tabKey): array
    {
        return match ($tabKey) {
            'request-reports' => [
                ['key' => 'code', 'label' => 'Số vào sổ'],
                ['key' => 'recipient', 'label' => 'Nhân sự tiếp nhận'],
                ['key' => 'receptionDate', 'label' => 'Ngày tiếp nhận'],
                ['key' => 'building', 'label' => 'Cơ sở'],
                ['key' => 'studentName', 'label' => 'Họ và tên SV'],
                ['key' => 'studentId', 'label' => 'MSSV/CCCD'],
                ['key' => 'class', 'label' => 'Lớp'],
                ['key' => 'department', 'label' => 'Đơn vị Khoa/Trung tâm/Viện'],
                ['key' => 'requestType', 'label' => 'Nội dung tiếp nhận'],
                ['key' => 'content', 'label' => 'Ghi rõ nội dung ghi nhận'],
                ['key' => 'status', 'label' => 'Tình trạng giải quyết'],
            ],
            'good-deeds-property' => [
                ['key' => 'code', 'label' => 'Số vào sổ'],
                ['key' => 'campus', 'label' => 'Cơ sở'],
                ['key' => 'receptionDate', 'label' => 'Ngày tiếp nhận'],
                ['key' => 'recipient', 'label' => 'Nhân sự tiếp nhận'],
                ['key' => 'finderName', 'label' => 'Họ và tên người giao TS'],
                ['key' => 'finderId', 'label' => 'MSSV/CCCD/SĐT'],
                ['key' => 'finderDept', 'label' => 'Đơn vị người giao'],
                ['key' => 'property', 'label' => 'Nội dung TS'],
                ['key' => 'returnDate', 'label' => 'Ngày giao trả'],
                ['key' => 'returner', 'label' => 'Nhân sự giao trả'],
                ['key' => 'ownerName', 'label' => 'Họ và tên người nhận'],
                ['key' => 'ownerId', 'label' => 'MSSV/CCCD người nhận'],
                ['key' => 'ownerClass', 'label' => 'Lớp người nhận'],
                ['key' => 'ownerDept', 'label' => 'Đơn vị người nhận'],
                ['key' => 'ownerPhone', 'label' => 'SĐT người nhận'],
            ],
            'good-deeds-deed' => [
                ['key' => 'appreciationCode', 'label' => 'Số vào sổ'],
                ['key' => 'appreciationCampus', 'label' => 'Cơ sở'],
                ['key' => 'appreciationName', 'label' => 'Họ và tên'],
                ['key' => 'appreciationRecDate', 'label' => 'Ngày tiếp nhận tài sản'],
                ['key' => 'appreciationGiveDate', 'label' => 'Ngày trao tặng thư'],
                ['key' => 'gift', 'label' => 'Quà'],
                ['key' => 'appreciationId', 'label' => 'MSSV/CCCD; Số điện thoại'],
                ['key' => 'appreciationDept', 'label' => 'Đơn vị'],
                ['key' => 'refCode', 'label' => 'Số vào sổ tiếp nhận và bàn giao tài sản'],
                ['key' => 'note', 'label' => 'Ghi chú'],
            ],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public function pushFieldKeys(string $tabKey): array
    {
        return array_column($this->pushFieldDefinitions($tabKey), 'key');
    }

    public function supportsTabKey(string $tabKey): bool
    {
        return $this->pushFieldKeys($tabKey) !== [];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public function prepareRows(string $tabKey, array $rows): array
    {
        $fields = $this->pushFieldKeys($tabKey);
        if ($fields === []) {
            return $rows;
        }

        return array_values(array_map(
            fn (array $row) => $this->normalizeRow($tabKey, $row, $fields),
            $rows,
        ));
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    private function normalizeRow(string $tabKey, array $row, array $fields): array
    {
        $normalized = [];
        foreach ($fields as $field) {
            $normalized[$field] = $this->normalizeFieldValue($tabKey, $field, $row[$field] ?? null);
        }

        return $normalized;
    }

    private function normalizeFieldValue(string $tabKey, string $field, mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $text = trim((string) $value);
        if ($text === '' || $text === '---') {
            return '';
        }

        if ($this->shouldUseStaffNickname($tabKey, $field)) {
            $text = Employee::nicknameFor($text);
        }

        return $text;
    }

    private function shouldUseStaffNickname(string $tabKey, string $field): bool
    {
        return match ($field) {
            'recipient' => in_array($tabKey, ['request-reports', 'good-deeds-property'], true),
            'returner' => $tabKey === 'good-deeds-property',
            default => false,
        };
    }
}
