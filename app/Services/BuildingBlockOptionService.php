<?php

namespace App\Services;

use App\Models\BuildingBlock;
use Illuminate\Support\Collection;

class BuildingBlockOptionService
{
    /** @var Collection<int, BuildingBlock>|null */
    private ?Collection $blocks = null;

    /** @return list<array{value: string, label: string}> */
    public function options(bool $activeOnly = true): array
    {
        return $this->blocks($activeOnly)
            ->map(fn (BuildingBlock $block) => [
                'value' => $this->storedValue($block),
                'label' => $this->optionLabel($block),
            ])
            ->unique('value')
            ->values()
            ->all();
    }

    /** @return list<string> */
    public function filterValues(bool $activeOnly = true): array
    {
        return collect($this->options($activeOnly))->pluck('value')->values()->all();
    }

    public function storedValue(BuildingBlock $block): string
    {
        $code = trim((string) $block->code);

        return $code !== '' ? $code : (string) $block->name;
    }

    public function optionLabel(BuildingBlock $block): string
    {
        $name = trim((string) $block->name);

        return $name !== '' ? $name : (string) $block->code;
    }

    public function displayLabel(?string $stored): string
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return '---';
        }

        foreach ($this->blocks(false) as $block) {
            if ($this->matchesStoredValue($block, $stored)) {
                return $this->optionLabel($block);
            }
        }

        return $stored;
    }

    /** Nhãn báo cáo: ưu tiên cột Ghi chú (note) của danh mục dãy nhà. */
    public function reportBuildingLabel(?string $stored): string
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return '---';
        }

        foreach ($this->blocks(false) as $block) {
            if (! $this->matchesStoredValue($block, $stored)) {
                continue;
            }

            $note = trim((string) $block->note);

            return $note !== '' ? $note : $this->optionLabel($block);
        }

        return $stored;
    }

    public function normalizeStoredValue(?string $input): ?string
    {
        $input = trim((string) $input);
        if ($input === '') {
            return null;
        }

        foreach ($this->blocks(false) as $block) {
            if ($this->matchesStoredValue($block, $input)) {
                return $this->storedValue($block);
            }
        }

        return $input;
    }

    public function syncMissingFromRecords(): void
    {
        $this->syncServiceRequestBuildingBlocks();
        $this->syncAssetReceptionBuildingBlocks();
    }

    public function syncServiceRequestBuildingBlocks(): void
    {
        \App\Models\ServiceRequest::query()
            ->where(function ($q) {
                $q->whereNull('building_block')->orWhere('building_block', '');
            })
            ->orderBy('id')
            ->each(function (\App\Models\ServiceRequest $request) {
                $inferred = $this->inferFromContent($request->content);
                if ($inferred === null) {
                    return;
                }

                $request->update([
                    'building_block' => $this->normalizeStoredValue($inferred),
                ]);
            });

        $this->normalizeExistingBuildingBlocks(\App\Models\ServiceRequest::class);
    }

    public function syncAssetReceptionBuildingBlocks(): void
    {
        $this->normalizeExistingBuildingBlocks(\App\Models\AssetReception::class);
    }

    /** @param class-string<\Illuminate\Database\Eloquent\Model> $modelClass */
    private function normalizeExistingBuildingBlocks(string $modelClass): void
    {
        $modelClass::query()
            ->whereNotNull('building_block')
            ->where('building_block', '!=', '')
            ->orderBy('id')
            ->each(function ($record) {
                $normalized = $this->normalizeStoredValue($record->building_block);
                if ($normalized !== null && $normalized !== $record->building_block) {
                    $record->update(['building_block' => $normalized]);
                }
            });
    }

    public function inferFromContent(?string $content): ?string
    {
        $content = trim((string) $content);
        if ($content === '' || ! preg_match('/phòng\s+([A-Za-z0-9\.\-]+)/ui', $content, $matches)) {
            return null;
        }

        $token = (string) ($matches[1] ?? '');
        $code = strtok($token, '.') ?: $token;
        if ($code === '') {
            return null;
        }

        $block = $this->blocks(false)->first(function (BuildingBlock $block) use ($code) {
            return strcasecmp((string) $block->code, $code) === 0
                || strcasecmp((string) $block->name, $code) === 0;
        });

        return $block ? $this->storedValue($block) : null;
    }

    private function matchesStoredValue(BuildingBlock $block, string $value): bool
    {
        $normalized = $this->normalizeKey($value);

        foreach ([$block->note, $block->name, $block->code] as $candidate) {
            if ($this->normalizeKey((string) $candidate) === $normalized) {
                return true;
            }
        }

        return $this->normalizeKey($this->storedValue($block)) === $normalized;
    }

    private function normalizeKey(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    /** @return Collection<int, BuildingBlock> */
    private function blocks(bool $activeOnly): Collection
    {
        if ($this->blocks === null) {
            $this->blocks = BuildingBlock::query()
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'note', 'is_inactive']);
        }

        if (! $activeOnly) {
            return $this->blocks;
        }

        return $this->blocks->where('is_inactive', false)->values();
    }
}
