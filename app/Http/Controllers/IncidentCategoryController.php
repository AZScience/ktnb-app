<?php

namespace App\Http\Controllers;

use App\Models\IncidentCategory;
use App\Models\Recognition;
use App\Services\CatalogExcelService;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncidentCategoryController extends Controller
{
    public function __construct(
        private ReportExportService $export,
        private CatalogExcelService $catalogExcel,
    ) {}

    public function index(): View
    {
        return view('personnel.incident-categories.index', [
            'recognitions' => Recognition::orderBy('name')->get(),
            'items' => $this->hydratedItems(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'recognition_id' => 'required|exists:recognitions,id',
            'note' => 'nullable|string',
        ]);

        $item = IncidentCategory::create([
            'id' => $this->makeUniqueId($data['name']),
            'name' => $data['name'],
            'recognition_id' => $data['recognition_id'],
            'note' => $data['note'] ?? '',
        ]);

        return $this->respond($request, $this->hydrateItem($item->fresh('recognition')), 'Đã thêm việc phát sinh.', 'incident-categories.index');
    }

    public function create(): View
    {
        return view('personnel.incident-categories.form', [
            'item' => null,
            'recognitions' => Recognition::orderBy('name')->get(),
        ]);
    }

    public function edit(IncidentCategory $incident_category): View
    {
        return view('personnel.incident-categories.form', [
            'item' => $incident_category,
            'recognitions' => Recognition::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, IncidentCategory $incident_category): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'recognition_id' => 'required|exists:recognitions,id',
            'note' => 'nullable|string',
        ]);

        $incident_category->update([
            'name' => $data['name'],
            'recognition_id' => $data['recognition_id'],
            'note' => $data['note'] ?? '',
        ]);

        return $this->respond($request, $this->hydrateItem($incident_category->fresh('recognition')), 'Đã cập nhật việc phát sinh.', 'incident-categories.index');
    }

    public function destroy(Request $request, IncidentCategory $incident_category): JsonResponse|RedirectResponse
    {
        $incident_category->delete();
        if ($request->wantsJson()) return response()->json(['message' => 'Đã xóa việc phát sinh.']);
        return redirect()->route('incident-categories.index')->with('success', 'Đã xóa việc phát sinh.');
    }

    public function export(): StreamedResponse
    {
        $rows = $this->hydratedItems()->map(fn (array $item) => [
            'recognition_name' => $item['recognition_name'],
            'name' => $item['name'],
            'note' => $item['note'],
        ]);

        return $this->export->download($rows, [
            'recognition_name' => 'Việc ghi nhận',
            'name' => 'Tên việc phát sinh',
            'note' => 'Ghi chú',
        ], 'DS_ViecPhatSinh_'.now()->format('Ymd').'.xlsx');
    }

    public function importPreview(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);
        return response()->json(['rows' => $this->parseSpreadsheet($request->file('file')->getPathname())]);
    }

    public function import(Request $request): JsonResponse
    {
        $data = $request->validate([
            'rows' => 'required|array|min:1',
            'rows.*.name' => 'required|string|max:255',
            'rows.*.recognition_name' => 'required|string|max:255',
            'rows.*.note' => 'nullable|string',
        ]);

        foreach ($data['rows'] as $row) {
            $recognitionId = $this->resolveRecognitionId($row['recognition_name'] ?? '');
            if (! $recognitionId) continue;

            $name = trim($row['name']);
            if ($name === '') continue;

            $payload = [
                'recognition_id' => $recognitionId,
                'note' => $row['note'] ?? '',
            ];

            $existing = IncidentCategory::where('name', $name)->first();
            if ($existing) {
                $existing->update(array_merge($payload, ['name' => $name]));
            } else {
                IncidentCategory::create(array_merge($payload, [
                    'id' => $this->makeUniqueId($name),
                    'name' => $name,
                ]));
            }
        }

        return response()->json(['message' => 'Import thành công.', 'items' => $this->hydratedItems()]);
    }

    private function parseSpreadsheet(string $path): array
    {
        return $this->catalogExcel->parseRows($path, [
            'recognition_name' => 'Việc ghi nhận',
            'name' => 'Tên việc phát sinh',
            'note' => 'Ghi chú',
        ], [
            'requiredKey' => 'name',
            'aliases' => [
                'việc ghi nhận' => 'recognition_name',
                'tên việc phát sinh' => 'name',
                'tên' => 'name',
                'ghi chú' => 'note',
            ],
        ]);
    }

    private function resolveRecognitionId(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') return null;

        $recognition = Recognition::find($value)
            ?? Recognition::where('name', $value)->first();

        return $recognition?->id;
    }

    private function hydratedItems()
    {
        return IncidentCategory::with('recognition')->orderBy('name')->get()
            ->map(fn (IncidentCategory $item) => $this->hydrateItem($item));
    }

    private function hydrateItem(IncidentCategory $item): array
    {
        $data = $item->toArray();
        $data['recognition_name'] = $item->recognition?->name ?? '';

        return $data;
    }

    private function makeUniqueId(string $name): string
    {
        $base = Str::slug($name) ?: 'incident';
        $id = $base; $n = 1;
        while (IncidentCategory::where('id', $id)->exists()) { $id = $base.'-'.$n; $n++; }
        return $id;
    }

    private function respond(Request $request, array $item, string $message, string $route): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) return response()->json(['message' => $message, 'item' => $item]);
        return redirect()->route($route)->with('success', $message);
    }
}
