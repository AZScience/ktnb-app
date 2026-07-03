<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_schedules', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('date');
            $table->string('building')->nullable();
            $table->string('room')->nullable();
            $table->string('period')->nullable();
            $table->string('type', 10)->nullable();
            $table->string('department')->nullable();
            $table->string('class')->nullable();
            $table->unsignedInteger('student_count')->nullable();
            $table->string('lecturer')->nullable();
            $table->text('content')->nullable();
            $table->string('status')->nullable();
            $table->string('time')->nullable();
            $table->string('proctor1')->nullable();
            $table->string('proctor2')->nullable();
            $table->string('proctor3')->nullable();
            $table->text('note')->nullable();
            $table->string('recognition_date')->nullable();
            $table->string('employee')->nullable();
            $table->unsignedInteger('attending_students')->nullable();
            $table->string('incident')->nullable();
            $table->boolean('is_notification')->default(false);
            $table->text('incident_detail')->nullable();
            $table->text('evidence')->nullable();
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_schedules');
    }
};
