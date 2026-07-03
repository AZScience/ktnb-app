<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_violations', function (Blueprint $table) {
            $table->longText('portrait_photo')->nullable()->change();
            $table->longText('document_photo')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('student_violations', function (Blueprint $table) {
            $table->string('portrait_photo')->nullable()->change();
            $table->string('document_photo')->nullable()->change();
        });
    }
};
