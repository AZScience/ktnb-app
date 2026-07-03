<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_checkins', function (Blueprint $table) {
            $table->string('submitted_by_email')->nullable()->after('submitted_by');
        });
    }

    public function down(): void
    {
        Schema::table('external_checkins', function (Blueprint $table) {
            $table->dropColumn('submitted_by_email');
        });
    }
};
