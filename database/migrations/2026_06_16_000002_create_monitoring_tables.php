<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_violations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name');
            $table->string('class')->nullable();
            $table->string('student_id')->nullable();
            $table->string('violation_date')->nullable();
            $table->string('violation_type')->nullable();
            $table->string('signed')->nullable();
            $table->string('officer')->nullable();
            $table->text('note')->nullable();
            $table->string('building')->nullable();
            $table->string('department')->nullable();
            $table->string('identifier')->nullable();
            $table->longText('signature_base64')->nullable();
            $table->string('portrait_photo')->nullable();
            $table->string('document_photo')->nullable();
            $table->timestamps();
        });

        Schema::create('online_checkins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->json('payload');
            $table->timestamp('server_timestamp')->useCurrent();
            $table->timestamps();
        });

        Schema::create('recognitions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('incident_categories', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('recognition_id');
            $table->string('name');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('gifts', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gifts');
        Schema::dropIfExists('incident_categories');
        Schema::dropIfExists('recognitions');
        Schema::dropIfExists('online_checkins');
        Schema::dropIfExists('student_violations');
    }
};
