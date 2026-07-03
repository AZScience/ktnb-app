<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_legacy_id')->nullable();
            $table->json('recipient_user_ids');
            $table->json('recipient_legacy_ids')->nullable();
            $table->string('subject');
            $table->longText('body');
            $table->json('attachments')->nullable();
            $table->boolean('is_read')->default(false);
            $table->json('trash_by_user_ids')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();
        });

        Schema::table('daily_schedules', function (Blueprint $table) {
            $table->string('meeting_link')->nullable()->after('evidence');
            $table->string('actual_student_count')->nullable()->after('meeting_link');
            $table->json('attendance_list')->nullable()->after('actual_student_count');
            $table->json('attendance_details')->nullable()->after('attendance_list');
            $table->timestamp('last_seen_at')->nullable()->after('attendance_details');
            $table->timestamp('session_end_at')->nullable()->after('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::table('daily_schedules', function (Blueprint $table) {
            $table->dropColumn([
                'meeting_link', 'actual_student_count', 'attendance_list',
                'attendance_details', 'last_seen_at', 'session_end_at',
            ]);
        });
        Schema::dropIfExists('messages');
    }
};
