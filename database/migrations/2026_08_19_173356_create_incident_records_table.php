<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_records', function (Blueprint $table) {
            $table->id();
            $table->dateTime('incident_time');
            $table->string('location');
            $table->json('participants');
            $table->text('content');
            $table->dateTime('conclusion_time');
            $table->string('witness_name')->nullable();
            $table->string('creator_name');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_records');
    }
};
