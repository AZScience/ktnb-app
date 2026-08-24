<?php

namespace App\Http\Controllers;

use App\Models\IncidentRecord;
use App\Services\IncidentRecordExportService;
use Illuminate\Http\Request;

class IncidentRecordController extends Controller
{
    public function index(Request $request)
    {
        $records = IncidentRecord::query()->with('recorder')->latest()->get();
        $employeeOptions = \App\Models\Employee::orderBy('name')->get()->map(function ($e) {
            return ['value' => $e->name, 'label' => $e->name];
        })->toArray();
        return view('monitoring.incident-records.index', compact('records', 'employeeOptions'));
    }

    public function show(IncidentRecord $incident_record)
    {
        return view('monitoring.incident-records.show', compact('incident_record'));
    }

    public function store(Request $request)
    {
        $rules = [
            'incident_time' => 'required|date',
            'location' => 'required|string|max:255',
            'participants' => 'required|array|min:2|max:10',
            'participants.0.name' => 'required|string|max:255',
            'participants.0.role' => 'required|string|max:255',
            'participants.1.name' => 'required|string|max:255',
            'participants.1.role' => 'required|string|max:255',
            'participants.*.name' => 'nullable|string|max:255',
            'participants.*.role' => 'nullable|string|max:255',
            'content' => 'required|string',
            'conclusion_time' => 'required|date',
            'witness_name' => 'nullable|string|max:255',
            'witness_signature' => 'nullable|string',
            'creator_signature' => 'nullable|string',
            'creator_name' => 'required|string|max:255',
            'evidence' => 'nullable|string',
        ];

        $messages = [
            'participants.0.name.required' => 'Vui lòng nhập Họ và tên người tham gia dòng 1',
            'participants.0.role.required' => 'Vui lòng nhập Chức vụ người tham gia dòng 1',
            'participants.1.name.required' => 'Vui lòng nhập Họ và tên người tham gia dòng 2',
            'participants.1.role.required' => 'Vui lòng nhập Chức vụ người tham gia dòng 2',
        ];

        $validated = $request->validate($rules, $messages);

        $validated['recorded_by'] = auth()->id();

        $record = IncidentRecord::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã lưu biên bản sự việc.',
                'item' => $record
            ]);
        }

        return redirect()->route('incident-records.index')->with('success', 'Đã lưu biên bản sự việc.');
    }

    public function update(Request $request, IncidentRecord $incident_record)
    {
        $rules = [
            'incident_time' => 'required|date',
            'location' => 'required|string|max:255',
            'participants' => 'required|array|min:2|max:10',
            'participants.0.name' => 'required|string|max:255',
            'participants.0.role' => 'required|string|max:255',
            'participants.1.name' => 'required|string|max:255',
            'participants.1.role' => 'required|string|max:255',
            'participants.*.name' => 'nullable|string|max:255',
            'participants.*.role' => 'nullable|string|max:255',
            'content' => 'required|string',
            'conclusion_time' => 'required|date',
            'witness_name' => 'nullable|string|max:255',
            'witness_signature' => 'nullable|string',
            'creator_signature' => 'nullable|string',
            'creator_name' => 'required|string|max:255',
            'evidence' => 'nullable|string',
        ];

        $messages = [
            'participants.0.name.required' => 'Vui lòng nhập Họ và tên người tham gia dòng 1',
            'participants.0.role.required' => 'Vui lòng nhập Chức vụ người tham gia dòng 1',
            'participants.1.name.required' => 'Vui lòng nhập Họ và tên người tham gia dòng 2',
            'participants.1.role.required' => 'Vui lòng nhập Chức vụ người tham gia dòng 2',
        ];

        $validated = $request->validate($rules, $messages);

        $incident_record->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã cập nhật biên bản.',
                'item' => $incident_record->fresh()
            ]);
        }

        return redirect()->route('incident-records.index')->with('success', 'Đã cập nhật biên bản.');
    }

    public function destroy(Request $request, IncidentRecord $incident_record)
    {
        $incident_record->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đã xóa biên bản.']);
        }

        return redirect()->route('incident-records.index')->with('success', 'Đã xóa biên bản.');
    }

    public function export(IncidentRecord $incident_record, IncidentRecordExportService $exportService)
    {
        return $exportService->download($incident_record);
    }
}

