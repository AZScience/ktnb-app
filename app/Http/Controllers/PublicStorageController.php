<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicStorageController extends Controller
{
    public function evidence(Request $request, string $path): StreamedResponse
    {
        $relative = $this->normalizeEvidencePath($path);
        abort_unless(Storage::disk('public')->exists($relative), 404);

        return Storage::disk('public')->response($relative);
    }

    private function normalizeEvidencePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path, '/'));
        $path = preg_replace('#/+#', '/', $path) ?? '';
        abort_if($path === '' || str_contains($path, '..'), 404);

        return 'evidence/'.$path;
    }
}
