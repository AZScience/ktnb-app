<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\OnlineCheckin;
use Illuminate\View\View;

class MonitoringOverviewController extends Controller
{
    public function onlineCheckins(): View
    {
        return view('monitoring.online-checkins.index', [
            'items' => OnlineCheckin::orderByDesc('created_at')->paginate(30),
        ]);
    }

    public function activityLogs(): View
    {
        return view('settings.activity-logs.index', [
            'items' => ActivityLog::orderByDesc('logged_at')->paginate(30),
        ]);
    }
}
