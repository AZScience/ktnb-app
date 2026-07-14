<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ProjectBackupService
{
    private string $root;

    /** @var list<string> */
    private array $blockedDirNames;

    /** @var list<string> */
    private array $blockedPathPrefixes;

    public function __construct()
    {
        $config = config('nttu.project_files', []);
        $configuredRoot = (string) ($config['root'] ?? base_path());
        $resolved = realpath($configuredRoot);

        if ($resolved === false || ! is_dir($resolved)) {
            throw new \RuntimeException('Không xác định được thư mục gốc project.');
        }

        $this->root = $resolved;
        $this->blockedDirNames = array_values(array_unique(array_merge(
            $config['blocked_dir_names'] ?? ['vendor', 'node_modules', '.git'],
            ['.svn', '.idea', '.vscode']
        )));
        $this->blockedPathPrefixes = array_values(array_unique(array_merge(
            $config['blocked_path_prefixes'] ?? [
                'storage/framework',
                'storage/logs',
                'bootstrap/cache',
            ],
            ['storage/app/backups']
        )));
    }

    public function rootPath(): string
    {
        return $this->root;
    }

    public function rootLabel(): string
    {
        return basename($this->root);
    }

    public function createBackupZip(string $storagePath): void
    {
        $this->prepareHeavyRuntime();

        $absoluteZip = Storage::disk('local')->path($storagePath);
        File::ensureDirectoryExists(dirname($absoluteZip));

        $zip = new ZipArchive;
        if ($zip->open($absoluteZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Không thể tạo file nén project.');
        }

        $this->addDirectoryToZip($zip, $this->root, '');
        $zip->close();
    }

    public function restoreFromZip(string $storagePath): void
    {
        abort_unless(Storage::disk('local')->exists($storagePath), 404);

        $this->prepareHeavyRuntime();

        $absoluteZip = Storage::disk('local')->path($storagePath);
        $zip = new ZipArchive;
        if ($zip->open($absoluteZip) !== true) {
            throw new \RuntimeException('Không thể mở file backup project.');
        }

        try {
            // Stream từng file — tránh extractTo() cả ZIP lớn (dễ OOM / HTTP 500 trên hosting).
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = str_replace('\\', '/', (string) $zip->getNameIndex($i));
                $name = ltrim($name, '/');
                if ($name === '' || str_ends_with($name, '/')) {
                    continue;
                }
                if (str_contains($name, '..') || $this->shouldExclude($name)) {
                    continue;
                }

                $target = $this->root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $name);
                File::ensureDirectoryExists(dirname($target));

                $stream = $zip->getStream($name);
                if ($stream === false) {
                    continue;
                }

                $out = fopen($target, 'wb');
                if ($out === false) {
                    fclose($stream);
                    throw new \RuntimeException("Không thể ghi file phục hồi: {$name}");
                }

                stream_copy_to_stream($stream, $out);
                fclose($out);
                fclose($stream);
            }
        } finally {
            $zip->close();
        }
    }

    private function prepareHeavyRuntime(): void
    {
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '1024M');
            @ini_set('max_execution_time', '600');
        }
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }
    }

    private function addDirectoryToZip(ZipArchive $zip, string $directory, string $relativePrefix): void
    {
        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $relative = $this->joinRelativePath($relativePrefix, $entry);
            if ($this->shouldExclude($relative)) {
                continue;
            }

            $absolute = $directory.DIRECTORY_SEPARATOR.$entry;
            if (is_dir($absolute)) {
                $this->addDirectoryToZip($zip, $absolute, $relative);

                continue;
            }

            if (! is_file($absolute)) {
                continue;
            }

            $zip->addFile($absolute, str_replace('\\', '/', $relative));
        }
    }

    private function shouldExclude(string $relativePath): bool
    {
        $normalized = $this->normalizeRelativePath($relativePath);
        if ($normalized === '') {
            return false;
        }

        if (preg_match('/(^|\/)\\.env(\.|$)/', $normalized)) {
            return true;
        }

        foreach (explode('/', $normalized) as $segment) {
            if (in_array($segment, $this->blockedDirNames, true)) {
                return true;
            }
        }

        $lower = strtolower($normalized);
        foreach ($this->blockedPathPrefixes as $prefix) {
            $prefix = strtolower(str_replace('\\', '/', $prefix));
            if ($lower === $prefix || str_starts_with($lower, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    private function joinRelativePath(string $prefix, string $segment): string
    {
        $prefix = $this->normalizeRelativePath($prefix);
        $segment = trim(str_replace('\\', '/', $segment), '/');

        return $prefix === '' ? $segment : "{$prefix}/{$segment}";
    }

    private function normalizeRelativePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path), '/');

        return $path === '.' ? '' : $path;
    }
}
