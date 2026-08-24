<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;

class CkeditorUploadController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        abort_unless($request->user(), 401);

        $request->validate([
            'upload' => [
                'required',
                File::types([
                    'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg',
                    'mp4', 'webm',
                    'mp3', 'wav', 'ogg', 'm4a',
                    'pdf',
                ])->max(20480),
            ],
        ]);

        $file = $request->file('upload');
        $path = $file->store('ckeditor/'.date('Y/m'), 'public');
        $url = Storage::disk('public')->url($path);

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = url($url);
        }

        return response()->json([
            'url' => $url,
            'fileName' => $file->getClientOriginalName(),
            'mimeType' => $file->getMimeType() ?: 'application/octet-stream',
        ]);
    }
}
