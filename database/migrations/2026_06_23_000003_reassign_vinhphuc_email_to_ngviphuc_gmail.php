<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_EMAIL = 'vinhphuc@ntt.edu.vn';

    private const NEW_EMAIL = 'ngviphuc@gmail.com';

    public function up(): void
    {
        $this->reassignEmail('users');
        $this->reassignEmail('employees');
    }

    public function down(): void
    {
        $this->reassignEmail('users', self::NEW_EMAIL, self::OLD_EMAIL);
        $this->reassignEmail('employees', self::NEW_EMAIL, self::OLD_EMAIL);
    }

    private function reassignEmail(string $table, string $from = self::OLD_EMAIL, string $to = self::NEW_EMAIL): void
    {
        if (! DB::getSchemaBuilder()->hasTable($table)) {
            return;
        }

        $hasOld = DB::table($table)->whereRaw('LOWER(email) = ?', [strtolower($from)])->exists();
        if (! $hasOld) {
            return;
        }

        $hasNew = DB::table($table)->whereRaw('LOWER(email) = ?', [strtolower($to)])->exists();
        if ($hasNew) {
            DB::table($table)->whereRaw('LOWER(email) = ?', [strtolower($from)])->delete();

            return;
        }

        DB::table($table)
            ->whereRaw('LOWER(email) = ?', [strtolower($from)])
            ->update(['email' => $to, 'updated_at' => now()]);
    }
};
