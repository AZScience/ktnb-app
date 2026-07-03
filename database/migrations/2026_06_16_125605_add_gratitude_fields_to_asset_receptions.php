<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_receptions', function (Blueprint $table) {
            $table->text('evidence')->nullable()->after('content');
            $table->string('giver_unit')->nullable()->after('giver_phone');
            $table->string('witness')->nullable()->after('receiving_staff');
            $table->string('return_staff')->nullable()->after('resolution_date');
            $table->string('receiver_id')->nullable()->after('receiver_name');
            $table->string('receiver_class')->nullable()->after('receiver_id');
            $table->string('receiver_unit')->nullable()->after('receiver_class');
            $table->string('receiver_phone')->nullable()->after('receiver_unit');
            $table->string('return_asset_state')->nullable()->after('receiver_phone');
            $table->text('receiver_feedback')->nullable()->after('return_asset_state');
            $table->string('return_witness')->nullable()->after('receiver_feedback');
            // Gratitude fields
            $table->string('gratitude_number')->nullable()->after('return_witness');
            $table->string('gratitude_gift')->nullable()->after('gratitude_number');
            $table->string('gratitude_date')->nullable()->after('gratitude_gift');
            $table->string('gratitude_staff')->nullable()->after('gratitude_date');
            $table->string('gratitude_status')->nullable()->after('gratitude_staff');
        });
    }

    public function down(): void
    {
        Schema::table('asset_receptions', function (Blueprint $table) {
            $table->dropColumn([
                'evidence', 'giver_unit', 'witness', 'return_staff',
                'receiver_id', 'receiver_class', 'receiver_unit', 'receiver_phone',
                'return_asset_state', 'receiver_feedback', 'return_witness',
                'gratitude_number', 'gratitude_gift', 'gratitude_date',
                'gratitude_staff', 'gratitude_status',
            ]);
        });
    }
};
