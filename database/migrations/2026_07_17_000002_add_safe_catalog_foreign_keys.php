<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('incident_categories') || ! Schema::hasTable('recognitions')) {
            return;
        }

        Schema::table('incident_categories', function (Blueprint $table) {
            $table->index('recognition_id', 'incident_categories_recognition_id_idx');
        });

        DB::table('incident_categories')
            ->where(function ($query) {
                $query->whereNull('recognition_id')
                    ->orWhereRaw("TRIM(recognition_id) = ''");
            })
            ->update(['recognition_id' => 'legacy-uncategorized-recognition']);

        $orphanIds = DB::table('incident_categories')
            ->leftJoin('recognitions', 'incident_categories.recognition_id', '=', 'recognitions.id')
            ->whereNull('recognitions.id')
            ->distinct()
            ->pluck('incident_categories.recognition_id')
            ->filter()
            ->values();

        foreach ($orphanIds as $recognitionId) {
            DB::table('recognitions')->insertOrIgnore([
                'id' => $recognitionId,
                'name' => $recognitionId === 'legacy-uncategorized-recognition'
                    ? 'Chưa phân loại'
                    : 'Dữ liệu cũ: '.$recognitionId,
                'note' => 'Tự tạo khi thêm ràng buộc FK để giữ dữ liệu Việc phát sinh cũ.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('incident_categories', function (Blueprint $table) {
            $table->foreign('recognition_id', 'incident_categories_recognition_id_fk')
                ->references('id')
                ->on('recognitions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('incident_categories')) {
            return;
        }

        Schema::table('incident_categories', function (Blueprint $table) {
            $table->dropForeign('incident_categories_recognition_id_fk');
            $table->dropIndex('incident_categories_recognition_id_idx');
        });
    }
};
