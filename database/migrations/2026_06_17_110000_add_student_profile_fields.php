<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('birth_place')->nullable()->after('birth_date');
            $table->text('permanent_address')->nullable()->after('class');
            $table->text('contact_address')->nullable()->after('permanent_address');
            $table->string('department')->nullable()->after('major');
        });

        DB::table('students')
            ->whereNull('department')
            ->whereNotNull('major')
            ->where('major', '!=', '')
            ->update(['department' => DB::raw('major')]);
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['birth_place', 'permanent_address', 'contact_address', 'department']);
        });
    }
};
