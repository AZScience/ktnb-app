<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_receptions', function (Blueprint $table) {
            $table->string('giver_employee_code')->nullable()->after('giver_name');
        });
    }

    public function down(): void
    {
        Schema::table('asset_receptions', function (Blueprint $table) {
            $table->dropColumn('giver_employee_code');
        });
    }
};
