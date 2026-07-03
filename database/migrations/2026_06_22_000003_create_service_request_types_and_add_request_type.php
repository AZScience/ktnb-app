<?php

use App\Services\ServiceRequestTypeService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_request_types', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('request_type')->nullable()->after('ticket_number');
        });

        app(ServiceRequestTypeService::class)->seedDefaults();
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn('request_type');
        });

        Schema::dropIfExists('service_request_types');
    }
};
