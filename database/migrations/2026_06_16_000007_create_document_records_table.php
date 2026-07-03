<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('doc_code')->nullable();
            $table->string('doc_number')->nullable();
            $table->string('title');
            $table->text('abstract')->nullable();
            $table->string('doc_type')->nullable();
            $table->string('issue_date')->nullable();
            $table->string('received_date')->nullable();
            $table->string('issuing_body')->nullable();
            $table->string('signer')->nullable();
            $table->string('department')->nullable();
            $table->string('assignee')->nullable();
            $table->string('urgency')->default('Thường');
            $table->string('confidentiality')->default('Thường');
            $table->string('status')->default('Mới');
            $table->string('original_file')->nullable();
            $table->text('extracted_text')->nullable();
            $table->text('ai_summary')->nullable();
            $table->json('keywords')->nullable();
            $table->string('file_password')->nullable();
            $table->timestamps();

            $table->index('doc_number');
            $table->index('doc_type');
            $table->index('issue_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_records');
    }
};
