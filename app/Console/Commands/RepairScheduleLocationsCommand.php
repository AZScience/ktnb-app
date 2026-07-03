<?php

namespace App\Console\Commands;

use App\Models\DailySchedule;
use App\Services\ScheduleLocationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RepairScheduleLocationsCommand extends Command
{
    protected $signature = 'nttu:repair-schedule-locations
                            {--from-firestore= : Đường dẫn schedules.json để khôi phục building/room theo id}
                            {--dry-run : Chỉ xem trước, không ghi DB}';

    protected $description = 'Chuẩn hóa lại cột Dãy nhà/Phòng trong daily_schedules và khôi phục nhãn đặc biệt (online, THN...)';

    public function handle(ScheduleLocationService $locations): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;
        $firestorePath = $this->option('from-firestore');

        if ($firestorePath !== null && $firestorePath !== '') {
            if (! File::isFile($firestorePath)) {
                $this->error('Không tìm thấy file Firestore export: '.$firestorePath);

                return self::FAILURE;
            }

            $this->info('Khôi phục từ Firestore export: '.$firestorePath);
            $rows = json_decode((string) File::get($firestorePath), true);
            if (! is_array($rows)) {
                $this->error('Không đọc được file Firestore export.');

                return self::FAILURE;
            }

            foreach ($rows as $row) {
                if (! is_array($row) || empty($row['id'])) {
                    continue;
                }

                $schedule = DailySchedule::query()->find((string) $row['id']);
                if (! $schedule) {
                    continue;
                }

                $pair = $locations->normalizePair(
                    isset($row['building']) ? (string) $row['building'] : $schedule->building,
                    isset($row['room']) ? (string) $row['room'] : $schedule->room,
                );

                if ($schedule->building === $pair['building'] && $schedule->room === $pair['room']) {
                    continue;
                }

                if ($dryRun) {
                    $this->line("{$schedule->id}: building [{$schedule->building}] -> [{$pair['building']}], room [{$schedule->room}] -> [{$pair['room']}]");
                } else {
                    $schedule->update([
                        'building' => $pair['building'],
                        'room' => $pair['room'],
                    ]);
                }

                $updated++;
            }
        } else {
            $this->info('Chuẩn hóa dữ liệu hiện có trong DB.');

            DailySchedule::query()->orderBy('id')->chunkById(200, function ($chunk) use ($locations, $dryRun, &$updated) {
                foreach ($chunk as $schedule) {
                    $pair = $locations->normalizePair($schedule->building, $schedule->room);
                    if ($schedule->building === $pair['building'] && $schedule->room === $pair['room']) {
                        continue;
                    }

                    if ($dryRun) {
                        $this->line("{$schedule->id}: building [{$schedule->building}] -> [{$pair['building']}], room [{$schedule->room}] -> [{$pair['room']}]");
                    } else {
                        $schedule->update([
                            'building' => $pair['building'],
                            'room' => $pair['room'],
                        ]);
                    }

                    $updated++;
                }
            }, 'id');
        }

        $this->info(($dryRun ? 'Sẽ cập nhật' : 'Đã cập nhật')." {$updated} bản ghi.");

        return self::SUCCESS;
    }
}
