<?php

namespace App\Http\Controllers;

use App\Models\DailySchedule;
use App\Models\Exam;
use App\Models\OnlineCheckin;
use App\Models\Poll;
use Illuminate\View\View;

class PublicExtensionController extends Controller
{
    public function poll(Poll $poll): View
    {
        return view('public.poll', ['poll' => $poll]);
    }

    public function exam(Exam $exam): View
    {
        return view('public.exam', ['exam' => $exam]);
    }

    public function onlineReport(string $id): View
    {
        $checkin = OnlineCheckin::find($id);
        $schedule = null;

        if ($checkin) {
            $data = $checkin->payload ?? [];
            $data['id'] = $checkin->id;
            $source = 'checkin';
        } else {
            $schedule = DailySchedule::find($id);
            if ($schedule) {
                $data = [
                    'id' => $schedule->id,
                    'class' => $schedule->class,
                    'content' => $schedule->content,
                    'lecturer' => $schedule->lecturer,
                    'period' => $schedule->period,
                    'date' => $schedule->date,
                    'room' => $schedule->room,
                    'studentCount' => $schedule->student_count,
                    'actualStudentCount' => $schedule->actual_student_count ?? $schedule->attending_students,
                    'meetingLink' => $schedule->meeting_link,
                    'status' => $schedule->status,
                    'attendanceList' => $schedule->attendance_list,
                    'attendanceDetails' => $schedule->attendance_details,
                ];
                $source = 'schedule';
            } else {
                abort(404, 'Không tìm thấy báo cáo.');
            }
        }

        return view('public.online-report', compact('data', 'source'));
    }
}
