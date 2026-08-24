<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_records', function (Blueprint $table) {
            $table->longText('witness_signature')->nullable();
            $table->longText('creator_signature')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('incident_records', function (Blueprint $table) {
            $table->dropColumn(['witness_signature', 'creator_signature']);
        });
    }
};