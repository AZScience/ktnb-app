<?php

namespace App\Services;

use App\Models\BuildingBlock;
use App\Models\IncidentCategory;
use App\Models\Recognition;
use App\Models\StudentViolation;

class StudentViolationCrosstabService
{
    /** @var list<array{key: string, label: string, aliases: list<string>}> */
    public const VIOLATION_TYPE_COLUMNS = [
        ['key' => 'cup_tiet', 'label' => 'Cúp tiết', 'aliases' => ['cúp tiết']],
        ['key' => 'di_hoc_tre', 'label' => 'Đi học trễ', 'aliases' => ['đi học trễ', 'đi trễ']],
        ['key' => 'dong_phuc', 'label' => 'Đồng phục không đúng quy định', 'aliases' => ['đồng phục không đúng quy định']],
        ['key' => 'khong_the', 'label' => 'Không đeo thẻ SV', 'aliases' => ['không đeo thẻ sv']],
        ['key' => 'mat_trat_tu', 'label' => 'Mất trật tự', 'aliases' => ['mất trật tự']],
        ['key' => 'hut_thuoc', 'label' => 'Hút thuốc trong khuôn viên Trường', 'aliases' => ['hút thuốc trong khuôn viên trường']],
        ['key' => 'lam_rieng', 'label' => 'Làm riêng trong giờ học', 'aliases' => ['làm riêng trong giờ học']],
        ['key' => 'vo_le', 'label' => 'Vô lễ với CB-GV-NV', 'aliases' => ['vô lễ với cb-gv-nv']],
        ['key' => 'hung_khi', 'label' => 'Mang hung khí vào trường', 'aliases' => ['mang hung khí vào trường']],
        ['key' => 'gay_go', 'label' => 'Gây gổ đánh nhau', 'aliases' => ['gây gổ đánh nhau']],
        ['key' => 'dua_nguoi', 'label' => 'Đưa người ngoài vào đánh nhau', 'aliases' => ['đưa người ngoài vào đánh nhau']],
        ['key' => 'danh_bai', 'label' => 'Đánh bài', 'aliases' => ['đánh bài']],
        ['key' => 'khac', 'label' => 'Các vi phạm khác', 'aliases' => ['các vi phạm khác', 'ghi nhận khác']],
    ];

    /** @var array<string, string>|null */
    private ?array $typeKeyByNormalizedLabel = null;

    /** @var array<string, string>|null normalized building name/code => note */
    private ?array $buildingNoteByKey = null;

    /**
     * @return list<array{key: string, label: string}>
     */
    public function fixedColumns(): array
    {
        return [
            ['key' => 'officer', 'label' => 'Nhân sự ghi nhận'],
            ['key' => 'violation_date', 'label' => 'Ngày ghi nhận'],
            ['key' => 'building', 'label' => 'Cơ sở'],
        ];
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function violationTypeColumns(): array
    {
        return array_map(
            fn (array $col) => ['key' => $col['key'], 'label' => $col['label']],
            self::VIOLATION_TYPE_COLUMNS,
        );
    }

    /**
     * @return list<string>
     */
    public function exportColumnKeys(): array
    {
        return [
            ...array_column($this->fixedColumns(), 'key'),
            ...array_column($this->violationTypeColumns(), 'key'),
        ];
    }

    /**
     * Cột xuất Excel sheet VIPHAM — khớp mẫu (không còn nhóm HỖ TRỢ GV ĐIỂM DANH).
     *
     * @return list<string>
     */
    public function spreadsheetExportColumnKeys(): array
    {
        return $this->exportColumnKeys();
    }

    /**
     * Cột đẩy Google Sheet — giữ 2 cột trống (E–F) khớp bảng mẫu HỖ TRỢ GV trên Sheet.
     *
     * @return list<string>
     */
    public function googleSheetExportColumnKeys(): array
    {
        return [
            ...array_column($this->fixedColumns(), 'key'),
            '__gap__',
            '__gap__',
            ...array_column($this->violationTypeColumns(), 'key'),
        ];
    }

    /**
     * @param  iterable<StudentViolation|array<string, mixed>>  $records
     * @return list<array<string, mixed>>
     */
    public function build(iterable $records): array
    {
        $groups = [];

        foreach ($records as $record) {
            $row = $record instanceof StudentViolation
                ? $this->recordFromModel($record)
                : $record;

            $officer = trim((string) ($row['officer'] ?? ''));
            $date = trim((string) ($row['violation_date'] ?? ''));
            $building = trim((string) ($row['building'] ?? ''));
            $groupKey = mb_strtolower($officer).'|'.$date.'|'.mb_strtolower($building);

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = $this->emptyRow($officer, $date, $building);
            }

            $typeKey = $this->resolveTypeKey((string) ($row['violation_type'] ?? ''));
            $groups[$groupKey][$typeKey] = ($groups[$groupKey][$typeKey] ?? 0) + 1;
        }

        $rows = array_values($groups);
        usort($rows, function (array $a, array $b) {
            $cmp = strcmp((string) ($a['__raw_officer'] ?? ''), (string) ($b['__raw_officer'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }

            $cmp = strcmp((string) $a['violation_date'], (string) $b['violation_date']);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp((string) ($a['__raw_building'] ?? ''), (string) ($b['__raw_building'] ?? ''));
        });

        return array_map(fn (array $row) => $this->presentRow($row), $rows);
    }

    public function resolveTypeKey(string $violationType): string
    {
        $normalized = $this->normalizeLabel($violationType);
        if ($normalized === '') {
            return 'khac';
        }

        $map = $this->typeKeyByNormalizedLabel();
        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        return 'khac';
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyRow(string $officer, string $date, string $building): array
    {
        $row = [
            'id' => md5(mb_strtolower($officer).'|'.$date.'|'.mb_strtolower($building)),
            '__raw_officer' => $officer,
            '__raw_building' => $building,
            'violation_date' => $date,
        ];

        foreach ($this->violationTypeColumns() as $col) {
            $row[$col['key']] = 0;
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function presentRow(array $row): array
    {
        $officer = trim((string) ($row['__raw_officer'] ?? ''));
        $building = trim((string) ($row['__raw_building'] ?? ''));

        $row['officer'] = $officer !== '' ? $officer : '---';
        $row['building'] = $this->resolveCampusFromBuilding($building);
        unset($row['__raw_officer'], $row['__raw_building']);

        return $row;
    }

    private function resolveCampusFromBuilding(string $building): string
    {
        if ($building === '') {
            return '---';
        }

        $note = $this->buildingNoteByKey()[$this->normalizeLabel($building)] ?? '';
        if ($note !== '') {
            return $note;
        }

        return $building;
    }

    /**
     * @return array<string, string>
     */
    private function buildingNoteByKey(): array
    {
        if ($this->buildingNoteByKey !== null) {
            return $this->buildingNoteByKey;
        }

        $map = [];
        BuildingBlock::query()
            ->get(['name', 'code', 'note'])
            ->each(function (BuildingBlock $block) use (&$map) {
                $note = trim((string) $block->note);
                foreach ([$block->name, $block->code] as $key) {
                    $normalized = $this->normalizeLabel((string) $key);
                    if ($normalized === '') {
                        continue;
                    }

                    if (! isset($map[$normalized]) || ($note !== '' && ($map[$normalized] ?? '') === '')) {
                        $map[$normalized] = $note;
                    }
                }
            });

        $this->buildingNoteByKey = $map;

        return $map;
    }

    /**
     * @return array<string, mixed>
     */
    private function recordFromModel(StudentViolation $row): array
    {
        return [
            'officer' => $row->officer,
            'violation_date' => $row->violation_date,
            'building' => $row->building,
            'violation_type' => $row->violation_type,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function typeKeyByNormalizedLabel(): array
    {
        if ($this->typeKeyByNormalizedLabel !== null) {
            return $this->typeKeyByNormalizedLabel;
        }

        $map = [];
        foreach (self::VIOLATION_TYPE_COLUMNS as $col) {
            $aliases = array_merge([$col['label']], $col['aliases']);
            foreach ($aliases as $alias) {
                $map[$this->normalizeLabel($alias)] = $col['key'];
            }
        }

        $recognition = Recognition::query()
            ->whereRaw('LOWER(TRIM(name)) LIKE ?', ['%sinh viên vi phạm%'])
            ->first();

        if ($recognition) {
            IncidentCategory::query()
                ->where('recognition_id', $recognition->id)
                ->orderBy('name')
                ->pluck('name')
                ->each(function (string $name) use (&$map) {
                    $normalized = $this->normalizeLabel($name);
                    if ($normalized === '' || isset($map[$normalized])) {
                        return;
                    }

                    foreach (self::VIOLATION_TYPE_COLUMNS as $col) {
                        foreach (array_merge([$col['label']], $col['aliases']) as $alias) {
                            if ($this->normalizeLabel($alias) === $normalized) {
                                $map[$normalized] = $col['key'];

                                return;
                            }
                        }
                    }
                });
        }

        $this->typeKeyByNormalizedLabel = $map;

        return $map;
    }

    private function normalizeLabel(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return $value;
    }
}
