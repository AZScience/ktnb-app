<?php

namespace App\Http\Controllers;

use App\Services\AiAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiAssistantController extends Controller
{
    public function __construct(private AiAssistantService $assistant) {}

    public function index(): View
    {
        return view('ai.assistant', [
            'pageConfig' => [
                'routes' => [
                    'ask' => route('ai.assistant.ask'),
                ],
            ],
        ]);
    }

    public function ask(Request $request): JsonResponse
    {
        $data = $request->validate([
            'question' => 'required|string|max:4000',
            'search_mode' => 'nullable|in:faq,general',
            'history' => 'nullable|array',
            'history.*.role' => 'required_with:history|in:user,assistant',
            'history.*.content' => 'required_with:history|string|max:8000',
        ]);

        $answer = $this->assistant->ask(
            $data['question'],
            $data['search_mode'] ?? 'faq',
            $data['history'] ?? [],
        );

        return response()->json(['answer' => $answer]);
    }

    public function extractAssetReception(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'image' => 'required|string',
        ]);

        try {
            $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $request->input('image'));
            
            $mime = 'image/jpeg';
            if (preg_match('/^data:(image\/\w+);base64,/', $request->input('image'), $matches)) {
                $mime = $matches[1];
            }

            $result = $this->assistant->extractAssetReception($base64, $mime);
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function extractServiceRequest(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'image' => 'required|string',
        ]);

        try {
            $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $request->input('image'));
            
            $mime = 'image/jpeg';
            if (preg_match('/^data:(image\/\w+);base64,/', $request->input('image'), $matches)) {
                $mime = $matches[1];
            }

            $result = $this->assistant->extractServiceRequest($base64, $mime);
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function extractPetition(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'image' => 'required|string',
        ]);

        try {
            $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $request->input('image'));
            
            $mime = 'image/jpeg';
            if (preg_match('/^data:(image\/\w+);base64,/', $request->input('image'), $matches)) {
                $mime = $matches[1];
            }

            $result = $this->assistant->extractPetition($base64, $mime);
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
