<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_receptions', function (Blueprint $table) {
            $table->longText('gratitude_evidence')->nullable()->after('gratitude_status');
        });
    }

    public function down(): void
    {
        Schema::table('asset_receptions', function (Blueprint $table) {
            $table->dropColumn('gratitude_evidence');
        });
    }
};
