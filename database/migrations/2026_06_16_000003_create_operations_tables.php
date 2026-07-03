<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('service_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ticket_number')->nullable();
            $table->string('building_block')->nullable();
            $table->string('student_name');
            $table->string('student_id')->nullable();
            $table->string('class')->nullable();
            $table->string('department')->nullable();
            $table->string('phone')->nullable();
            $table->text('content');
            $table->string('request_date')->nullable();
            $table->string('reception_date')->nullable();
            $table->string('recipient')->nullable();
            $table->boolean('is_processed_immediately')->default(false);
            $table->string('appointment_date')->nullable();
            $table->string('resolution_date')->nullable();
            $table->string('resolver_name')->nullable();
            $table->text('feedback')->nullable();
            $table->string('status')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('petitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reception_date')->nullable();
            $table->string('building_block')->nullable();
            $table->string('recipient')->nullable();
            $table->string('citizen_name');
            $table->string('citizen_id')->nullable();
            $table->text('citizen_address')->nullable();
            $table->string('citizen_phone')->nullable();
            $table->text('summary');
            $table->string('petition_type')->nullable();
            $table->unsignedInteger('number_of_people')->nullable();
            $table->string('previous_authority')->nullable();
            $table->boolean('is_accepted')->default(false);
            $table->boolean('is_returned')->default(false);
            $table->boolean('is_forwarded')->default(false);
            $table->text('resolution_follow_up')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_receptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entry_number')->nullable();
            $table->string('reception_date')->nullable();
            $table->string('building_block')->nullable();
            $table->string('giver_name');
            $table->string('giver_id')->nullable();
            $table->string('giver_class')->nullable();
            $table->string('giver_phone')->nullable();
            $table->text('content');
            $table->string('asset_state')->nullable();
            $table->string('return_status')->default('Chưa trả');
            $table->string('receiving_staff')->nullable();
            $table->string('resolution_date')->nullable();
            $table->string('receiver_name')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_receptions');
        Schema::dropIfExists('petitions');
        Schema::dropIfExists('service_requests');
        Schema::dropIfExists('document_types');
    }
};
