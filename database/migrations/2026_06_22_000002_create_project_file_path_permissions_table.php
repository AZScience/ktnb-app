<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_file_path_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('path', 500)->index();
            $table->string('role_id', 64);
            $table->json('permissions');
            $table->timestamps();

            $table->unique(['path', 'role_id']);
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_file_path_permissions');
    }
};
