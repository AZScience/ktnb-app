<?php

namespace App\Services;

use App\Models\DailySchedule;
use App\Models\OnlineCheckin;
use Illuminate\Support\Facades\Auth;

class OnlineCheckinReviewService
{
    /** @return array<string, mixed> */
    public function normalize(OnlineCheckin $checkin): array
    {
        $p = $checkin->payload ?? [];

        return [
            'id' => $checkin->id,
            'timestamp' => optional($checkin->server_timestamp)->format('d/m/Y H:i'),
            'timestampRaw' => optional($checkin->server_timestamp)?->toIso8601String(),
            'period' => $p['period'] ?? '',
            'classId' => $p['class'] ?? $p['classId'] ?? '',
            'className' => $p['content'] ?? $p['className'] ?? '',
            'lecturer' => $p['lecturer'] ?? '',
            'studentCount' => $p['studentCount'] ?? $p['totalStudents'] ?? '',
            'actualStudentCount' => $p['actualStudentCount'] ?? $p['attendingStudents'] ?? $p['studentCount'] ?? '',
            'incident' => $p['incident'] ?? '',
            'incidentDetail' => $p['incidentDetail'] ?? '',
            'status' => $p['status'] ?? 'pending_review',
            'source' => $p['type'] ?? $p['source'] ?? 'ex_online',
            'evidence' => $p['evidence'] ?? '',
            'meetingLink' => $p['meetingLink'] ?? $p['meeting_link'] ?? '',
            'scheduleDate' => $p['scheduleDate'] ?? $p['date'] ?? optional($checkin->server_timestamp)?->format('Y-m-d'),
        ];
    }

    public function syncApprovedToSchedule(OnlineCheckin $checkin): bool
    {
        $row = $this->normalize($checkin);
        if (($row['status'] ?? '') !== 'approved') {
            return false;
        }

        $classId = $row['classId'];
        $scheduleDate = $row['scheduleDate'];
        if (! $classId || ! $scheduleDate) {
            return false;
        }

        $schedule = DailySchedule::query()
            ->forModule('online')
            ->where('class', $classId)
            ->where(function ($q) use ($scheduleDate) {
                $q->where('date', $scheduleDate);
                if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $scheduleDate, $m)) {
                    $q->orWhere('date', sprintf('%s/%s/%s', $m[3], $m[2], $m[1]));
                }
            })
            ->first();

        if (! $schedule) {
            return false;
        }

        $note = $row['meetingLink'] ? "Link họp: {$row['meetingLink']}" : ($schedule->note ?? '');

        $schedule->update([
            'recognition_date' => now()->format('Y-m-d'),
            'employee' => Auth::user()?->name ?? 'Hệ thống (Duyệt Online)',
            'actual_student_count' => $row['actualStudentCount'] ?: $schedule->actual_student_count,
            'attending_students' => $row['actualStudentCount'] ?: $schedule->attending_students,
            'incident' => $row['incident'] ?: $schedule->incident,
            'incident_detail' => $row['incidentDetail'] ?: $schedule->incident_detail,
            'evidence' => $row['evidence'] ?: $schedule->evidence,
            'meeting_link' => $row['meetingLink'] ?: $schedule->meeting_link,
            'note' => $note,
        ]);

        return true;
    }
}
