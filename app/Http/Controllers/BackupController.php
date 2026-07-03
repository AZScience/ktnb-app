<?php

namespace App\Http\Controllers;

use App\Services\ProjectBackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class BackupController extends Controller
{
    private array $tables = [
        'users', 'roles', 'positions', 'departments', 'employees', 'lecturers', 'students',
        'building_blocks', 'classrooms', 'gifts', 'recognitions', 'incident_categories',
        'document_types', 'document_records', 'daily_schedules', 'student_violations',
        'service_requests', 'petitions', 'asset_receptions', 'online_checkins',
        'external_checkins', 'messages', 'polls', 'exams', 'discussion_sections',
        'shift_feedbacks', 'system_parameters', 'activity_logs', 'user_settings',
    ];

    public function __construct(
        private ProjectBackupService $projectBackup,
    ) {}

    public function index(Request $request): View
    {
        $tab = $this->normalizeScope($request->query('tab', 'database'));

        return view('settings.backup.index', [
            'tab' => $tab,
            'databaseFiles' => $this->listBackupFiles('database'),
            'projectFiles' => $this->listBackupFiles('project'),
            'projectRootLabel' => $this->projectBackup->rootLabel(),
            'purgeDataTypes' => self::purgeDataTypeOptions(),
        ]);
    }

    /** @return list<array{key: string, label: string}> */
    public static function purgeDataTypeOptions(): array
    {
        return [
            ['key' => 'activity_logs', 'label' => 'Nhật ký truy cập'],
            ['key' => 'daily_schedules', 'label' => 'Lịch học / giám sát'],
            ['key' => 'student_violations', 'label' => 'Vi phạm sinh viên'],
            ['key' => 'service_requests', 'label' => 'Tiếp nhận yêu cầu'],
            ['key' => 'petitions', 'label' => 'Đơn thư / khiếu nại'],
            ['key' => 'asset_receptions', 'label' => 'Tiếp nhận tài sản'],
            ['key' => 'online_checkins', 'label' => 'Ghi nhận lớp online'],
            ['key' => 'external_checkins', 'label' => 'Thực hành ngoài'],
            ['key' => 'messages', 'label' => 'Tin nhắn nội bộ'],
            ['key' => 'shift_feedbacks', 'label' => 'Phản hồi ca trực'],
            ['key' => 'document_records', 'label' => 'Hồ sơ văn bản'],
        ];
    }

    public function export(Request $request): RedirectResponse
    {
        $scope = $this->normalizeScope($request->input('scope', 'database'));

        if ($scope === 'project') {
            $filename = $this->buildProjectBackupFilename();
            $this->projectBackup->createBackupZip("backups/project/{$filename}");

            return redirect()
                ->route('backup.index', ['tab' => 'project'])
                ->with('success', "Đã tạo bản sao lưu project: {$filename}")
                ->with('download_backup', $filename)
                ->with('download_scope', 'project');
        }

        $filename = $this->buildDatabaseBackupFilename();
        $this->createDatabaseBackupZip($filename);

        return redirect()
            ->route('backup.index', ['tab' => 'database'])
            ->with('success', "Đã tạo bản sao lưu CSDL: {$filename}")
            ->with('download_backup', $filename)
            ->with('download_scope', 'database');
    }

    public function download(Request $request, string $filename): StreamedResponse
    {
        $scope = $this->normalizeScope($request->query('scope', 'database'));
        $path = $this->resolveBackupPath($filename, $scope);

        return Storage::disk('local')->download($path, basename($path));
    }

    public function import(Request $request): RedirectResponse
    {
        $scope = $this->normalizeScope($request->input('scope', 'database'));
        $request->validate(['backup' => 'required|file|mimes:zip']);

        $storedName = $scope === 'project'
            ? $this->buildProjectBackupFilename('-import')
            : $this->buildDatabaseBackupFilename('-import');

        $storageDir = $this->storagePrefix($scope);
        if (Storage::disk('local')->exists("{$storageDir}/{$storedName}")) {
            $storedName = $scope === 'project'
                ? $this->buildProjectBackupFilename('-import-'.time())
                : $this->buildDatabaseBackupFilename('-import-'.time());
        }

        Storage::disk('local')->putFileAs($storageDir, $request->file('backup'), $storedName);

        try {
            if ($scope === 'project') {
                $this->projectBackup->restoreFromZip("{$storageDir}/{$storedName}");
            } else {
                $this->restoreDatabaseFromZip("{$storageDir}/{$storedName}");
            }
        } catch (\Throwable $e) {
            return redirect()
                ->route('backup.index', ['tab' => $scope])
                ->with('error', 'Phục hồi thất bại: '.$e->getMessage());
        }

        $label = $scope === 'project' ? 'project' : 'CSDL';

        return redirect()
            ->route('backup.index', ['tab' => $scope])
            ->with('success', "Đã phục hồi {$label} và lưu file {$storedName} trên hệ thống.");
    }

    public function restore(Request $request, string $filename): RedirectResponse|JsonResponse
    {
        $scope = $this->normalizeScope($request->query('scope', $request->input('scope', 'database')));

        try {
            $path = $this->resolveBackupPath($filename, $scope);

            if ($scope === 'project') {
                $this->projectBackup->restoreFromZip($path);
                $message = "Đã phục hồi mã nguồn project từ {$filename}.";
            } else {
                $this->restoreDatabaseFromZip($path);
                $message = "Đã phục hồi CSDL từ {$filename}.";
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return redirect()->route('backup.index', ['tab' => $scope])->with('success', $message);
        } catch (\Throwable $e) {
            $message = 'Phục hồi thất bại: '.$e->getMessage();

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 500);
            }

            return redirect()->route('backup.index', ['tab' => $scope])->with('error', $message);
        }
    }

    public function rename(Request $request, string $filename): RedirectResponse|JsonResponse
    {
        $scope = $this->normalizeScope($request->query('scope', $request->input('scope', 'database')));
        $path = $this->resolveBackupPath($filename, $scope);

        $data = $request->validate([
            'new_name' => 'required|string|max:120',
        ]);

        $newName = $this->normalizeBackupFilename($data['new_name'], $scope);

        if ($newName === basename($path)) {
            $message = 'Tên file không thay đổi.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'filename' => $newName]);
            }

            return redirect()->route('backup.index', ['tab' => $scope])->with('success', $message);
        }

        $storageDir = $this->storagePrefix($scope);
        abort_if(Storage::disk('local')->exists("{$storageDir}/{$newName}"), 422, 'Tên file đã tồn tại.');

        $this->moveBackupFile(basename($path), $newName, $scope);

        $message = "Đã đổi tên thành {$newName}.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'filename' => $newName]);
        }

        return redirect()->route('backup.index', ['tab' => $scope])->with('success', $message);
    }

    public function destroy(Request $request, string $filename): RedirectResponse|JsonResponse
    {
        $scope = $this->normalizeScope($request->query('scope', $request->input('scope', 'database')));
        $this->resolveBackupPath($filename, $scope);
        $this->deleteBackupFile($filename, $scope);

        $message = "Đã xóa {$filename}.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('backup.index', ['tab' => $scope])->with('success', $message);
    }

    public function purgeData(Request $request): RedirectResponse|JsonResponse
    {
        $allowedTypes = array_column(self::purgeDataTypeOptions(), 'key');

        $data = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'types' => 'required|array|min:1',
            'types.*' => 'string|in:'.implode(',', $allowedTypes),
        ]);

        $from = \Carbon\Carbon::parse($data['from_date'])->startOfDay();
        $to = \Carbon\Carbon::parse($data['to_date'])->endOfDay();
        $deletedByType = [];
        $deleted = 0;

        foreach (array_values(array_unique($data['types'])) as $type) {
            $count = $this->purgeDataType($type, $from, $to);
            if ($count > 0) {
                $deletedByType[$type] = $count;
                $deleted += $count;
            }
        }

        $typeLabels = collect(self::purgeDataTypeOptions())->pluck('label', 'key');
        $selectedLabels = collect($data['types'])
            ->map(fn (string $key) => $typeLabels[$key] ?? $key)
            ->unique()
            ->values()
            ->all();

        $message = $deleted > 0
            ? 'Đã xóa '.$deleted.' bản ghi từ '.$from->format('d/m/Y').' đến '.$to->format('d/m/Y').' ('.implode(', ', $selectedLabels).').'
            : 'Không có bản ghi nào trong khoảng ngày đã chọn cho các loại dữ liệu: '.implode(', ', $selectedLabels).'.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'deleted' => $deleted,
                'deletedByType' => $deletedByType,
            ]);
        }

        return redirect()->route('backup.index', ['tab' => 'database'])->with('success', $message);
    }

    private function purgeDataType(string $type, \Carbon\Carbon $from, \Carbon\Carbon $to): int
    {
        if (! $this->tableExists($type)) {
            return 0;
        }

        return match ($type) {
            'activity_logs' => (int) DB::table('activity_logs')
                ->whereBetween('logged_at', [$from, $to])
                ->delete(),
            'daily_schedules' => (int) DB::table('daily_schedules')
                ->whereRaw(
                    "STR_TO_DATE(date, '%d/%m/%Y') BETWEEN ? AND ?",
                    [$from->toDateString(), $to->toDateString()]
                )
                ->delete(),
            default => (int) DB::table($type)
                ->whereBetween('created_at', [$from, $to])
                ->delete(),
        };
    }

    private function normalizeScope(?string $scope): string
    {
        return $scope === 'project' ? 'project' : 'database';
    }

    private function storagePrefix(string $scope): string
    {
        return $scope === 'project' ? 'backups/project' : 'backups';
    }

    private function restoreDatabaseFromZip(string $storagePath): void
    {
        abort_unless(Storage::disk('local')->exists($storagePath), 404);

        $extractDir = storage_path('app/backups/restore-'.time());
        File::ensureDirectoryExists($extractDir);

        $zip = new ZipArchive;
        $zip->open(Storage::disk('local')->path($storagePath));
        $zip->extractTo($extractDir);
        $zip->close();

        Artisan::call('nttu:import-firestore', ['path' => $extractDir]);
        File::deleteDirectory($extractDir);
    }

    private const DATABASE_BACKUP_STEM_PATTERN = '/^(?:backup[_-])?(\d{2}-\d{2}-\d{4}_\d{2}-\d{2}-\d{2})(?:-([\w.-]+))?$/i';

    private const PROJECT_BACKUP_STEM_PATTERN = '/^project_(\d{2}-\d{2}-\d{4}_\d{2}-\d{2}-\d{2})(?:-([\w.-]+))?$/i';

    private function buildDatabaseBackupFilename(string $suffix = ''): string
    {
        $stem = now()->format('d-m-Y_H-i-s');

        return $suffix !== '' ? "{$stem}{$suffix}.zip" : "{$stem}.zip";
    }

    private function buildProjectBackupFilename(string $suffix = ''): string
    {
        $stem = 'project_'.now()->format('d-m-Y_H-i-s');

        return $suffix !== '' ? "{$stem}{$suffix}.zip" : "{$stem}.zip";
    }

    private function normalizeBackupFilename(string $input, string $scope): string
    {
        $name = trim($input);
        $name = preg_replace('/\.zip$/i', '', $name) ?? $name;
        $name = trim($name);

        $pattern = $scope === 'project'
            ? self::PROJECT_BACKUP_STEM_PATTERN
            : self::DATABASE_BACKUP_STEM_PATTERN;

        abort_unless(
            preg_match($pattern, $name, $matches),
            422,
            $scope === 'project'
                ? 'Tên file phải có dạng project_dd-mm-yyyy_hh-mm-ss (có thể thêm hậu tố, ví dụ -import).'
                : 'Tên file phải có dạng dd-mm-yyyy_hh-mm-ss hoặc backup_dd-mm-yyyy_hh-mm-ss (có thể thêm hậu tố, ví dụ -import).'
        );

        $base = $matches[1];
        $suffix = $matches[2] ?? '';

        if ($scope === 'project') {
            return $suffix !== '' ? "project_{$base}-{$suffix}.zip" : "project_{$base}.zip";
        }

        return $suffix !== '' ? "{$base}-{$suffix}.zip" : "{$base}.zip";
    }

    private function resolveBackupPath(string $filename, string $scope): string
    {
        abort_unless($this->isValidBackupFilename($filename, $scope), 404);

        $path = "{$this->storagePrefix($scope)}/{$filename}";

        if ($scope === 'database') {
            abort_unless(
                Storage::disk('local')->exists($path) || File::isFile($this->legacyBackupAbsolutePath($filename)),
                404
            );
        } else {
            abort_unless(Storage::disk('local')->exists($path), 404);
        }

        return $path;
    }

    private function legacyBackupAbsolutePath(string $filename): string
    {
        return storage_path('app/backups/'.$filename);
    }

    private function deleteBackupFile(string $filename, string $scope): void
    {
        $privatePath = "{$this->storagePrefix($scope)}/{$filename}";
        if (Storage::disk('local')->exists($privatePath)) {
            Storage::disk('local')->delete($privatePath);
        }

        if ($scope === 'database') {
            $legacyPath = $this->legacyBackupAbsolutePath($filename);
            if (File::isFile($legacyPath)) {
                File::delete($legacyPath);
            }
        }
    }

    private function moveBackupFile(string $from, string $to, string $scope): void
    {
        $storageDir = $this->storagePrefix($scope);
        $privateFrom = "{$storageDir}/{$from}";
        $privateTo = "{$storageDir}/{$to}";

        if (Storage::disk('local')->exists($privateFrom)) {
            Storage::disk('local')->move($privateFrom, $privateTo);
        }

        if ($scope === 'database') {
            $legacyFrom = $this->legacyBackupAbsolutePath($from);
            $legacyTo = $this->legacyBackupAbsolutePath($to);
            if (File::isFile($legacyFrom)) {
                File::ensureDirectoryExists(dirname($legacyTo));
                File::move($legacyFrom, $legacyTo);
            }
        }
    }

    private function isValidBackupFilename(string $filename, string $scope): bool
    {
        if ($filename === '' || str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            return false;
        }

        if ($scope === 'project') {
            return (bool) preg_match('/^project_\d{2}-\d{2}-\d{4}_\d{2}-\d{2}-\d{2}(-[\w.-]+)?\.zip$/', $filename);
        }

        return (bool) preg_match('/^\d{2}-\d{2}-\d{4}_\d{2}-\d{2}-\d{2}(-[\w.-]+)?\.zip$/', $filename)
            || (bool) preg_match('/^backup[_-][\w\-\.]+\.zip$/i', $filename);
    }

    private function createDatabaseBackupZip(string $filename): void
    {
        $dir = storage_path('app/backups/export-'.now()->format('YmdHis'));
        File::ensureDirectoryExists($dir);

        foreach ($this->tables as $table) {
            if (! $this->tableExists($table)) {
                continue;
            }

            $rows = DB::table($table)->get();
            File::put("{$dir}/{$table}.json", json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $tempZip = storage_path('app/backups/'.uniqid('tmp_', true).'.zip');
        File::ensureDirectoryExists(dirname($tempZip));

        $zip = new ZipArchive;
        $zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach (File::files($dir) as $file) {
            $zip->addFile($file->getPathname(), $file->getFilename());
        }
        $zip->close();
        File::deleteDirectory($dir);

        Storage::disk('local')->put("backups/{$filename}", File::get($tempZip));
        File::delete($tempZip);
    }

    /** @return list<array{name: string, label: string, size: int, date: string, timestamp: int}> */
    private function listBackupFiles(string $scope): array
    {
        if ($scope === 'database') {
            $this->migrateLegacyBackups();
        } else {
            Storage::disk('local')->makeDirectory('backups/project');
        }

        return collect(Storage::disk('local')->files($this->storagePrefix($scope)))
            ->filter(fn ($path) => str_ends_with($path, '.zip'))
            ->map(function (string $path) {
                $name = basename($path);
                $timestamp = Storage::disk('local')->lastModified($path);

                return [
                    'name' => $name,
                    'label' => $this->extractBackupLabel($name),
                    'size' => Storage::disk('local')->size($path),
                    'date' => now()->setTimestamp($timestamp)->format('d/m/Y H:i:s'),
                    'timestamp' => $timestamp,
                ];
            })
            ->sortByDesc('timestamp')
            ->values()
            ->all();
    }

    private function migrateLegacyBackups(): void
    {
        $legacyDir = storage_path('app/backups');
        if (! File::isDirectory($legacyDir)) {
            return;
        }

        foreach (File::files($legacyDir) as $file) {
            if (! str_ends_with($file->getFilename(), '.zip') || str_starts_with($file->getFilename(), 'tmp_')) {
                continue;
            }

            $legacyPath = 'backups/'.$file->getFilename();
            if (Storage::disk('local')->exists($legacyPath)) {
                File::delete($file->getPathname());

                continue;
            }

            Storage::disk('local')->put($legacyPath, File::get($file->getPathname()));
            File::delete($file->getPathname());
        }
    }

    private function extractBackupLabel(string $filename): string
    {
        if (preg_match('/^project_(\d{2}-\d{2}-\d{4}_\d{2}-\d{2}-\d{2})(?:-[\w.-]+)?\.zip$/', $filename, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^(\d{2}-\d{2}-\d{4}_\d{2}-\d{2}-\d{2})(?:-import)?\.zip$/', $filename, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^backup_(\d{2}-\d{2}-\d{4}_\d{2}-\d{2}-\d{2})\.zip$/', $filename, $matches)) {
            return $matches[1];
        }

        return pathinfo($filename, PATHINFO_FILENAME);
    }

    private function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }
}
