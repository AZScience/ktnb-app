<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ImportLegacyDataCommand extends Command
{
    protected $signature = 'nttu:import-legacy
                            {--fresh : Xóa dữ liệu nghiệp vụ trước khi import}
                            {--monolith= : Đường dẫn file backup JSON tổng (mặc định: backup trong repo cũ)}
                            {--export-dir= : Thư mục firestore-export}';

    protected $description = 'Gộp dữ liệu ứng dụng cũ (firestore-export + backup JSON) và import vào Laravel';

    public function handle(): int
    {
        $repoRoot = dirname(base_path());
        $exportDir = $this->option('export-dir') ?: $repoRoot.DIRECTORY_SEPARATOR.'firestore-export';
        $monolith = $this->option('monolith') ?: $repoRoot.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'api'.DIRECTORY_SEPARATOR.'backup-save'.DIRECTORY_SEPARATOR.'backup_12-06-2026_09-20.json';
        $staging = storage_path('app/legacy-import');

        if (! File::isDirectory($exportDir) && ! File::exists($monolith)) {
            $this->error('Không tìm thấy firestore-export hoặc file backup monolith.');
            $this->line('Export từ app cũ: Cài đặt → Sao lưu & Phục hồi → Xuất JSON, rồi chạy lại lệnh với --monolith=đường/dẫn/file.json');

            return self::FAILURE;
        }

        $this->info('Chuẩn bị dữ liệu import...');
        $this->prepareStaging($staging, $exportDir, $monolith);

        if ($this->option('fresh')) {
            $this->warn('Đang xóa dữ liệu nghiệp vụ hiện tại...');
            $this->truncateImportableTables();
        }

        $this->info('Bắt đầu import vào database...');
        $exit = Artisan::call('nttu:import-firestore', ['path' => $staging], $this->output);

        if ($exit === 0) {
            $this->reportMissingCollections($staging);
        }

        return $exit === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function prepareStaging(string $staging, string $exportDir, string $monolith): void
    {
        if (File::isDirectory($staging)) {
            File::deleteDirectory($staging);
        }
        File::ensureDirectoryExists($staging);

        /** @var array<string, array<string, mixed>> $merged */
        $merged = [];

        if (File::isDirectory($exportDir)) {
            foreach (File::glob($exportDir.'/*.json') ?: [] as $file) {
                $collection = pathinfo($file, PATHINFO_FILENAME);
                $raw = json_decode(File::get($file), true);
                if (! is_array($raw)) {
                    $this->warn("Bỏ qua {$collection}: JSON lỗi");

                    continue;
                }
                $merged[$collection] = array_merge($merged[$collection] ?? [], $this->toDocumentMap($raw));
                $this->line("  + firestore-export/{$collection}.json (".count($merged[$collection]).' bản ghi)');
            }
        }

        if (File::exists($monolith)) {
            $mono = json_decode(File::get($monolith), true);
            if (is_array($mono)) {
                foreach ($mono as $collection => $docs) {
                    if (! is_array($docs)) {
                        continue;
                    }
                    $map = $this->toDocumentMap($docs);
                    $before = count($merged[$collection] ?? []);
                    $merged[$collection] = array_merge($merged[$collection] ?? [], $map);
                    $added = count($merged[$collection]) - $before;
                    $this->line("  + monolith [{$collection}] (+{$added}, tổng ".count($merged[$collection]).')');
                }
            }
        }

        foreach ($merged as $collection => $map) {
            File::put(
                $staging.DIRECTORY_SEPARATOR.$collection.'.json',
                json_encode($map, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );
        }

        $this->info('Đã gộp '.count($merged).' collection vào: '.$staging);
    }

    /** @return array<string, mixed> */
    private function toDocumentMap(array $raw): array
    {
        if ($raw === []) {
            return [];
        }

        $keys = array_keys($raw);
        $isList = $keys === range(0, count($raw) - 1);

        if ($isList) {
            $map = [];
            foreach ($raw as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $id = (string) ($row['id'] ?? '');
                if ($id === '') {
                    continue;
                }
                $map[$id] = $row;
            }

            return $map;
        }

        return $raw;
    }

    private function truncateImportableTables(): void
    {
        $tables = [
            'messages', 'shift_feedbacks', 'online_checkins', 'external_checkins',
            'document_records', 'asset_receptions', 'petitions', 'service_requests',
            'student_violations', 'daily_schedules', 'recognitions', 'incident_categories',
            'employees', 'gifts', 'students', 'lecturers', 'classrooms', 'building_blocks',
            'document_types', 'departments', 'positions', 'roles',
            'polls', 'exams', 'discussion_sections', 'system_parameters',
        ];

        Schema::disableForeignKeyConstraints();
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->line("  truncated {$table}");
            }
        }
        Schema::enableForeignKeyConstraints();
    }

    private function reportMissingCollections(string $staging): void
    {
        $expected = [
            'document_records' => 'Hồ sơ / Tra cứu văn bản',
            'requests' => 'Tiếp nhận yêu cầu',
            'petitions' => 'Tiếp nhận đơn thư',
            'asset-receptions' => 'Nhận trả tài sản',
            'student-violations' => 'Sinh viên vi phạm',
            'lecturers' => 'Giảng viên',
            'students' => 'Sinh viên',
            'gifts' => 'Quà tặng',
        ];

        $missing = [];
        foreach ($expected as $file => $label) {
            $path = $staging.DIRECTORY_SEPARATOR.$file.'.json';
            if (! File::exists($path) || File::size($path) < 5) {
                $missing[] = "{$label} ({$file})";
            }
        }

        if ($missing !== []) {
            $this->newLine();
            $this->warn('Chưa có dữ liệu export cho:');
            foreach ($missing as $line) {
                $this->line('  - '.$line);
            }
            $this->line('Xuất thêm từ app cũ (Cài đặt → Sao lưu) rồi chạy:');
            $this->line('  php artisan nttu:import-firestore /đường/dẫn/document_records.json --only=document_records');
        }
    }
}
