<?php

namespace App\Console\Commands;

use App\Models\AssetReception;
use App\Models\Employee;
use App\Models\Petition;
use App\Models\ServiceRequest;
use Illuminate\Console\Command;

class NormalizeStaffNicknamesCommand extends Command
{
    protected $signature = 'nttu:normalize-staff-nicknames';

    protected $description = 'Chuẩn hóa trường nhân sự tiếp nhận trong DB sang bí danh';

    public function handle(): int
    {
        Employee::forgetRecipientNicknameLookup();

        $updated = 0;

        ServiceRequest::query()->each(function (ServiceRequest $item) use (&$updated) {
            $nickname = Employee::nicknameFor($item->recipient);
            if ($nickname !== '' && $nickname !== trim((string) $item->recipient)) {
                $item->update(['recipient' => $nickname]);
                $updated++;
            }
        });

        AssetReception::query()->each(function (AssetReception $item) use (&$updated) {
            $changes = [];
            foreach (['receiving_staff', 'return_staff', 'gratitude_staff'] as $field) {
                $nickname = Employee::nicknameFor($item->{$field});
                if ($nickname !== '' && $nickname !== trim((string) $item->{$field})) {
                    $changes[$field] = $nickname;
                }
            }
            if ($changes !== []) {
                $item->update($changes);
                $updated++;
            }
        });

        Petition::query()->each(function (Petition $item) use (&$updated) {
            $nickname = Employee::nicknameFor($item->recipient);
            if ($nickname !== '' && $nickname !== trim((string) $item->recipient)) {
                $item->update(['recipient' => $nickname]);
                $updated++;
            }
        });

        $this->info("Đã chuẩn hóa {$updated} bản ghi sang bí danh.");

        return self::SUCCESS;
    }
}
