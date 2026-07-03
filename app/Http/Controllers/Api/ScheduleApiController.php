<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailySchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = trim((string) $request->get('class', ''));
        $date = trim((string) $request->get('date', date('d/m/Y')));

        if ($query === '') {
            return $this->ok([]);
        }

        $dateFormats = $this->resolveDateFormats($date);
        $queryUpper = mb_strtoupper($query);
        $queryLower = mb_strtolower($query);
        $meetCode = $this->extractMeetCode($query);

        $items = DailySchedule::query()
            ->whereIn('date', $dateFormats)
            ->where(function ($q) use ($query, $queryUpper, $queryLower, $meetCode) {
                $q->where('class', 'like', "%{$query}%")
                    ->orWhere('class', $query)
                    ->orWhereRaw('UPPER(class) LIKE ?', ["%{$queryUpper}%"])
                    ->orWhere('content', 'like', "%{$query}%")
                    ->orWhereRaw('LOWER(content) LIKE ?', ["%{$queryLower}%"]);

                if ($meetCode !== '') {
                    $q->orWhere('meeting_link', 'like', "%{$meetCode}%");
                } elseif (str_contains($queryLower, 'meet.google.com')) {
                    $q->orWhere('meeting_link', 'like', "%{$queryLower}%");
                }
            })
            ->orderBy('period')
            ->get()
            ->map(fn ($s) => $this->toExtensionFormat($s));

        return $this->ok($items);
    }

    public function update(Request $request, DailySchedule $schedule): JsonResponse
    {
        $data = $request->all();

        if (isset($data['actualStudentCount']) || isset($data['studentCount'])) {
            $schedule->actual_student_count = (string) ($data['actualStudentCount'] ?? $data['studentCount']);
            $schedule->attending_students = (int) ($data['actualStudentCount'] ?? $data['studentCount']);
        }
        if (isset($data['meetingLink'])) {
            $schedule->meeting_link = $data['meetingLink'];
        }
        if (isset($data['attendanceList'])) {
            $schedule->attendance_list = $data['attendanceList'];
        }
        if (isset($data['attendanceDetails'])) {
            $schedule->attendance_details = $data['attendanceDetails'];
        }
        if (isset($data['status'])) {
            $schedule->status = $this->mapStatus($data['status']) ?? $data['status'];
        }
        if (isset($data['lastSeenAt'])) {
            $schedule->last_seen_at = $data['lastSeenAt'];
        }
        if (isset($data['endTime'])) {
            $schedule->session_end_at = $data['endTime'];
        }
        if (isset($data['hostName'])) {
            $schedule->employee = $data['hostName'];
        }

        $schedule->save();

        return $this->ok($this->toExtensionFormat($schedule->fresh()));
    }

    public function show(DailySchedule $schedule): JsonResponse
    {
        return $this->ok($this->toExtensionFormat($schedule));
    }

    private function resolveDateFormats(string $date): array
    {
        $date = trim($date);
        $formats = [$date];

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)) {
            $formats[] = "{$m[3]}/{$m[2]}/{$m[1]}";
            $formats[] = "{$m[3]}-{$m[2]}-{$m[1]}";
        } elseif (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $m)) {
            $formats[] = "{$m[3]}-{$m[2]}-{$m[1]}";
            $formats[] = "{$m[1]}/{$m[2]}/{$m[3]}";
        } elseif (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $date, $m)) {
            $formats[] = "{$m[1]}/{$m[2]}/{$m[3]}";
            $formats[] = "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        return array_values(array_unique($formats));
    }

    private function extractMeetCode(string $value): string
    {
        if (preg_match('/meet\.google\.com\/([a-z]{3}-[a-z]{4}-[a-z]{3})/i', $value, $m)) {
            return strtolower($m[1]);
        }

        return '';
    }

    private function mapStatus(?string $status): ?string
    {
        return match ($status) {
            'teaching' => 'Đang dạy',
            'completed' => 'Hoàn thành',
            default => $status,
        };
    }

    private function toExtensionFormat(DailySchedule $s): array
    {
        return [
            'id' => $s->id,
            '_id' => $s->id,
            'Class' => $s->class,
            'class' => $s->class,
            'Lecturer' => $s->lecturer,
            'lecturer' => $s->lecturer,
            'Period' => $s->period,
            'period' => $s->period,
            'Date' => $s->date,
            'date' => $s->date,
            'Course' => $s->content,
            'content' => $s->content,
            'Subject' => $s->content,
            'Room' => $s->room,
            'room' => $s->room,
            'Building' => $s->building,
            'building' => $s->building,
            'studentCount' => $s->student_count,
            'TotalStudents' => $s->student_count,
            'actualStudentCount' => $s->actual_student_count ?? $s->attending_students,
            'meetingLink' => $s->meeting_link,
            'status' => $s->status,
            'attendanceList' => $s->attendance_list,
            'attendanceDetails' => $s->attendance_details,
        ];
    }

    private function ok(mixed $data): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data])
            ->header('Access-Control-Allow-Origin', '*');
    }
}
