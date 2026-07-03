<?php

namespace App\Http\Controllers;

use App\Services\ImportProgressService;
use Illuminate\Http\JsonResponse;

class ImportStatusController extends Controller
{
    public function show(string $id, ImportProgressService $progress): JsonResponse
    {
        $payload = $progress->get($id);
        if ($payload === null) {
            return response()->json(['message' => 'Không tìm thấy tiến trình import.'], 404);
        }

        return response()->json($payload);
    }
}
