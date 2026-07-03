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
}
