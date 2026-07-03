<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->text('note')->nullable();
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('department_id')->index();
            $table->string('name');
            $table->string('head')->nullable();
            $table->string('deputy_head')->nullable();
            $table->string('secretary')->nullable();
            $table->string('spokesperson')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_id')->unique();
            $table->string('name');
            $table->string('nickname')->nullable();
            $table->string('position')->nullable();
            $table->string('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('role_id')->nullable();
            $table->string('email')->unique();
            $table->text('note')->nullable();
            $table->string('avatar_url')->nullable();
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('roles')->nullOnDelete();
        });

        Schema::create('building_blocks', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('code');
            $table->string('name');
            $table->boolean('is_inactive')->default(false);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('classrooms', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('building_block_id');
            $table->unsignedInteger('seating_capacity')->nullable();
            $table->unsignedInteger('table_count')->nullable();
            $table->unsignedInteger('exam_capacity')->nullable();
            $table->string('room_type')->nullable();
            $table->string('subject_nature')->nullable();
            $table->boolean('has_projector')->default(false);
            $table->boolean('is_inactive')->default(false);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('building_block_id')->references('id')->on('building_blocks');
        });

        Schema::create('students', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('avatar_url')->nullable();
            $table->enum('gender', ['Nam', 'Nữ'])->default('Nam');
            $table->string('birth_date')->nullable();
            $table->string('class')->nullable();
            $table->string('major')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('citizen_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('lecturers', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('department')->nullable();
            $table->string('position')->nullable();
            $table->string('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('note')->nullable();
            $table->string('avatar_url')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamp('logged_at');
            $table->string('user_id')->nullable();
            $table->string('user_email')->nullable();
            $table->string('action', 32);
            $table->string('target_type');
            $table->text('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('previous_data')->nullable();
            $table->json('new_data')->nullable();
            $table->timestamps();
        });

        Schema::create('system_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_parameters');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('lecturers');
        Schema::dropIfExists('students');
        Schema::dropIfExists('classrooms');
        Schema::dropIfExists('building_blocks');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('roles');
    }
};
