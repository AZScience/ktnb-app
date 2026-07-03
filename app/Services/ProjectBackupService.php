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

        $extractDir = storage_path('app/backups/project-restore-'.time());
        File::ensureDirectoryExists($extractDir);

        $zip = new ZipArchive;
        if ($zip->open(Storage::disk('local')->path($storagePath)) !== true) {
            throw new \RuntimeException('Không thể mở file backup project.');
        }

        $zip->extractTo($extractDir);
        $zip->close();

        $this->mergeExtractedTree($extractDir, $this->root);
        File::deleteDirectory($extractDir);
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

    private function mergeExtractedTree(string $sourceDir, string $targetRoot): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relative = ltrim(str_replace('\\', '/', substr($item->getPathname(), strlen($sourceDir))), '/');
            if ($relative === '' || $this->shouldExclude($relative)) {
                continue;
            }

            $target = $targetRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
            if ($item->isDir()) {
                File::ensureDirectoryExists($target);

                continue;
            }

            File::ensureDirectoryExists(dirname($target));
            File::copy($item->getPathname(), $target);
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
