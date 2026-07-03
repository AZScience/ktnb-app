<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestType;
use Illuminate\Support\Str;

class ServiceRequestTypeService
{
    public const DEFAULT_TYPES = [
        'Phiếu yêu cầu hỗ trợ',
        'Đơn kiến nghị, phản ánh',
        'Báo mất',
        'Nội dung khác',
        'Vi phạm quy chế học tập',
    ];

    public function ensureType(?string $name): ?string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        $existing = ServiceRequestType::where('name', $name)->first();
        if ($existing) {
            return $existing->name;
        }

        ServiceRequestType::create([
            'id' => $this->nextId($name),
            'name' => $name,
        ]);

        return $name;
    }

    /** @return list<array{value: string, label: string}> */
    public function options(): array
    {
        $this->syncFromRecords();

        $names = ServiceRequestType::orderBy('name')->pluck('name')->all();
        $ordered = [];

        foreach (self::DEFAULT_TYPES as $default) {
            if (in_array($default, $names, true)) {
                $ordered[] = $default;
            }
        }

        foreach ($names as $name) {
            if (! in_array($name, $ordered, true)) {
                $ordered[] = $name;
            }
        }

        return collect($ordered)
            ->map(fn (string $name) => ['value' => $name, 'label' => $name])
            ->values()
            ->all();
    }

    public function seedDefaults(): void
    {
        foreach (self::DEFAULT_TYPES as $name) {
            $this->ensureType($name);
        }
    }

    /** Đồng bộ danh mục và backfill loại yêu cầu cho bản ghi cũ. */
    public function syncFromRecords(): void
    {
        $this->seedDefaults();

        ServiceRequest::query()
            ->whereNotNull('request_type')
            ->where('request_type', '!=', '')
            ->distinct()
            ->pluck('request_type')
            ->each(fn (string $name) => $this->ensureType($name));

        ServiceRequest::query()
            ->where(function ($q) {
                $q->whereNull('request_type')->orWhere('request_type', '');
            })
            ->orderBy('id')
            ->each(function (ServiceRequest $request) {
                $inferred = $this->inferTypeFromContent($request->content);
                $request->update([
                    'request_type' => $this->ensureType($inferred),
                ]);
            });
    }

    private function inferTypeFromContent(?string $content): string
    {
        $text = mb_strtolower(trim((string) $content));

        if ($text === '') {
            return self::DEFAULT_TYPES[0];
        }

        if (str_contains($text, 'phản ánh') || str_contains($text, 'kiến nghị')) {
            return 'Đơn kiến nghị, phản ánh';
        }

        if (str_contains($text, 'vi phạm')) {
            return 'Vi phạm quy chế học tập';
        }

        if (str_contains($text, 'báo mất') || preg_match('/\bmất\b/u', $text)) {
            return 'Báo mất';
        }

        return self::DEFAULT_TYPES[0];
    }

    private function nextId(string $name): string
    {
        $base = Str::slug($name) ?: 'type';
        $id = $base;
        $n = 2;

        while (ServiceRequestType::where('id', $id)->exists()) {
            $id = $base.'-'.$n;
            $n++;
        }

        return $id;
    }
}
