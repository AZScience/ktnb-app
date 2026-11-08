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

        $user = auth()->user();
        if ($user && ($user->is_super_admin || $user->role === 'super_admin')) {
            return true;
        }

        try {
            return app(PermissionService::class)->isSuperAdmin($user)
                || app(PermissionService::class)->can('/settings', 'access');
        } catch (\Throwable $e) {
            return false;
        }
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

        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        if (! in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'])) {
            $ext = 'png';
        }

        // Xác định tên tệp đích: Luôn tạo tên tệp có timestamp + random hash để triệt tiêu 100% cache trình duyệt
        if (! empty($targetName)) {
            $cleanTarget = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($targetName, PATHINFO_FILENAME));
            $filename = $cleanTarget . '_' . date('Ymd_His') . '_' . Str::random(4) . '.' . $ext;
        } elseif (! empty($oldSrc)) {
            $cleaned = trim(parse_url($oldSrc, PHP_URL_PATH) ?? '', '/');
            $oldBase = basename($cleaned);
            $nameWithoutExt = pathinfo($oldBase, PATHINFO_FILENAME);
            // Loại bỏ các hậu tố timestamp cũ (vd: _20261107_164500 hoặc _20261107_164500_abcd) để giữ tên gốc ngắn gọn
            $cleanBase = preg_replace('/_\d{8}_\d{4,6}(_[a-zA-Z0-9]{2,6})?$/', '', $nameWithoutExt);
            $cleanBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $cleanBase);
            $cleanBase = trim($cleanBase, '_');
            if (empty($cleanBase) || $cleanBase === 'screenshots') {
                $cleanBase = 'screenshot';
            }
            $filename = $cleanBase . '_' . date('Ymd_His') . '_' . Str::random(4) . '.' . $ext;
        } else {
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

        // Tự động cập nhật đường dẫn trong tài liệu Markdown và dọn dẹp ảnh cũ
        if (! empty($oldSrc)) {
            $oldPathClean = trim(parse_url($oldSrc, PHP_URL_PATH) ?? '', '/');
            $oldDecoded = urldecode($oldPathClean);
            $oldRawDecoded = rawurldecode($oldPathClean);
            $manualPath = $this->getManualPath();
            if (file_exists($manualPath)) {
                $md = file_get_contents($manualPath);
                $searchList = array_unique(array_filter([
                    $oldSrc,
                    $oldPathClean,
                    '/' . $oldPathClean,
                    $oldDecoded,
                    '/' . $oldDecoded,
                    $oldRawDecoded,
                    '/' . $oldRawDecoded,
                ]));
                $updatedMd = str_replace($searchList, $newRelativePath, $md);
                if ($updatedMd !== $md) {
                    @file_put_contents(base_path('TAI_LIEU_TOAN_DIEN_UNG_DUNG_KTNB.md'), $updatedMd);
                    @file_put_contents(public_path('TAI_LIEU_TOAN_DIEN_UNG_DUNG_KTNB.md'), $updatedMd);
                }
            }

            // Dọn dẹp tệp ảnh cũ do các lần tải trước sinh ra để không chiếm dung lượng ổ cứng
            if (preg_match('/_\d{8}_\d{4,6}/', basename($oldPathClean))) {
                $oldPub = public_path($oldPathClean);
                $oldBase = base_path($oldPathClean);
                if (file_exists($oldPub)) @unlink($oldPub);
                if (file_exists($oldBase)) @unlink($oldBase);
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

    public function uploadVideo(Request $request)
    {
        if (! $this->canUserEdit()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền tải video lên tài liệu.',
            ], 403);
        }

        $request->validate([
            'video' => 'required|file|mimes:mp4,webm,ogg,mov,m4v,mkv|max:102400',
            'old_src' => 'nullable|string',
        ]);

        $file = $request->file('video');
        $oldSrc = $request->input('old_src');

        $ext = strtolower($file->getClientOriginalExtension() ?: 'mp4');
        if (! in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'm4v'])) {
            $ext = 'mp4';
        }

        $filename = 'manual_video_' . date('Ymd_His') . '_' . Str::random(5) . '.' . $ext;

        $destPub = public_path('videos/manual/' . $filename);
        $destBase = base_path('videos/manual/' . $filename);

        $dirPub = dirname($destPub);
        $dirBase = dirname($destBase);
        if (! is_dir($dirPub)) @mkdir($dirPub, 0755, true);
        if (! is_dir($dirBase)) @mkdir($dirBase, 0755, true);

        // Lưu tệp video vào public và base
        $file->move($dirPub, $filename);
        @copy($destPub, $destBase);

        $newRelativePath = 'videos/manual/' . $filename;
        $url = asset($newRelativePath) . '?v=' . time();

        // Tự động cập nhật đường dẫn trong tài liệu Markdown và dọn dẹp video cũ nếu có
        if (! empty($oldSrc)) {
            $oldPathClean = trim(parse_url($oldSrc, PHP_URL_PATH) ?? '', '/');
            $manualPath = $this->getManualPath();
            if (file_exists($manualPath)) {
                $md = file_get_contents($manualPath);
                $searchList = array_unique(array_filter([
                    $oldSrc,
                    $oldPathClean,
                    '/' . $oldPathClean,
                    urldecode($oldPathClean),
                    '/' . urldecode($oldPathClean),
                ]));
                $updatedMd = str_replace($searchList, $newRelativePath, $md);
                if ($updatedMd !== $md) {
                    @file_put_contents(base_path('TAI_LIEU_TOAN_DIEN_UNG_DUNG_KTNB.md'), $updatedMd);
                    @file_put_contents(public_path('TAI_LIEU_TOAN_DIEN_UNG_DUNG_KTNB.md'), $updatedMd);
                }
            }

            // Dọn dẹp tệp video cũ
            if (str_starts_with($oldPathClean, 'videos/manual/')) {
                $oldPub = public_path($oldPathClean);
                $oldBase = base_path($oldPathClean);
                if (file_exists($oldPub)) @unlink($oldPub);
                if (file_exists($oldBase)) @unlink($oldBase);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã tải video lên thành công!',
            'url' => $url,
            'relative_path' => $newRelativePath,
            'filename' => $filename,
        ]);
    }
}
