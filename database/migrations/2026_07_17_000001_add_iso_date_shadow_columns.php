<?php

use App\Support\DateStringNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, array<string, string>> table => display column => ISO column */
    private array $columns = [
        'daily_schedules' => [
            'date' => 'date_iso',
            'recognition_date' => 'recognition_date_iso',
        ],
        'student_violations' => [
            'violation_date' => 'violation_date_iso',
        ],
        'service_requests' => [
            'request_date' => 'request_date_iso',
            'reception_date' => 'reception_date_iso',
            'appointment_date' => 'appointment_date_iso',
            'resolution_date' => 'resolution_date_iso',
        ],
        'petitions' => [
            'reception_date' => 'reception_date_iso',
        ],
        'asset_receptions' => [
            'reception_date' => 'reception_date_iso',
            'resolution_date' => 'resolution_date_iso',
            'gratitude_date' => 'gratitude_date_iso',
        ],
        'document_records' => [
            'issue_date' => 'issue_date_iso',
            'received_date' => 'received_date_iso',
        ],
        'external_checkins' => [
            'schedule_date' => 'schedule_date_iso',
        ],
        'shift_feedbacks' => [
            'shift_date' => 'shift_date_iso',
        ],
    ];

    public function up(): void
    {
        foreach ($this->columns as $table => $pairs) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $schema) use ($table, $pairs) {
                foreach ($pairs as $isoColumn) {
                    if (! Schema::hasColumn($table, $isoColumn)) {
                        $schema->date($isoColumn)->nullable()->index();
                    }
                }
            });

            $this->backfill($table, $pairs);
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->columns) as $table => $pairs) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $schema) use ($table, $pairs) {
                foreach (array_reverse($pairs) as $isoColumn) {
                    if (Schema::hasColumn($table, $isoColumn)) {
                        $schema->dropColumn($isoColumn);
                    }
                }
            });
        }
    }

    /** @param array<string, string> $pairs */
    private function backfill(string $table, array $pairs): void
    {
        $select = array_merge(['id'], array_keys($pairs));

        DB::table($table)
            ->select($select)
            ->orderBy('id')
            ->chunk(500, function ($rows) use ($table, $pairs) {
                foreach ($rows as $row) {
                    $updates = [];
                    foreach ($pairs as $displayColumn => $isoColumn) {
                        $updates[$isoColumn] = DateStringNormalizer::toIso($row->{$displayColumn} ?? null);
                    }

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update($updates);
                }
            });
    }
};
