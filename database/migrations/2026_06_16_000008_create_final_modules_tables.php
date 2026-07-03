<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_checkins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('class_id')->nullable();
            $table->string('class_name')->nullable();
            $table->string('class')->nullable();
            $table->string('schedule_date')->nullable();
            $table->string('lecturer')->nullable();
            $table->string('building')->nullable();
            $table->string('room')->nullable();
            $table->string('period')->nullable();
            $table->string('student_count')->nullable();
            $table->string('actual_student_count')->nullable();
            $table->json('photo_urls')->nullable();
            $table->json('location')->nullable();
            $table->string('incident')->nullable();
            $table->text('incident_detail')->nullable();
            $table->boolean('is_notification')->default(false);
            $table->string('status')->default('pending_review');
            $table->string('submitted_by')->nullable();
            $table->timestamps();
        });

        Schema::create('shift_feedbacks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->nullable();
            $table->string('employee_name')->nullable();
            $table->string('shift_date')->nullable();
            $table->text('proof_printed')->nullable();
            $table->text('proof_online')->nullable();
            $table->text('proof_incident')->nullable();
            $table->text('proof_facility')->nullable();
            $table->timestamps();
        });

        Schema::table('asset_receptions', function (Blueprint $table) {
            $table->boolean('is_gratitude')->default(false)->after('return_status');
        });
    }

    public function down(): void
    {
        Schema::table('asset_receptions', function (Blueprint $table) {
            $table->dropColumn('is_gratitude');
        });
        Schema::dropIfExists('shift_feedbacks');
        Schema::dropIfExists('external_checkins');
    }
};
