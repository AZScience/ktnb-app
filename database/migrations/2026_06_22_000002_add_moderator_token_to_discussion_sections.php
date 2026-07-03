<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discussion_sections', function (Blueprint $table) {
            $table->string('moderator_token_hash', 64)->nullable()->after('class_id');
        });
    }

    public function down(): void
    {
        Schema::table('discussion_sections', function (Blueprint $table) {
            $table->dropColumn('moderator_token_hash');
        });
    }
};
