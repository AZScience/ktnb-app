<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('hometown')->nullable()->after('birth_place');
            $table->string('ethnicity')->nullable()->after('hometown');
            $table->string('religion')->nullable()->after('ethnicity');
            $table->text('temporary_address')->nullable()->after('permanent_address');
            $table->string('region')->nullable()->after('contact_address');
            $table->text('address')->nullable()->after('region');
            $table->string('father_name')->nullable()->after('address');
            $table->string('father_occupation')->nullable()->after('father_name');
            $table->string('mother_name')->nullable()->after('father_occupation');
            $table->string('mother_occupation')->nullable()->after('mother_name');
            $table->string('parent_phone')->nullable()->after('mother_occupation');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'hometown',
                'ethnicity',
                'religion',
                'temporary_address',
                'region',
                'address',
                'father_name',
                'father_occupation',
                'mother_name',
                'mother_occupation',
                'parent_phone',
            ]);
        });
    }
};
