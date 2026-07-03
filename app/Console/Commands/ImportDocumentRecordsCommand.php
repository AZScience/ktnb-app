<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\ExecutableFinder;

class ImportDocumentRecordsCommand extends Command
{
    protected $signature = 'nttu:import-document-records
                            {--fetch : Tải document_records từ Firebase (cần FIREBASE_EMAIL/PASSWORD trong .env)}
                            {--file= : Import từ file JSON (map hoặc mảng)}
                            {--fresh : Xóa hồ sơ hiện tại trước khi import}';

    protected $description = 'Import hồ sơ văn bản (document_records) từ ứng dụng cũ / Firebase';

    public function handle(): int
    {
        $file = $this->resolveSourceFile();
        if ($file === null) {
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->warn('Đang xóa hồ sơ văn bản hiện tại...');
            \App\Models\DocumentRecord::query()->delete();
        }

        $this->info("Import từ: {$file}");
        $exit = Artisan::call('nttu:import-firestore', [
            'path' => $file,
            '--only' => 'document_records',
        ], $this->output);

        if ($exit === 0) {
            $count = \App\Models\DocumentRecord::count();
            $this->info("Quản lý hồ sơ: {$count} bản ghi trong database.");
        }

        return $exit === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function resolveSourceFile(): ?string
    {
        if ($file = $this->option('file')) {
            if (! File::exists($file)) {
                $this->error("Không tìm thấy file: {$file}");

                return null;
            }

            return $this->extractDocumentRecordsFile($file);
        }

        $default = dirname(base_path()).DIRECTORY_SEPARATOR.'firestore-export'.DIRECTORY_SEPARATOR.'document_records.json';
        if ($this->option('fetch')) {
            return $this->fetchFromFirebase($default) ? $default : null;
        }

        if (File::exists($default) && File::size($default) > 5) {
            return $default;
        }

        $monolith = dirname(base_path()).DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'api'.DIRECTORY_SEPARATOR.'backup-save';
        foreach (File::glob($monolith.DIRECTORY_SEPARATOR.'backup_*.json') ?: [] as $backup) {
            $raw = json_decode(File::get($backup), true);
            if (is_array($raw) && ! empty($raw['document_records'])) {
                $temp = storage_path('app/document_records.json');
                File::put($temp, json_encode($raw['document_records'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                $this->line('Tách document_records từ: '.basename($backup));

                return $temp;
            }
        }

        $this->error('Chưa có dữ liệu document_records.');
        $this->line('Cách 1 — Tải từ Firebase (thêm vào laravel-app/.env):');
        $this->line('  FIREBASE_EMAIL=email_đăng_nhập_app_cũ');
        $this->line('  FIREBASE_PASSWORD=mật_khẩu');
        $this->line('  php artisan nttu:import-document-records --fetch');
        $this->line('Cách 2 — File backup JSON từ app cũ (Cài đặt → Sao lưu):');
        $this->line('  php artisan nttu:import-document-records --file=D:\\đường\\dẫn\\backup.json');

        return null;
    }

    private function fetchFromFirebase(string $outputPath): bool
    {
        $email = config('nttu.firebase.email');
        $password = config('nttu.firebase.password');

        if (! $email || ! $password) {
            $this->error('Thiếu FIREBASE_EMAIL / FIREBASE_PASSWORD trong laravel-app/.env');

            return false;
        }

        $repoRoot = dirname(base_path());
        $script = $repoRoot.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'export-firestore-collection.mjs';
        if (! File::exists($script)) {
            $this->error("Không tìm thấy script export: {$script}");

            return false;
        }

        $node = (new ExecutableFinder)->find('node');
        if (! $node) {
            $this->error('Cần cài Node.js để tải dữ liệu từ Firebase.');

            return false;
        }

        $this->info('Đang tải document_records từ Firebase...');

        $relativeOutput = 'firestore-export/document_records.json';
        $result = Process::path($repoRoot)
            ->timeout(600)
            ->env([
                'FIREBASE_EMAIL' => $email,
                'FIREBASE_PASSWORD' => $password,
                'FIREBASE_API_KEY' => config('nttu.firebase.api_key'),
                'FIREBASE_AUTH_DOMAIN' => config('nttu.firebase.auth_domain'),
                'FIREBASE_PROJECT_ID' => config('nttu.firebase.project_id'),
                'FIRESTORE_COLLECTION' => 'document_records',
                'FIRESTORE_OUTPUT' => $relativeOutput,
            ])
            ->run([$node, $script]);

        if ($result->successful()) {
            $this->line(trim($result->output()));

            return File::exists($outputPath);
        }

        $this->error(trim($result->errorOutput() ?: $result->output()));

        return false;
    }

    private function extractDocumentRecordsFile(string $file): string
    {
        $raw = json_decode(File::get($file), true);
        if (! is_array($raw)) {
            return $file;
        }

        if (array_key_exists('document_records', $raw) && is_array($raw['document_records'])) {
            $temp = storage_path('app/document_records.json');
            File::put($temp, json_encode($raw['document_records'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $this->line('Tách collection document_records từ backup.');

            return $temp;
        }

        return $file;
    }
}
