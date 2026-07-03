<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EvidenceStorageService
{
    public function storeFiles(array $files): string
    {
        $entries = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $name = $file->getClientOriginalName();
            $path = $file->store('evidence/'.date('Y/m'), 'public');
            $url = Storage::disk('public')->url($path);
            $entries[] = $name.':::'.$url;
        }

        return implode('|', $entries);
    }

    public function mergeEvidence(?string $existing, array $newFiles): string
    {
        $parts = array_filter(explode('|', (string) $existing));

        foreach ($newFiles as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }
            $name = $file->getClientOriginalName();
            $path = $file->store('evidence/'.date('Y/m'), 'public');
            $url = Storage::disk('public')->url($path);
            $parts[] = $name.':::'.$url;
        }

        return implode('|', array_filter($parts));
    }

    public function parse(?string $evidence): array
    {
        if (! $evidence) {
            return [];
        }

        return collect(explode('|', $evidence))
            ->filter()
            ->map(function (string $item) {
                if (str_contains($item, ':::')) {
                    [$name, $url] = explode(':::', $item, 2);

                    return ['name' => $name, 'url' => $url];
                }

                return ['name' => basename($item), 'url' => $item];
            })
            ->values()
            ->all();
    }

    /** Chuyển base64 inline trong chuỗi minh chứng thành URL lưu trên disk. */
    public function normalizeForStorage(?string $evidence): ?string
    {
        if ($evidence === null || $evidence === '') {
            return $evidence;
        }

        $parts = collect(explode('|', $evidence))
            ->filter()
            ->map(function (string $item) {
                if (str_contains($item, ':::')) {
                    [$name, $url] = explode(':::', $item, 2);
                } else {
                    $name = basename(parse_url($item, PHP_URL_PATH) ?: $item);
                    $url = $item;
                }

                if (stripos($url, 'data:') === 0) {
                    $stored = $this->storeDataUrl($url, $name);
                    if ($stored === null) {
                        throw new \RuntimeException('Không thể lưu tệp minh chứng. Kiểm tra quyền ghi thư mục storage.');
                    }

                    return $stored;
                }

                return $name.':::'.$url;
            })
            ->filter()
            ->values()
            ->all();

        return $parts === [] ? null : implode('|', $parts);
    }

    private function storeDataUrl(string $dataUrl, string $name): ?string
    {
        if (! preg_match('/^data:([^;]+);base64,(.+)$/s', $dataUrl, $matches)) {
            return null;
        }

        $binary = base64_decode($matches[2], true);
        if ($binary === false || $binary === '') {
            return null;
        }

        $ext = $this->extensionFromMime($matches[1], $name);
        $safeName = $this->sanitizeFileName($name, $ext);
        $path = 'evidence/'.date('Y/m').'/'.Str::uuid().'.'.$ext;

        Storage::disk('public')->put($path, $binary);

        return $safeName.':::'.Storage::disk('public')->url($path);
    }

    private function extensionFromMime(string $mime, string $name): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/bmp' => 'bmp',
            'video/webm' => 'webm',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/x-msvideo' => 'avi',
            'video/x-matroska' => 'mkv',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'text/plain' => 'txt',
        ];

        if (isset($map[$mime])) {
            return $map[$mime];
        }

        $fromName = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return in_array($fromName, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'webm', 'mp4', 'mov', 'avi', 'mkv', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'], true)
            ? ($fromName === 'jpeg' ? 'jpg' : $fromName)
            : 'bin';
    }

    private function sanitizeFileName(string $name, string $ext): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);
        $base = trim(preg_replace('/[^\pL\pN._-]+/u', '_', $base) ?? '', '_');

        return $base !== '' ? $base.'.'.$ext : 'file_'.$ext;
    }
}
