<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncidentRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncidentRecordApiController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'incident_time' => 'required|date',
            'location' => 'required|string',
            'creator_name' => 'required|string',
            'witness_name' => 'nullable|string',
            'creator_signature' => 'nullable|string',
            'witness_signature' => 'nullable|string',
            'participants' => 'nullable|array',
            'evidence' => 'nullable|array',
            'content' => 'nullable|string',
        ]);

        $record = IncidentRecord::create($data);

        return response()->json([
            'success' => true,
            'id' => $record->id,
            'data' => $record,
        ])->header('Access-Control-Allow-Origin', '*');
    }

    public function show(string $id): JsonResponse
    {
        $record = IncidentRecord::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $record,
        ])->header('Access-Control-Allow-Origin', '*');
    }
}