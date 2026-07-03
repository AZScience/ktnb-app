<?php

namespace App\Services;

use App\Models\DailySchedule;
use App\Models\ExternalCheckin;
use Illuminate\Support\Facades\Auth;

class ExternalCheckinReviewService
{
    public function syncApprovedToSchedule(ExternalCheckin $checkin): bool
    {
        if ($checkin->status !== 'approved') {
            return false;
        }

        $scheduleDate = $this->normalizeScheduleDate($checkin->schedule_date);
        if (! $scheduleDate || ! $checkin->class_id) {
            return false;
        }

        $schedule = DailySchedule::query()
            ->forModule('external-practice')
            ->where('class', $checkin->class_id)
            ->where(function ($q) use ($scheduleDate) {
                $q->where('date', $scheduleDate)
                    ->orWhere('date', $this->toDisplayDate($scheduleDate));
            })
            ->first();

        if (! $schedule) {
            return false;
        }

        $photos = $checkin->photo_urls ?? [];
        $evidence = $photos ? implode('|||', $photos) : $schedule->evidence;
        $lat = $checkin->location['latitude'] ?? '';
        $lng = $checkin->location['longitude'] ?? '';

        $schedule->update([
            'recognition_date' => now()->format('Y-m-d'),
            'employee' => Auth::user()?->name ?? 'Hệ thống (Duyệt Check-in)',
            'incident' => $checkin->incident ?: $schedule->incident,
            'is_notification' => $checkin->is_notification ?? $schedule->is_notification,
            'incident_detail' => $checkin->incident_detail ?: $schedule->incident_detail,
            'actual_student_count' => $checkin->actual_student_count ?: $schedule->actual_student_count,
            'attending_students' => $checkin->actual_student_count ?: $schedule->attending_students,
            'evidence' => $evidence,
            'note' => trim("Đã duyệt từ tọa độ: {$lat}, {$lng} ".($schedule->note ?? '')),
        ]);

        return true;
    }

    private function normalizeScheduleDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date)) {
            return $date;
        }
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        return $date;
    }

    private function toDisplayDate(string $iso): string
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $iso, $m)) {
            return sprintf('%s/%s/%s', $m[3], $m[2], $m[1]);
        }

        return $iso;
    }
}
