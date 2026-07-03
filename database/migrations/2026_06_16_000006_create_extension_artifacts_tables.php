<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('question');
            $table->json('options');
            $table->unsignedSmallInteger('duration')->default(10);
            $table->json('attendance_list')->nullable();
            $table->string('class_id')->nullable();
            $table->string('lecturer')->nullable();
            $table->json('voters')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('end_time')->nullable();
            $table->timestamps();
        });

        Schema::create('exams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('class_id')->nullable();
            $table->string('course_name')->nullable();
            $table->string('type')->nullable();
            $table->unsignedSmallInteger('duration')->default(15);
            $table->json('questions');
            $table->string('source')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('discussion_sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('student_content')->nullable();
            $table->string('author_email')->nullable();
            $table->string('author_name')->nullable();
            $table->json('comments')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_sections');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('polls');
    }
};
