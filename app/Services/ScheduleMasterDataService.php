<?php

namespace App\Services;

use App\Models\BuildingBlock;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\Lecturer;
use Illuminate\Support\Facades\Cache;

class ScheduleMasterDataService
{
    public function __construct(
        private ScheduleLocationService $locations,
    ) {}

    /** @return array<string, mixed> */
    public function all(): array
    {
        return Cache::remember('schedule:master-data:v4', 3600, function () {
            $blockMap = [];
            foreach (BuildingBlock::orderBy('name')->get(['id', 'name', 'code', 'note']) as $block) {
                $label = (string) $block->name;
                $blockMap[$label] = (string) $block->id;
                $blockMap[(string) $block->name] = (string) $block->id;
                $blockMap[(string) $block->code] = (string) $block->id;
            }

            return [
                'buildings' => collect($this->locations->buildingOptions())->pluck('label')->values()->all(),
                'blocks' => BuildingBlock::orderBy('name')->get(['id', 'name', 'code'])
                    ->map(fn (BuildingBlock $block) => [
                        'id' => (string) $block->id,
                        'name' => (string) $block->name,
                        'code' => (string) $block->code,
                    ])->values()->all(),
                'blockMap' => $blockMap,
                'departments' => Department::orderBy('name')->pluck('name')
                    ->map(fn ($name) => (string) $name)->values()->all(),
                'rooms' => Classroom::orderBy('name')->get(['id', 'name', 'building_block_id'])
                    ->map(fn (Classroom $room) => [
                        'id' => (string) $room->id,
                        'name' => (string) $room->name,
                        'building_block_id' => $room->building_block_id,
                    ])->values()->all(),
                'lecturers' => Lecturer::orderBy('name')->pluck('name')
                    ->map(fn ($name) => (string) $name)->values()->all(),
            ];
        });
    }

    public static function forget(): void
    {
        Cache::forget('schedule:master-data:v4');
    }
}
