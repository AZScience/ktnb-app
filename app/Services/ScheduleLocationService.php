<?php

namespace App\Services;

use App\Models\BuildingBlock;
use App\Models\Classroom;
use Illuminate\Support\Collection;

class ScheduleLocationService
{
  private const SPECIAL_BUILDING_LABELS = [
        'Học trực tuyến' => 'Học trực tuyến',
        'Thi trực tuyến' => 'Thi trực tuyến',
        'THỰC HÀNH NGOÀI' => 'THỰC HÀNH NGOÀI',
        'THN' => 'THỰC HÀNH NGOÀI',
        'Giáo dục quốc phòng' => 'Giáo dục quốc phòng',
    ];

    /** @var Collection<int, BuildingBlock>|null */
    private ?Collection $blocks = null;

    /** @var Collection<int, Classroom>|null */
    private ?Collection $classrooms = null;

    /** @var array<string, string>|null */
    private ?array $classroomNameMap = null;

    public function warmNormalizationCaches(): void
    {
        $this->blocks();
        $this->classrooms();
        $this->classroomNameMap();
    }

    public function buildingDisplayLabel(?string $stored): string
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return '---';
        }

        if (isset(self::SPECIAL_BUILDING_LABELS[$stored])) {
            return self::SPECIAL_BUILDING_LABELS[$stored];
        }

        foreach ($this->blocks() as $block) {
            if ($this->matchesBlock($block, $stored)) {
                return $this->blockLabel($block);
            }
        }

        return $stored;
    }

    public function roomDisplayLabel(?string $stored): string
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return '---';
        }

        return $stored;
    }

    /**
     * Chuẩn hóa cặp building/room khi import hoặc sửa dữ liệu.
     *
     * @return array{building: ?string, room: ?string}
     */
    public function normalizePair(?string $building, ?string $room): array
    {
        $building = $this->nullable($building);
        $room = $this->nullable($room);

        if ($this->looksSwapped($building, $room)) {
            [$building, $room] = [$room, $building];
        }

        if ($building === null && $room !== null) {
            $building = $this->inferBuildingFromRoom($room);
        }

        if ($building !== null) {
            $building = $this->normalizeBuildingForStorage($building) ?? $building;
        }

        if ($room !== null) {
            $room = $this->normalizeRoomForStorage($room, $building) ?? $room;
        }

        return [
            'building' => $building,
            'room' => $room,
        ];
    }

    public function normalizeBuildingForStorage(?string $input): ?string
    {
        $input = trim((string) $input);
        if ($input === '') {
            return null;
        }

        if (isset(self::SPECIAL_BUILDING_LABELS[$input])) {
            return $input === 'THN' ? 'THỰC HÀNH NGOÀI' : $input;
        }

        foreach ($this->blocks() as $block) {
            if ($this->matchesBlock($block, $input)) {
                return $this->blockLabel($block);
            }
        }

        return $input;
    }

    public function normalizeRoomForStorage(?string $room, ?string $building = null): ?string
    {
        $room = trim((string) $room);
        if ($room === '') {
            return null;
        }

        if ($this->looksLikeDepartmentName($room)) {
            return $room;
        }

        $classroom = $this->classroomNameMap()[mb_strtolower($room)] ?? null;

        return $classroom ?? $room;
    }

    public function inferBuildingFromRoom(?string $room): ?string
    {
        $room = trim((string) $room);
        if ($room === '') {
            return null;
        }

        if ($this->looksLikeDepartmentName($room)) {
            return null;
        }

        if (! preg_match('/^([A-Za-z0-9]+)[.\-]/u', $room, $matches)) {
            return null;
        }

        $prefix = (string) ($matches[1] ?? '');
        if ($prefix === '') {
            return null;
        }

        $block = $this->blocks()->first(
            fn (BuildingBlock $item) => strcasecmp((string) $item->code, $prefix) === 0
        );

        return $block ? $this->blockLabel($block) : $prefix;
    }

    public function buildingMatchesFilter(?string $stored, string $filterValue): bool
    {
        $stored = trim((string) $stored);
        $filterValue = trim($filterValue);
        if ($stored === '' || $filterValue === '') {
            return false;
        }

        if (strcasecmp($stored, $filterValue) === 0) {
            return true;
        }

        return strcasecmp($this->buildingDisplayLabel($stored), $filterValue) === 0
            || strcasecmp($this->normalizeBuildingForStorage($filterValue) ?? '', $stored) === 0;
    }

    /** @return list<array{value: string, label: string, code: string}> */
    public function buildingOptions(): array
    {
        $options = [];

        foreach (self::SPECIAL_BUILDING_LABELS as $value => $label) {
            if ($value === 'THN') {
                continue;
            }
            $options[] = ['value' => $value, 'label' => $label, 'code' => $value];
        }

        foreach ($this->blocks() as $block) {
            $label = $this->blockLabel($block);
            $options[] = [
                'value' => $label,
                'label' => $label,
                'code' => (string) $block->code,
            ];
        }

        return collect($options)
            ->unique('value')
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /** @return array<string, string> */
    public function buildingBlockMap(): array
    {
        $map = [];

        foreach ($this->buildingOptions() as $option) {
            $map[$option['value']] = $option['code'];
            $map[$option['label']] = $option['code'];
            $map[$option['code']] = $option['code'];
        }

        foreach ($this->blocks() as $block) {
            $map[(string) $block->id] = (string) $block->code;
        }

        return $map;
    }

    private function looksSwapped(?string $building, ?string $room): bool
    {
        $building = trim((string) $building);
        $room = trim((string) $room);

        if ($building === '' || $room === '') {
            return false;
        }

        $buildingLooksLikeRoom = (bool) preg_match('/^[A-Za-z0-9]+[.\-]/u', $building);
        $roomLooksLikeBuildingCode = ! str_contains($room, '.')
            && ! str_contains($room, '-')
            && mb_strlen($room) <= 12
            && ! $this->looksLikeDepartmentName($room);

        return $buildingLooksLikeRoom && $roomLooksLikeBuildingCode;
    }

    private function looksLikeDepartmentName(string $value): bool
    {
        return (bool) preg_match('/^Khoa\b/ui', trim($value));
    }

    private function matchesBlock(BuildingBlock $block, string $value): bool
    {
        $value = mb_strtolower(trim($value));

        foreach ([$block->code, $block->name, $block->note] as $candidate) {
            if ($value === mb_strtolower(trim((string) $candidate))) {
                return true;
            }
        }

        if (preg_match('/^dãy nhà\s+(.+)$/ui', $value, $matches)) {
            $suffix = mb_strtolower(trim((string) ($matches[1] ?? '')));

            return $suffix !== ''
                && ($suffix === mb_strtolower((string) $block->code)
                    || $suffix === mb_strtolower(trim((string) $block->name)));
        }

        return false;
    }

    private function blockLabel(BuildingBlock $block): string
    {
        $name = trim((string) $block->name);

        return $name !== '' ? $name : (string) $block->code;
    }

    /** @return Collection<int, BuildingBlock> */
    private function blocks(): Collection
    {
        if ($this->blocks === null) {
            $this->blocks = BuildingBlock::query()
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'note']);
        }

        return $this->blocks;
    }

    /** @return Collection<int, Classroom> */
    private function classrooms(): Collection
    {
        if ($this->classrooms === null) {
            $this->classrooms = Classroom::query()
                ->orderBy('name')
                ->get(['id', 'name', 'building_block_id']);
        }

        return $this->classrooms;
    }

    /** @return array<string, string> */
    private function classroomNameMap(): array
    {
        if ($this->classroomNameMap !== null) {
            return $this->classroomNameMap;
        }

        $map = [];
        foreach ($this->classrooms() as $classroom) {
            $name = (string) $classroom->name;
            if ($name !== '') {
                $map[mb_strtolower($name)] = $name;
            }
        }

        return $this->classroomNameMap = $map;
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
