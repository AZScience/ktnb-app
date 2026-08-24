<?php

namespace App\Http\Controllers;

use App\Models\Position;
use App\Services\CatalogExcelService;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PositionController extends Controller
{
    public function __construct(
        private ReportExportService $export,
        private CatalogExcelService $catalogExcel,
    ) {}

    public function index(): View
    {
        return view('personnel.positions.index', [
            'items' => Position::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'note' => 'nullable|string',
        ]);

        $position = Position::create([
            'id' => $this->makeUniqueId($data['name']),
            'name' => $data['name'],
            'note' => $data['note'] ?? '',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã thêm chức vụ.',
                'item' => $position,
            ]);
        }

        return redirect()->route('positions.index')->with('success', 'Đã thêm chức vụ.');
    }

    public function create(): View
    {
        return view('personnel.positions.form', ['item' => null]);
    }

    public function edit(Position $position): View
    {
        return view('personnel.positions.form', ['item' => $position]);
    }

    public function update(Request $request, Position $position): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'note' => 'nullable|string',
        ]);

        $position->update([
            'name' => $data['name'],
            'note' => $data['note'] ?? '',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã cập nhật chức vụ.',
                'item' => $position->fresh(),
            ]);
        }

        return redirect()->route('positions.index')->with('success', 'Đã cập nhật chức vụ.');
    }

    public function destroy(Request $request, Position $position): JsonResponse|RedirectResponse
    {
        $position->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đã xóa chức vụ.']);
        }

        return redirect()->route('positions.index')->with('success', 'Đã xóa chức vụ.');
    }

    public function export(): StreamedResponse
    {
        $items = Position::orderBy('name')->get();

        return $this->export->download($items, [
            'name' => 'Tên chức vụ',
            'note' => 'Ghi chú',
        ], 'DS_ChucVu_'.now()->format('Ymd').'.xlsx');
    }

    public function importPreview(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

        return response()->json([
            'rows' => $this->parseSpreadsheet($request->file('file')->getPathname()),
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $data = $request->validate([
            'rows' => 'required|array|min:1',
            'rows.*.name' => 'required|string|max:255',
            'rows.*.note' => 'nullable|string',
        ]);

        foreach ($data['rows'] as $row) {
            $name = trim($row['name']);
            if ($name === '') {
                continue;
            }

            $existing = Position::where('name', $name)->first();
            if ($existing) {
                $existing->update(['note' => $row['note'] ?? '']);
            } else {
                Position::create([
                    'id' => $this->makeUniqueId($name),
                    'name' => $name,
                    'note' => $row['note'] ?? '',
                ]);
            }
        }

        $items = Position::orderBy('name')->get();

        return response()->json([
            'message' => 'Import thành công.',
            'items' => $items,
        ]);
    }

    private function parseSpreadsheet(string $path): array
    {
        return $this->catalogExcel->parseNameNoteRows($path, 'Tên chức vụ');
    }

    private function makeUniqueId(string $name): string
    {
        $base = Str::slug($name) ?: 'position';
        $id = $base;
        $suffix = 1;

        while (Position::where('id', $id)->exists()) {
            $id = $base.'-'.$suffix;
            $suffix++;
        }

        return $id;
    }
}
