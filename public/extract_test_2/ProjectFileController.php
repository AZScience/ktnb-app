<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Services\PermissionService;
use App\Services\ProjectFilePermissionService;
use App\Services\ProjectFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectFileController extends Controller
{
    public function __construct(
        private ProjectFileService $files,
        private PermissionService $permissions,
        private ProjectFilePermissionService $pathPermissions,
        private ActivityLogService $activityLog,
    ) {}

    public function index(): View
    {
        $this->authorizeAccess();

        return view('settings.project-files.index');
    }

    public function list(Request $request): JsonResponse
    {
        $this->authorizeAccess();

        $path = (string) $request->query('path', '');
        $this->pathPermissions->assertPathAction($path, 'view');

        $listing = $this->files->listDirectory($path);
        $listing['items'] = $this->pathPermissions->annotateItems(
            $this->pathPermissions->filterVisibleItems($listing['items']),
        );

        return response()->json($listing);
    }

    public function show(Request $request): JsonResponse
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'path' => 'required|string|max:500',
        ]);

        $this->pathPermissions->assertPathAction($data['path'], 'view');

        return response()->json($this->files->readFile($data['path']));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'path' => 'nullable|string|max:500',
            'type' => 'required|in:file,folder,upload',
            'name' => 'required_if:type,file,folder|nullable|string|max:255',
            'content' => 'nullable|string',
            'file' => 'required_if:type,upload|file|max:51200',
        ]);

        $parent = (string) ($data['path'] ?? '');
        $this->pathPermissions->assertPathAction($parent, 'add');
        $createdPath = match ($data['type']) {
            'folder' => $this->files->createDirectory($parent, $data['name']),
            'file' => $this->files->createFile($parent, $data['name'], $data['content'] ?? ''),
            'upload' => $this->files->uploadFile($parent, $request->file('file')),
        };

        $this->activityLog->log(
            'Tạo file project',
            'ProjectFile',
            $createdPath,
        );

        return response()->json([
            'message' => 'Đã tạo thành công.',
            'path' => $createdPath,
            'listing' => $this->files->listDirectory($parent),
        ], 201);
    }

    public function batchUpload(Request $request): JsonResponse
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'path' => 'nullable|string|max:500',
            'files' => 'required|array|min:1|max:500',
            'files.*' => 'file|max:51200',
            'paths' => 'nullable|array',
            'paths.*' => 'nullable|string|max:500',
        ]);

        $parent = (string) ($data['path'] ?? '');
        $this->pathPermissions->assertPathAction($parent, 'add');
        $uploads = [];
        foreach ($data['files'] as $index => $file) {
            $uploads[] = [
                'file' => $file,
                'relative_path' => $data['paths'][$index] ?? $file->getClientOriginalName(),
            ];
        }

        $result = $this->files->uploadBatch($parent, $uploads);

        $this->activityLog->log(
            'Upload hàng loạt file project',
            'ProjectFile',
            $parent.' ('.count($result['created']).' file)',
        );

        $message = 'Đã upload '.count($result['created']).' file.';
        if ($result['skipped'] !== []) {
            $message .= ' Bỏ qua '.count($result['skipped']).' file trùng.';
        }
        if ($result['errors'] !== []) {
            $message .= ' '.count($result['errors']).' lỗi.';
        }

        return response()->json([
            'message' => $message,
            'created' => $result['created'],
            'skipped' => $result['skipped'],
            'errors' => $result['errors'],
            'listing' => $this->files->listDirectory($parent),
        ], 201);
    }

    public function move(Request $request): JsonResponse
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'path' => 'required|string|max:500',
            'destination' => 'required|string|max:500',
        ]);

        $this->pathPermissions->assertPathAction($data['path'], 'edit');
        $this->pathPermissions->assertPathAction($data['destination'], 'add');

        $newPath = $this->files->move($data['path'], $data['destination']);
        $parent = dirname(str_replace('\\', '/', $newPath));
        $parent = $parent === '.' ? '' : $parent;

        $this->activityLog->log(
            'Di chuyển file project',
            'ProjectFile',
            "{$data['path']} -> {$newPath}",
        );

        return response()->json([
            'message' => 'Đã di chuyển.',
            'path' => $newPath,
            'listing' => $this->files->listDirectory($parent),
        ]);
    }

    public function extract(Request $request): JsonResponse
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'path' => 'nullable|string|max:500',
            'paths' => 'nullable|array|min:1|max:100',
            'paths.*' => 'string|max:500',
            'destination' => 'nullable|string|max:500',
        ]);

        $paths = $data['paths'] ?? (($data['path'] ?? null) ? [$data['path']] : []);
        if ($paths === []) {
            abort(422, 'Chưa chọn file nén.');
        }

        foreach ($paths as $archivePath) {
            $this->pathPermissions->assertPathAction($archivePath, 'edit');
        }
        if (($data['destination'] ?? null) !== null) {
            $this->pathPermissions->assertPathAction($data['destination'], 'add');
        }

        if (count($paths) === 1 && ($data['destination'] ?? null) !== null) {
            $result = $this->files->extractArchive($paths[0], $data['destination']);
            $this->activityLog->log(
                'Giải nén file project',
                'ProjectFile',
                "{$paths[0]} -> {$result['destination']}",
            );

            return response()->json([
                'message' => "Đã giải nén {$result['extracted_count']} file.",
                'destination' => $result['destination'],
                'listing' => $this->files->listDirectory($result['destination']),
            ]);
        }

        $batch = $this->files->extractMany($paths);
        $this->activityLog->log(
            'Giải nén hàng loạt file project',
            'ProjectFile',
            implode(', ', array_column($batch['results'], 'path')),
        );

        $message = 'Đã giải nén '.count($batch['results']).' file nén.';
        if ($batch['errors'] !== []) {
            $message .= ' '.count($batch['errors']).' lỗi.';
        }

        $parent = dirname(str_replace('\\', '/', $paths[0]));
        $parent = $parent === '.' ? '' : $parent;

        return response()->json([
            'message' => $message,
            'results' => $batch['results'],
            'errors' => $batch['errors'],
            'listing' => $this->files->listDirectory($parent),
        ]);
    }

    public function compress(Request $request): JsonResponse
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'paths' => 'required|array|min:1|max:500',
            'paths.*' => 'string|max:500',
            'path' => 'nullable|string|max:500',
            'name' => 'nullable|string|max:255',
        ]);

        $parent = (string) ($data['path'] ?? '');
        foreach ($data['paths'] as $selectedPath) {
            $this->pathPermissions->assertPathAction($selectedPath, 'edit');
        }
        $this->pathPermissions->assertPathAction($parent, 'add');

        $archivePath = $this->files->createArchive(
            $data['paths'],
            $parent,
            (string) ($data['name'] ?? ''),
        );

        $this->activityLog->log(
            'Nén file project',
            'ProjectFile',
            $archivePath.' ('.count($data['paths']).' mục)',
        );

        return response()->json([
            'message' => 'Đã nén thành công.',
            'path' => $archivePath,
            'listing' => $this->files->listDirectory($parent),
        ], 201);
    }

    public function batchDownload(Request $request): BinaryFileResponse
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'paths' => 'required|array|min:1|max:500',
            'paths.*' => 'string|max:500',
        ]);

        foreach ($data['paths'] as $selectedPath) {
            $this->pathPermissions->assertPathAction($selectedPath, 'download');
        }

        return $this->files->downloadSelection($data['paths']);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'path' => 'required|string|max:500',
            'content' => 'required|string',
        ]);

        $this->pathPermissions->assertPathAction($data['path'], 'edit');

        $this->files->writeFile($data['path'], $data['content']);

        $this->activityLog->log(
            'Sửa file project',
            'ProjectFile',
            $data['path'],
        );

        return response()->json([
            'message' => 'Đã lưu file.',
            'file' => $this->files->readFile($data['path']),
        ]);
    }

    public function rename(Request $request): JsonResponse
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'path' => 'required|string|max:500',
            'name' => 'required|string|max:255',
        ]);

        $this->pathPermissions->assertPathAction($data['path'], 'edit');

        $newPath = $this->files->rename($data['path'], $data['name']);
        $parent = dirname(str_replace('\\', '/', $newPath));
        $parent = $parent === '.' ? '' : $parent;

        $this->activityLog->log(
            'Đổi tên file project',
            'ProjectFile',
            "{$data['path']} -> {$newPath}",
        );

        return response()->json([
            'message' => 'Đã đổi tên.',
            'path' => $newPath,
            'listing' => $this->files->listDirectory($parent),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'path' => 'nullable|string|max:500',
            'paths' => 'nullable|array|min:1|max:500',
            'paths.*' => 'string|max:500',
        ]);

        $paths = $data['paths'] ?? (($data['path'] ?? null) ? [$data['path']] : []);
        if ($paths === []) {
            abort(422, 'Chưa chọn mục nào để xóa.');
        }

        foreach ($paths as $selectedPath) {
            $this->pathPermissions->assertPathAction($selectedPath, 'delete');
        }

        $parent = dirname(str_replace('\\', '/', $paths[0]));
        $parent = $parent === '.' ? '' : $parent;

        if (count($paths) === 1) {
            $this->files->delete($paths[0]);
            $this->activityLog->log('Xóa file project', 'ProjectFile', $paths[0]);

            return response()->json([
                'message' => 'Đã xóa.',
                'listing' => $this->files->listDirectory($parent),
            ]);
        }

        $result = $this->files->deleteMany($paths);
        $this->activityLog->log(
            'Xóa hàng loạt file project',
            'ProjectFile',
            implode(', ', $result['deleted']),
        );

        $message = 'Đã xóa '.count($result['deleted']).' mục.';
        if ($result['errors'] !== []) {
            $message .= ' '.count($result['errors']).' lỗi.';
        }

        return response()->json([
            'message' => $message,
            'deleted' => $result['deleted'],
            'errors' => $result['errors'],
            'listing' => $this->files->listDirectory($parent),
        ]);
    }

    public function download(Request $request): BinaryFileResponse
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'path' => 'required|string|max:500',
        ]);

        $this->pathPermissions->assertPathAction($data['path'], 'download');

        return $this->files->download($data['path']);
    }

    private function authorizeAccess(): void
    {
        if (! $this->permissions->isSuperAdmin() && ! $this->permissions->can('/settings/project-files', 'access')) {
            abort(403, 'Chỉ quản trị viên hệ thống mới được quản lý file project.');
        }
    }
}
