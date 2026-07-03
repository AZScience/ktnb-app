<?php

use App\Models\DocumentRecord;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('document_records', 'created_by_user_id')) {
            return;
        }

        $onlyUserId = User::query()->count() === 1
            ? User::query()->value('id')
            : null;

        if (! $onlyUserId) {
            return;
        }

        DocumentRecord::query()
            ->whereNull('created_by_user_id')
            ->update(['created_by_user_id' => $onlyUserId]);
    }

    public function down(): void
    {
        // No rollback — creator attribution is not reversible safely.
    }
};
