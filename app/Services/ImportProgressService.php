<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ImportProgressService
{
    private const TTL_SECONDS = 3600;

    /** @return array{id: string, status: string, total: int, processed: int, message: string} */
    public function start(int $total, string $label = 'Import'): array
    {
        $id = (string) Str::uuid();
        $payload = [
            'id' => $id,
            'status' => 'running',
            'label' => $label,
            'total' => max(0, $total),
            'processed' => 0,
            'message' => 'Đang xử lý...',
            'error' => null,
        ];
        Cache::put($this->key($id), $payload, self::TTL_SECONDS);

        return $payload;
    }

    /** @param  array<string, mixed>  $patch */
    public function update(string $id, array $patch): void
    {
        $current = $this->get($id);
        if ($current === null) {
            return;
        }

        Cache::put($this->key($id), array_merge($current, $patch), self::TTL_SECONDS);
    }

    public function finish(string $id, string $message = 'Import thành công.'): void
    {
        $this->update($id, [
            'status' => 'completed',
            'message' => $message,
        ]);
    }

    public function fail(string $id, string $message): void
    {
        $this->update($id, [
            'status' => 'failed',
            'message' => $message,
            'error' => $message,
        ]);
    }

    /** @return array<string, mixed>|null */
    public function get(string $id): ?array
    {
        $payload = Cache::get($this->key($id));

        return is_array($payload) ? $payload : null;
    }

    private function key(string $id): string
    {
        return 'import_progress:'.$id;
    }
}
