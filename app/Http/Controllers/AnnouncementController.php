<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function index(): View
    {
        return view('tools.announcements.index', [
            'items' => Announcement::query()
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Announcement $item) => $this->hydrateItem($item)),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $this->validatedData($request);
        $user = $request->user();

        $item = Announcement::create([
            'id' => (string) Str::uuid(),
            'title' => $data['title'],
            'body' => $data['body'],
            'is_active' => $data['is_active'],
            'published_from' => $data['published_from'],
            'published_until' => $data['published_until'],
            'created_by_user_id' => $user?->id,
            'created_by_name' => $user?->name,
        ]);

        $this->activityLog->log('Đăng thông báo', 'Announcement', $item->title);

        return $this->respond($request, $this->hydrateItem($item), 'Đã đăng thông báo.', 'announcements.index');
    }

    public function update(Request $request, Announcement $announcement): JsonResponse|RedirectResponse
    {
        $data = $this->validatedData($request);

        $announcement->update([
            'title' => $data['title'],
            'body' => $data['body'],
            'is_active' => $data['is_active'],
            'published_from' => $data['published_from'],
            'published_until' => $data['published_until'],
        ]);

        $this->activityLog->log('Cập nhật thông báo', 'Announcement', $announcement->title);

        return $this->respond($request, $this->hydrateItem($announcement->fresh()), 'Đã cập nhật thông báo.', 'announcements.index');
    }

    public function destroy(Request $request, Announcement $announcement): JsonResponse|RedirectResponse
    {
        $title = $announcement->title;
        $announcement->delete();

        $this->activityLog->log('Xóa thông báo', 'Announcement', $title);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đã xóa thông báo.']);
        }

        return redirect()->route('announcements.index')->with('success', 'Đã xóa thông báo.');
    }

    private function validatedData(Request $request): array
    {
        $request->merge([
            'published_from' => $this->nullableDateInput($request->input('published_from')),
            'published_until' => $this->nullableDateInput($request->input('published_until')),
        ]);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'published_from' => 'nullable|date',
            'published_until' => 'nullable|date|after_or_equal:published_from',
        ]);

        return [
            'title' => trim($data['title']),
            'body' => trim($data['body']),
            'is_active' => true,
            'published_from' => $this->parseOptionalDate($data['published_from'] ?? null, startOfDay: true),
            'published_until' => $this->parseOptionalDate($data['published_until'] ?? null, startOfDay: false),
        ];
    }

    private function nullableDateInput(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function parseOptionalDate(?string $value, bool $startOfDay): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $date = Carbon::parse($value);

        return $startOfDay ? $date->startOfDay() : $date->endOfDay();
    }

    private function hydrateItem(Announcement $item): array
    {
        $data = $item->toArray();
        $data['author_name'] = $item->created_by_name ?: $item->author?->name ?: '';
        $data['body_preview'] = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $item->body)) ?? '');
        $data['published_from'] = $item->published_from?->format('Y-m-d') ?? '';
        $data['published_until'] = $item->published_until?->format('Y-m-d') ?? '';
        $data['created_at_label'] = $item->created_at?->format('d/m/Y H:i') ?? '';

        return $data;
    }

    private function respond(Request $request, array $item, string $message, string $route): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => $message, 'item' => $item]);
        }

        return redirect()->route($route)->with('success', $message);
    }
}
