<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OnlineCheckin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OnlineCheckinController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatedPayload($request);
        $id = (string) Str::uuid();

        OnlineCheckin::create([
            'id' => $id,
            'payload' => $payload,
            'server_timestamp' => now(),
        ]);

        return response()->json(['success' => true, 'id' => $id, 'data' => ['id' => $id]])
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function update(Request $request, string $onlineCheckin): JsonResponse
    {
        $checkin = OnlineCheckin::findOrFail($onlineCheckin);
        $payload = array_merge($checkin->payload ?? [], $this->validatedPayload($request));
        $checkin->update(['payload' => $payload, 'server_timestamp' => now()]);

        return response()->json(['success' => true, 'id' => $checkin->id])
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function show(string $onlineCheckin): JsonResponse
    {
        $checkin = OnlineCheckin::find($onlineCheckin);
        if (! $checkin) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy báo cáo'], 404)
                ->header('Access-Control-Allow-Origin', '*');
        }

        $data = array_merge($checkin->payload ?? [], ['id' => $checkin->id]);

        return response()->json(['success' => true, 'data' => $data])
            ->header('Access-Control-Allow-Origin', '*');
    }

    /** @return array<string, mixed> */
    private function validatedPayload(Request $request): array
    {
        $validated = $request->validate([
            'classId' => ['nullable', 'string', 'max:120'],
            'class' => ['nullable', 'string', 'max:120'],
            'lecturer' => ['nullable', 'string', 'max:200'],
            'content' => ['nullable', 'string', 'max:2000'],
            'meetingLink' => ['nullable', 'string', 'max:500'],
            'period' => ['nullable', 'string', 'max:50'],
            'date' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'max:100'],
            'attendanceList' => ['nullable', 'array', 'max:500'],
            'attendanceDetails' => ['nullable', 'array', 'max:500'],
            'studentCount' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'actualStudentCount' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        $extra = collect($request->all())
            ->except(array_keys($validated))
            ->filter(fn ($value) => is_scalar($value) || is_array($value))
            ->take(30)
            ->all();

        return array_merge($validated, $extra);
    }
}
