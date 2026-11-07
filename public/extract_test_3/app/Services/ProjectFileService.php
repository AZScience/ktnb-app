<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProjectFileService
{
    private string $root;

    /** @var list<string> */
    private array $blockedDirNames;

    /** @var list<string> */
    private array $blockedPathPrefixes;

    /** @var list<string> */
    private array $editableExtensions;

    private int $maxEditBytes;

    private int $maxUploadBytes;

    public function __construct()
    {
        $config = config('nttu.project_files', []);
        $configuredRoot = (string) ($config['root'] ?? dirname(base_path()));
        $resolved = realpath($configuredRoot);

        if ($resolved === false || ! is_dir($resolved)) {
            throw new \RuntimeException('Không xác định được thư mục gốc project.');
        }

        $this->root = $resolved;
        $this->blockedDirNames = $config['blocked_dir_names'] ?? ['vendor', 'node_modules', '.git'];
        $this->blockedPathPrefixes = $config['blocked_path_prefixes'] ?? [
            'laravel-app/storage/framework',
            'laravel-app/storage/logs',
            'laravel-app/bootstrap/cache',
        ];
        $this->editableExtensions = $config['editable_extensions'] ?? [
            'php', 'js', 'ts', 'tsx', 'jsx', 'css', 'scss', 'json', 'md', 'txt', 'xml',
            'yml', 'yaml', 'env.example', 'gitignore', 'htaccess', 'blade.php', 'html',
            'vue', 'sql', 'ini', 'conf', 'sh', 'bat', 'ps1', 'svg',
        ];
        $this->maxEditBytes = (int) ($config['max_edit_kb'] ?? 512) * 1024;
        $this->maxUploadBytes = (int) ($config['max_upload_mb'] ?? 50) * 1024 * 1024;
    }

    public function rootPath(): string
    {
        return $this->root;
    }

    /**
     * @return array{
     *     path: string,
     *     breadcrumbs: list<array{label: string, path: string}>,
     *     items: list<array<string, mixed>>
     * }
     */
    public function listDirectory(string $relativePath = ''): array
    {
        $directory = $this->resolveExistingPath($relativePath, mustBeDirectory: true);

        $items = [];
        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $childRelative = $this->joinRelativePath($relativePath, $entry);
            if ($this->isBlockedPath($childRelative, isDir: is_dir($directory.DIRECTORY_SEPARATOR.$entry))) {
                continue;
            }

            $full = $directory.DIRECTORY_SEPARATOR.$entry;
            $isDir = is_dir($full);
            $items[] = [
                'name' => $entry,
                'path' => $childRelative,
                'type' => $isDir ? 'directory' : 'file',
                'size' => $isDir ? null : (is_file($full) ? filesize($full) : 0),
                'size_label' => $isDir ? '—' : $this->formatBytes(is_file($full) ? (int) filesize($full) : 0),
                'modified_at' => date('d/m/Y H:i', (int) filemtime($full)),
                'modified_ts' => (int) filemtime($full),
                'editable' => ! $isDir && $this->isEditableFile($entry, $full),
                'extension' => $isDir ? null : strtolower(pathinfo($entry, PATHINFO_EXTENSION)),
                'is_archive' => ! $isDir && $this->isArchiveFile($entry),
            ];
        }

        usort($items, function (array $a, array $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'directory' ? -1 : 1;
            }

            return strcasecmp($a['name'], $b['name']);
        });

        return [
            'path' => $this->normalizeRelativePath($relativePath),
            'breadcrumbs' => $this->breadcrumbs($relativePath),
            'items' => $items,
        ];
    }

    /**
     * @return array{
     *     path: string,
     *     name: string,
     *     content: string,
     *     editable: bool,
     *     size: int,
     *     size_label: string,
     *     modified_at: string
     * }
     */
    public function readFile(string $relativePath): array
    {
        $file = $this->resolveExistingPath($relativePath, mustBeDirectory: false);
        $name = basename($file);

        if (! $this->isEditableFile($name, $file)) {
            abort(422, 'File này không hỗ trợ chỉnh sửa trực tiếp. Hãy tải xuống để sửa.');
        }

        $size = (int) filesize($file);
        if ($size > $this->maxEditBytes) {
            abort(422, 'File quá lớn để mở trong trình soạn thảo.');
        }

        return [
            'path' => $this->normalizeRelativePath($relativePath),
            'name' => $name,
            'content' => (string) file_get_contents($file),
            'editable' => true,
            'size' => $size,
            'size_label' => $this->formatBytes($size),
            'modified_at' => date('d/m/Y H:i', (int) filemtime($file)),
        ];
    }

    public function writeFile(string $relativePath, string $content): void
    {
        $file = $this->resolveExistingPath($relativePath, mustBeDirectory: false);
        $name = basename($file);

        if (! $this->isEditableFile($name, $file)) {
            abort(422, 'Không thể ghi file này.');
        }

        if (strlen($content) > $this->maxEditBytes) {
            abort(422, 'Nội dung file vượt quá giới hạn cho phép.');
        }

        File::put($file, $content);
    }

    public function createDirectory(string $relativePath, string $name): string
    {
        $parent = $this->resolveExistingPath($relativePath, mustBeDirectory: true);
        $folderName = $this->sanitizeEntryName($name);
        $childRelative = $this->joinRelativePath($relativePath, $folderName);

        if ($this->isBlockedPath($childRelative, isDir: true)) {
            abort(403, 'Không được tạo thư mục tại vị trí này.');
        }

        $target = $parent.DIRECTORY_SEPARATOR.$folderName;
        if (file_exists($target)) {
            abort(422, 'Thư mục đã tồn tại.');
        }

        File::makeDirectory($target, 0755, true);

        return $childRelative;
    }

    public function uploadFile(string $relativePath, UploadedFile $uploaded): string
    {
        if ($uploaded->getSize() > $this->maxUploadBytes) {
            abort(422, 'File upload vượt quá giới hạn dung lượng.');
        }

        $parent = $this->resolveExistingPath($relativePath, mustBeDirectory: true);
        $fileName = $this->sanitizeEntryName($uploaded->getClientOriginalName());
        $childRelative = $this->joinRelativePath($relativePath, $fileName);

        if ($this->isBlockedPath($childRelative, isDir: false)) {
            abort(403, 'Không được upload file tại vị trí này.');
        }

        $target = $parent.DIRECTORY_SEPARATOR.$fileName;
        if (file_exists($target)) {
            abort(422, 'File đã tồn tại. Hãy đổi tên hoặc xóa file cũ.');
        }

        $uploaded->move($parent, $fileName);

        return $childRelative;
    }

    /**
     * @param  list<array{file: UploadedFile, relative_path?: string|null}>  $entries
     * @return array{created: list<string>, skipped: list<string>, errors: list<string>}
     */
    public function uploadBatch(string $relativePath, array $entries): array
    {
        $this->resolveExistingPath($relativePath, mustBeDirectory: true);
        $created = [];
        $skipped = [];
        $errors = [];

        foreach ($entries as $entry) {
            /** @var UploadedFile $uploaded */
            $uploaded = $entry['file'];
            $relative = trim((string) ($entry['relative_path'] ?? $uploaded->getClientOriginalName()));
            $relative = str_replace('\\', '/', $relative);

            try {
                if ($uploaded->getSize() > $this->maxUploadBytes) {
                    $errors[] = "{$relative}: vượt quá giới hạn dung lượng.";
                    continue;
                }

                $segments = array_values(array_filter(explode('/', $relative), fn ($s) => $s !== '' && $s !== '.'));
                if ($segments === []) {
                    $errors[] = 'Tên file không hợp lệ.';
                    continue;
                }

                $fileName = array_pop($segments);
                $fileName = $this->sanitizeEntryName($fileName);
                $dirRelative = $relativePath;
                if ($segments !== []) {
                    $sanitizedSegments = array_map(fn ($s) => $this->sanitizeEntryName($s), $segments);
                    $dirRelative = $this->joinRelativePath($relativePath, implode('/', $sanitizedSegments));
                }

                $childRelative = $this->joinRelativePath($dirRelative, $fileName);
                if ($this->isBlockedPath($childRelative, isDir: false)) {
                    $errors[] = "{$relative}: không được upload tại vị trí này.";
                    continue;
                }

                $targetDir = $this->absolutePath($dirRelative);
                if (! is_dir($targetDir)) {
                    if ($this->isBlockedPath($dirRelative, isDir: true)) {
                        $errors[] = "{$relative}: không được tạo thư mục tại vị trí này.";
                        continue;
                    }
                    File::makeDirectory($targetDir, 0755, true);
                }

                $target = $targetDir.DIRECTORY_SEPARATOR.$fileName;
                if (file_exists($target)) {
                    $skipped[] = $childRelative;
                    continue;
                }

                $uploaded->move($targetDir, $fileName);
                $created[] = $childRelative;
            } catch (\Throwable $e) {
                $errors[] = "{$relative}: {$e->getMessage()}";
            }
        }

        return compact('created', 'skipped', 'errors');
    }

    public function move(string $sourceRelative, string $destinationDirRelative): string
    {
        $source = $this->resolveExistingPath($sourceRelative);
        $isDir = is_dir($source);
        $destDir = $this->resolveExistingPath($destinationDirRelative, mustBeDirectory: true);

        $name = basename($source);
        $targetRelative = $this->joinRelativePath($destinationDirRelative, $name);
        $sourceNorm = $this->normalizeRelativePath($sourceRelative);
        $targetNorm = $this->normalizeRelativePath($targetRelative);
        $destNorm = $this->normalizeRelativePath($destinationDirRelative);

        if ($sourceNorm === $destNorm || $sourceNorm === $targetNorm) {
            abort(422, 'Mục đã nằm trong thư mục đích.');
        }

        if ($isDir && ($targetNorm === $sourceNorm || str_starts_with($targetNorm.'/', $sourceNorm.'/'))) {
            abort(422, 'Không thể di chuyển thư mục vào chính nó hoặc thư mục con.');
        }

        if ($this->isBlockedPath($sourceRelative, isDir: $isDir) || $this->isBlockedPath($targetRelative, isDir: $isDir)) {
            abort(403, 'Không được di chuyển mục này.');
        }

        $target = $destDir.DIRECTORY_SEPARATOR.$name;
        if (file_exists($target)) {
            abort(422, 'Đã tồn tại mục cùng tên trong thư mục đích.');
        }

        rename($source, $target);

        return $targetRelative;
    }

    /**
     * @return array{destination: string, extracted_count: int}
     */
    public function extractArchive(string $archiveRelative, ?string $destinationRelative = null): array
    {
        $archivePath = $this->resolveExistingPath($archiveRelative, mustBeDirectory: false);
        $archiveName = basename($archivePath);

        if (! $this->isArchiveFile($archiveName)) {
            abort(422, 'File không phải định dạng nén được hỗ trợ (zip, tar, tar.gz, tgz).');
        }

        $parentRelative = $this->parentRelativePath($archiveRelative);
        $destRelative = $destinationRelative !== null && $destinationRelative !== ''
            ? $this->sanitizeRelativePath($destinationRelative)
            : $this->joinRelativePath($parentRelative, pathinfo($archiveName, PATHINFO_FILENAME));

        if ($this->isBlockedPath($destRelative, isDir: true)) {
            abort(403, 'Không được giải nén vào vị trí này.');
        }

        $destAbsolute = $this->absolutePath($destRelative);
        if (! is_dir($destAbsolute)) {
            File::makeDirectory($destAbsolute, 0755, true);
        }

        $extracted = $this->performExtraction($archivePath, $destAbsolute);

        return [
            'destination' => $destRelative,
            'extracted_count' => $extracted,
        ];
    }

    public function isArchiveFile(string $fileName): bool
    {
        $lower = strtolower($fileName);
        if (str_ends_with($lower, '.tar.gz') || str_ends_with($lower, '.tgz')) {
            return true;
        }

        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        return in_array($extension, ['zip', 'tar', 'gz'], true);
    }

    private function performExtraction(string $archivePath, string $destination): int
    {
        $lower = strtolower($archivePath);
        if (str_ends_with($lower, '.zip')) {
            return $this->extractZip($archivePath, $destination);
        }

        if (str_ends_with($lower, '.tar.gz') || str_ends_with($lower, '.tgz') || str_ends_with($lower, '.tar')) {
            return $this->extractPhar($archivePath, $destination);
        }

        abort(422, 'Định dạng nén chưa được hỗ trợ.');
    }

    private function extractZip(string $archivePath, string $destination): int
    {
        if (! class_exists(\ZipArchive::class)) {
            abort(500, 'Máy chủ chưa bật extension ZipArchive.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($archivePath) !== true) {
            abort(422, 'Không mở được file zip.');
        }

        $count = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if ($name === '' || str_contains($name, '..')) {
                continue;
            }

            $normalized = trim(str_replace('\\', '/', $name), '/');
            if ($normalized === '') {
                continue;
            }

            $target = $destination.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $normalized);
            if (str_ends_with($name, '/')) {
                File::makeDirectory($target, 0755, true);
                continue;
            }

            File::ensureDirectoryExists(dirname($target));
            $contents = $zip->getFromIndex($i);
            if ($contents === false) {
                continue;
            }
            File::put($target, $contents);
            $count++;
        }

        $zip->close();

        return $count;
    }

    private function extractPhar(string $archivePath, string $destination): int
    {
        if (! class_exists(\PharData::class)) {
            abort(500, 'Máy chủ chưa bật extension Phar.');
        }

        $phar = new \PharData($archivePath);
        $phar->extractTo($destination, null, true);

        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($destination, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $count++;
            }
        }

        return $count;
    }

    private function sanitizeRelativePath(string $path): string
    {
        $segments = array_values(array_filter(
            explode('/', str_replace('\\', '/', trim($path))),
            fn ($segment) => $segment !== '' && $segment !== '.'
        ));

        $clean = [];
        foreach ($segments as $segment) {
            if ($segment === '..') {
                abort(422, 'Đường dẫn không hợp lệ.');
            }
            $clean[] = $this->sanitizeEntryName($segment);
        }

        return implode('/', $clean);
    }

    public function createFile(string $relativePath, string $name, string $content = ''): string
    {
        $parent = $this->resolveExistingPath($relativePath, mustBeDirectory: true);
        $fileName = $this->sanitizeEntryName($name);
        $childRelative = $this->joinRelativePath($relativePath, $fileName);

        if ($this->isBlockedPath($childRelative, isDir: false)) {
            abort(403, 'Không được tạo file tại vị trí này.');
        }

        if (! $this->isEditableFile($fileName)) {
            abort(422, 'Loại file không được phép tạo từ giao diện.');
        }

        $target = $parent.DIRECTORY_SEPARATOR.$fileName;
        if (file_exists($target)) {
            abort(422, 'File đã tồn tại.');
        }

        File::put($target, $content);

        return $childRelative;
    }

    public function rename(string $relativePath, string $newName): string
    {
        $source = $this->resolveExistingPath($relativePath);
        $parentRelative = $this->parentRelativePath($relativePath);
        $sanitized = $this->sanitizeEntryName($newName);
        $targetRelative = $this->joinRelativePath($parentRelative, $sanitized);

        if ($this->isBlockedPath($relativePath, isDir: is_dir($source)) || $this->isBlockedPath($targetRelative, isDir: is_dir($source))) {
            abort(403, 'Không được đổi tên mục này.');
        }

        $target = dirname($source).DIRECTORY_SEPARATOR.$sanitized;
        if (file_exists($target)) {
            abort(422, 'Tên đích đã tồn tại.');
        }

        rename($source, $target);

        return $targetRelative;
    }

    public function delete(string $relativePath): void
    {
        $target = $this->resolveExistingPath($relativePath);
        if ($this->isBlockedPath($relativePath, isDir: is_dir($target))) {
            abort(403, 'Không được xóa mục này.');
        }

        if (is_dir($target)) {
            File::deleteDirectory($target);
        } else {
            File::delete($target);
        }
    }

    /**
     * @param  list<string>  $relativePaths
     * @return array{deleted: list<string>, errors: list<string>}
     */
    public function deleteMany(array $relativePaths): array
    {
        $deleted = [];
        $errors = [];

        foreach ($relativePaths as $relativePath) {
            try {
                $this->delete($relativePath);
                $deleted[] = $this->normalizeRelativePath($relativePath);
            } catch (\Throwable $e) {
                $errors[] = "{$relativePath}: {$e->getMessage()}";
            }
        }

        return compact('deleted', 'errors');
    }

    public function download(string $relativePath): BinaryFileResponse
    {
        $absolute = $this->resolveExistingPath($relativePath);
        if (is_dir($absolute)) {
            abort(422, 'Không thể tải trực tiếp thư mục. Hãy chọn nén hoặc tải hàng loạt.');
        }

        return response()->download($absolute, basename($absolute));
    }

    /**
     * @param  list<string>  $relativePaths
     */
    public function downloadSelection(array $relativePaths): BinaryFileResponse
    {
        $paths = array_values(array_unique(array_map(
            fn ($path) => $this->normalizeRelativePath((string) $path),
            $relativePaths
        )));

        if ($paths === []) {
            abort(422, 'Chưa chọn mục nào để tải.');
        }

        if (count($paths) === 1) {
            $absolute = $this->resolveExistingPath($paths[0]);
            if (is_file($absolute)) {
                return response()->download($absolute, basename($absolute));
            }
        }

        $zipPath = $this->createTemporaryZip($paths, 'download-'.date('Ymd-His').'.zip');

        return response()->download($zipPath, basename($zipPath))->deleteFileAfterSend(true);
    }

    /**
     * @param  list<string>  $relativePaths
     */
    public function createArchive(array $relativePaths, string $parentRelative, string $archiveName): string
    {
        if (! class_exists(\ZipArchive::class)) {
            abort(500, 'Máy chủ chưa bật extension ZipArchive.');
        }

        $paths = array_values(array_unique(array_map(
            fn ($path) => $this->normalizeRelativePath((string) $path),
            $relativePaths
        )));

        if ($paths === []) {
            abort(422, 'Chưa chọn mục nào để nén.');
        }

        $parent = $this->resolveExistingPath($parentRelative, mustBeDirectory: true);
        $fileName = $this->sanitizeArchiveName($archiveName);
        $childRelative = $this->joinRelativePath($parentRelative, $fileName);

        if ($this->isBlockedPath($childRelative, isDir: false)) {
            abort(403, 'Không được tạo file nén tại vị trí này.');
        }

        $target = $parent.DIRECTORY_SEPARATOR.$fileName;
        if (file_exists($target)) {
            abort(422, 'File nén đích đã tồn tại.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($target, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Không tạo được file zip.');
        }

        foreach ($paths as $relativePath) {
            $absolute = $this->resolveExistingPath($relativePath);
            $baseName = basename(str_replace('\\', '/', $relativePath));
            if (is_file($absolute)) {
                $zip->addFile($absolute, $baseName);
                continue;
            }

            $this->addDirectoryToZip($zip, $absolute, $baseName, $relativePath);
        }

        $zip->close();

        return $childRelative;
    }

    /**
     * @param  list<string>  $archivePaths
     * @return array{results: list<array{path: string, destination: string, extracted_count: int}>, errors: list<string>}
     */
    public function extractMany(array $archivePaths): array
    {
        $results = [];
        $errors = [];

        foreach ($archivePaths as $archivePath) {
            try {
                $results[] = array_merge(
                    ['path' => $this->normalizeRelativePath((string) $archivePath)],
                    $this->extractArchive((string) $archivePath)
                );
            } catch (\Throwable $e) {
                $errors[] = "{$archivePath}: {$e->getMessage()}";
            }
        }

        return compact('results', 'errors');
    }

    /**
     * @param  list<string>  $relativePaths
     */
    private function createTemporaryZip(array $relativePaths, string $zipName): string
    {
        if (! class_exists(\ZipArchive::class)) {
            abort(500, 'Máy chủ chưa bật extension ZipArchive.');
        }

        $zipPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.uniqid('nttu-pf-', true).'-'.$zipName;
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Không tạo được file zip tạm.');
        }

        foreach ($relativePaths as $relativePath) {
            $absolute = $this->resolveExistingPath($relativePath);
            $baseName = basename(str_replace('\\', '/', $relativePath));
            if (is_file($absolute)) {
                $zip->addFile($absolute, $baseName);
                continue;
            }

            $this->addDirectoryToZip($zip, $absolute, $baseName, $relativePath);
        }

        $zip->close();

        return $zipPath;
    }

    private function addDirectoryToZip(\ZipArchive $zip, string $directory, string $zipPrefix, string $relativePath): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            $fullPath = $file->getPathname();
            $localPath = ltrim(str_replace('\\', '/', substr($fullPath, strlen($directory))), '/');
            $entryName = $zipPrefix === '' ? $localPath : $zipPrefix.'/'.$localPath;
            $entryRelative = $this->joinRelativePath($relativePath, $localPath);

            if ($this->isBlockedPath($entryRelative, isDir: $file->isDir())) {
                continue;
            }

            if ($file->isDir()) {
                $zip->addEmptyDir(rtrim($entryName, '/'));
                continue;
            }

            $zip->addFile($fullPath, $entryName);
        }
    }

    private function sanitizeArchiveName(string $name): string
    {
        $name = trim(str_replace(['\\', '/'], '', $name));
        if ($name === '') {
            $name = 'archive-'.date('Ymd-His');
        }

        $lower = strtolower($name);
        if (! str_ends_with($lower, '.zip')) {
            $name .= '.zip';
        }

        return $this->sanitizeEntryName($name);
    }

    public function normalizeRelativePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = trim($path, '/');
        if ($path === '' || $path === '.') {
            return '';
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new \InvalidArgumentException('Đường dẫn không hợp lệ.');
            }
        }

        return $path;
    }

    private function resolveExistingPath(string $relativePath, ?bool $mustBeDirectory = null): string
    {
        $relative = $this->normalizeRelativePath($relativePath);
        if ($this->isBlockedPath($relative, isDir: $mustBeDirectory ?? false)) {
            abort(403, 'Không được truy cập đường dẫn này.');
        }

        $absolute = $this->absolutePath($relative);
        if (! file_exists($absolute)) {
            throw new NotFoundHttpException('Không tìm thấy file hoặc thư mục.');
        }

        if ($mustBeDirectory === true && ! is_dir($absolute)) {
            abort(422, 'Đường dẫn không phải thư mục.');
        }

        if ($mustBeDirectory === false && ! is_file($absolute)) {
            abort(422, 'Đường dẫn không phải file.');
        }

        return $absolute;
    }

    private function absolutePath(string $relativePath): string
    {
        $relative = $this->normalizeRelativePath($relativePath);
        $absolute = $relative === ''
            ? $this->root
            : $this->root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

        $parent = $relative === '' ? $this->root : dirname($absolute);
        $parentReal = realpath($parent);
        if ($parentReal === false || ! str_starts_with($parentReal, $this->root)) {
            abort(403, 'Đường dẫn nằm ngoài project.');
        }

        if (file_exists($absolute)) {
            $resolved = realpath($absolute);
            if ($resolved === false || ! str_starts_with($resolved, $this->root)) {
                abort(403, 'Đường dẫn nằm ngoài project.');
            }

            return $resolved;
        }

        return $absolute;
    }

    private function isBlockedPath(string $relativePath, bool $isDir): bool
    {
        $normalized = $this->normalizeRelativePath($relativePath);
        if ($normalized === '') {
            return false;
        }

        $baseName = basename(str_replace('\\', '/', $normalized));
        if (in_array($baseName, ['.env', '.env.backup', '.env.production'], true)) {
            return true;
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

    private function isEditableFile(string $fileName, ?string $absolutePath = null): bool
    {
        if (str_ends_with(strtolower($fileName), '.blade.php')) {
            return true;
        }

        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (! in_array($extension, $this->editableExtensions, true)) {
            return false;
        }

        if ($absolutePath && is_file($absolutePath) && filesize($absolutePath) > $this->maxEditBytes) {
            return false;
        }

        return true;
    }

    private function sanitizeEntryName(string $name): string
    {
        $name = trim(str_replace(['\\', '/'], '', $name));
        if ($name === '' || $name === '.' || $name === '..') {
            abort(422, 'Tên không hợp lệ.');
        }

        return $name;
    }

    private function joinRelativePath(string $parent, string $child): string
    {
        $parent = $this->normalizeRelativePath($parent);

        return $parent === '' ? $child : $parent.'/'.$child;
    }

    private function parentRelativePath(string $relativePath): string
    {
        $normalized = $this->normalizeRelativePath($relativePath);
        if ($normalized === '') {
            return '';
        }

        $parts = explode('/', $normalized);
        array_pop($parts);

        return implode('/', $parts);
    }

    /** @return list<array{label: string, path: string}> */
    private function breadcrumbs(string $relativePath): array
    {
        $crumbs = [['label' => '', 'path' => '']];
        $normalized = $this->normalizeRelativePath($relativePath);
        if ($normalized === '') {
            return $crumbs;
        }

        $parts = explode('/', $normalized);
        $current = '';
        foreach ($parts as $part) {
            $current = $current === '' ? $part : $current.'/'.$part;
            $crumbs[] = ['label' => $part, 'path' => $current];
        }

        return $crumbs;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / (1024 * 1024), 2).' MB';
    }
}
