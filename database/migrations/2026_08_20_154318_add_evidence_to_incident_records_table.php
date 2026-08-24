<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_records', function (Blueprint $table) {
            $table->longText('evidence')->nullable()->after('creator_name');
        });
    }

    public function down(): void
    {
        Schema::table('incident_records', function (Blueprint $table) {
            $table->dropColumn('evidence');
        });
    }
};