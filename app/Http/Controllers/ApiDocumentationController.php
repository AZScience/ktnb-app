<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ApiDocumentationController extends Controller
{
    public function index(): View
    {
        $apiBase = rtrim(url('/api/v1'), '/');
        $webBase = rtrim(url('/'), '/');
        $apiKey = (string) config('services.nttu.api_key', '');
        $maskedKey = $this->maskSecret($apiKey);

        return view('tools.api-documentation', [
            'apiBase' => $apiBase,
            'webBase' => $webBase,
            'apiKeyMasked' => $maskedKey,
            'apiKeyConfigured' => $apiKey !== '',
        ]);
    }

    private function maskSecret(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '(chưa cấu hình — xem EXTERNAL_API_KEY trong .env)';
        }
        if (strlen($value) <= 8) {
            return str_repeat('•', strlen($value));
        }

        return substr($value, 0, 4).'••••'.substr($value, -4);
    }
}
