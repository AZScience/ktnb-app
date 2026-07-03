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
        $id = (string) Str::uuid();

        OnlineCheckin::create([
            'id' => $id,
            'payload' => $request->all(),
            'server_timestamp' => now(),
        ]);

        return response()->json(['success' => true, 'id' => $id, 'data' => ['id' => $id]])
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function update(Request $request, string $onlineCheckin): JsonResponse
    {
        $checkin = OnlineCheckin::findOrFail($onlineCheckin);
        $payload = array_merge($checkin->payload ?? [], $request->all());
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
}
