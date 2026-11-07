<?php

namespace App\Http\Controllers;

use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ManualController extends Controller
{
    private function getManualPath(): string
    {
        $base = base_path('TAI_LIEU_TOAN_DIEN_UNG_DUNG_KTNB.md');
        $pub = public_path('TAI_LIEU_TOAN_DIEN_UNG_DUNG_KTNB.md');

        if (file_exists($pub) && file_exists($base)) {
            return (filemtime($pub) >= filemtime($base)) ? $pub : $base;
        }

        return file_exists($pub) ? $pub : $base;
    }

    private function canUserEdit(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $perms = app(PermissionService::class);

        return $perms->isSuperAdmin()
            || $perms->employee()?->role_id === 'system'
            || $perms->can('system.manage', 'edit');
    }

    public function index()
    {
        $path = $this->getManualPath();
        $markdown = file_exists($path) ? file_get_contents($path) : '# Tài liệu không tồn tại';
        $html = Str::markdown($markdown);
        $updatedAt = file_exists($path) ? date('d/m/Y H:i', filemtime($path)) : date('d/m/Y H:i');
        $canEdit = $this->canUserEdit();

        return view('manual.index', [
            'htmlContent' => $html,
            'rawMarkdown' => $markdown,
            'updatedAt' => $updatedAt,
            'canEdit' => $canEdit,
        ]);
    }

    public function download()
    {
        $path = $this->getManualPath();
        if (file_exists($path)) {
            return response()->download($path, 'TAI_LIEU_TOAN_DIEN_UNG_DUNG_KTNB.md', [
                'Content-Type' => 'text/markdown; charset=UTF-8',
            ]);
        }

        abort(404, 'File tài liệu không tồn tại.');
    }

    public function save(Request $request)
    {
        if (! $this->canUserEdit()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền quản trị để chỉnh sửa tài liệu.',
            ], 403);
        }

        $request->validate([
            'content' => 'required|string',
        ]);

        $content = $request->input('content');

        // Tạo bản sao lưu dự phòng tự động
        $backupDir = storage_path('app/manual_backups');
        if (! is_dir($backupDir)) {
            @mkdir($backupDir, 0755, true);
        }
        $existing = $this->getManualPath();
        if (file_exists($existing)) {
            @copy($existing, $backupDir . '/manual_backup_' . date('Ymd_His') . '.md');
        }

        // Lưu vào cả hai đường dẫn base_path và public_path
        $basePath = base_path('TAI_LIEU_TOAN_DIEN_UNG_DUNG_KTNB.md');
        $publicPath = public_path('TAI_LIEU_TOAN_DIEN_UNG_DUNG_KTNB.md');

        @file_put_contents($basePath, $content);
        @file_put_contents($publicPath, $content);

        return response()->json([
            'success' => true,
            'message' => 'Đã lưu tài liệu hướng dẫn thành công!',
            'updatedAt' => date('d/m/Y H:i'),
            'html' => Str::markdown($content),
        ]);
    }

    public function uploadImage(Request $request)
    {
        if (! $this->canUserEdit()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền tải ảnh lên tài liệu.',
            ], 403);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:20480',
            'old_src' => 'nullable|string',
            'target_name' => 'nullable|string',
        ]);

        $file = $request->file('image');
        $oldSrc = $request->input('old_src');
        $targetName = $request->input('target_name');

        // Xác định tên tệp đích
        if (! empty($targetName)) {
            $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($targetName));
            if (! Str::endsWith(strtolower($filename), ['.png', '.jpg', '.jpeg', '.webp'])) {
                $filename .= '.' . ($file->getClientOriginalExtension() ?: 'png');
            }
        } elseif (! empty($oldSrc)) {
            $cleaned = trim(parse_url($oldSrc, PHP_URL_PATH) ?? '', '/');
            $filename = basename($cleaned);
            if (empty($filename) || $filename === 'screenshots') {
                $filename = 'manual_' . date('Ymd_His') . '.' . ($file->getClientOriginalExtension() ?: 'png');
            }
        } else {
            $ext = $file->getClientOriginalExtension() ?: 'png';
            $filename = 'screenshot_' . date('Ymd_His') . '_' . Str::random(4) . '.' . $ext;
        }

        $destPub = public_path('screenshots/' . $filename);
        $destBase = base_path('screenshots/' . $filename);

        $dirPub = dirname($destPub);
        $dirBase = dirname($destBase);
        if (! is_dir($dirPub)) @mkdir($dirPub, 0755, true);
        if (! is_dir($dirBase)) @mkdir($dirBase, 0755, true);

        // Lưu tệp ảnh vào public và base
        $file->move($dirPub, $filename);
        @copy($destPub, $destBase);

        $newRelativePath = 'screenshots/' . $filename;
        $url = asset($newRelativePath) . '?v=' . time();

        // Nếu oldSrc khác tên mới, tự động cập nhật đường dẫn trong tài liệu Markdown
        if (! empty($oldSrc)) {
            $oldPathClean = trim(parse_url($oldSrc, PHP_URL_PATH) ?? '', '/');
            $manualPath = $this->getManualPath();
            if (file_exists($manualPath)) {
                $md = file_get_contents($manualPath);
                $updatedMd = str_replace([$oldSrc, $oldPathClean], $newRelativePath, $md);
                if ($updatedMd !== $md) {
                    @file_put_contents(base_path('TAI_LIEU_TOAN_DIEN_UNG_DUNG_KTNB.md'), $updatedMd);
                    @file_put_contents(public_path('TAI_LIEU_TOAN_DIEN_UNG_DUNG_KTNB.md'), $updatedMd);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã tải ảnh lên và thay thế thành công!',
            'url' => $url,
            'relative_path' => $newRelativePath,
            'filename' => $filename,
        ]);
    }
}
