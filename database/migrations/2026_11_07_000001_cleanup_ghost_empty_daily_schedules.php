<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('daily_schedules')) {
            return;
        }

        // Xóa các dòng rác do import file Excel kéo dài ngày mà không có dữ liệu môn/lớp/phòng/GV
        DB::table('daily_schedules')
            ->where(function ($q) {
                $q->whereNull('class')->orWhere('class', '');
            })
            ->where(function ($q) {
                $q->whereNull('content')->orWhere('content', '');
            })
            ->where(function ($q) {
                $q->whereNull('room')->orWhere('room', '');
            })
            ->where(function ($q) {
                $q->whereNull('lecturer')->orWhere('lecturer', '');
            })
            ->where(function ($q) {
                $q->whereNull('period')->orWhere('period', '');
            })
            ->delete();
    }

    public function down(): void
    {
        // Không khôi phục các dòng rác
    }
};
