<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discussion_sections', function (Blueprint $table) {
            $table->string('moderator_email')->nullable()->after('author_email');
            $table->string('class_id')->nullable()->after('moderator_email');
        });
    }

    public function down(): void
    {
        Schema::table('discussion_sections', function (Blueprint $table) {
            $table->dropColumn(['moderator_email', 'class_id']);
        });
    }
};
