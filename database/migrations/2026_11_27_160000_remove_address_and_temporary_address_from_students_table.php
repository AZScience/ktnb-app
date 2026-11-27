<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Chuyển dữ liệu fallback để không bị mất thông tin
        // Nếu permanent_address trống nhưng address có dữ liệu -> copy sang permanent_address
        if (Schema::hasColumn('students', 'address') && Schema::hasColumn('students', 'permanent_address')) {
            DB::table('students')
                ->where(function ($q) {
                    $q->whereNull('permanent_address')->orWhere('permanent_address', '');
                })
                ->whereNotNull('address')
                ->where('address', '!=', '')
                ->update(['permanent_address' => DB::raw('address')]);
        }

        // Nếu contact_address trống nhưng temporary_address có dữ liệu -> copy sang contact_address
        if (Schema::hasColumn('students', 'temporary_address') && Schema::hasColumn('students', 'contact_address')) {
            DB::table('students')
                ->where(function ($q) {
                    $q->whereNull('contact_address')->orWhere('contact_address', '');
                })
                ->whereNotNull('temporary_address')
                ->where('temporary_address', '!=', '')
                ->update(['contact_address' => DB::raw('temporary_address')]);
        }

        // 2. Drop 2 cột address và temporary_address
        Schema::table('students', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('students', 'address')) {
                $columnsToDrop[] = 'address';
            }
            if (Schema::hasColumn('students', 'temporary_address')) {
                $columnsToDrop[] = 'temporary_address';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'address')) {
                $table->string('address')->nullable()->after('permanent_address');
            }
            if (!Schema::hasColumn('students', 'temporary_address')) {
                $table->string('temporary_address')->nullable()->after('contact_address');
            }
        });
    }
};
