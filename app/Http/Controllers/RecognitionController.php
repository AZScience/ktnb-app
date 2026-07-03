<?php

namespace App\Http\Controllers;

use App\Models\Recognition;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecognitionController extends Controller
{
    public function __construct(private ReportExportService $export) {}

    public function index(): View
    {
        return view('personnel.recognitions.index', ['items' => Recognition::orderBy('name')->get()]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'note' => 'nullable|string']);
        $item = Recognition::create(['id' => $this->makeUniqueId($data['name']), 'name' => $data['name'], 'note' => $data['note'] ?? '']);
        return $this->respond($request, $item, 'Đã thêm việc ghi nhận.', 'recognitions.index');
    }

    public function create(): View { return view('personnel.recognitions.form', ['item' => null]); }
    public function edit(Recognition $recognition): View { return view('personnel.recognitions.form', ['item' => $recognition]); }

    public function update(Request $request, Recognition $recognition): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'note' => 'nullable|string']);
        $recognition->update(['name' => $data['name'], 'note' => $data['note'] ?? '']);
        return $this->respond($request, $recognition->fresh(), 'Đã cập nhật việc ghi nhận.', 'recognitions.index');
    }

    public function destroy(Request $request, Recognition $recognition): JsonResponse|RedirectResponse
    {
        $recognition->delete();
        if ($request->wantsJson()) return response()->json(['message' => 'Đã xóa việc ghi nhận.']);
        return redirect()->route('recognitions.index')->with('success', 'Đã xóa việc ghi nhận.');
    }

    public function export(): StreamedResponse
    {
        return $this->export->download(Recognition::orderBy('name')->get(), ['name' => 'Tên việc ghi nhận', 'note' => 'Ghi chú'], 'DS_ViecGhiNhan_'.now()->format('Ymd').'.xlsx');
    }

    public function importPreview(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);
        return response()->json(['rows' => $this->parseSpreadsheet($request->file('file')->getPathname())]);
    }

    public function import(Request $request): JsonResponse
    {
        $data = $request->validate(['rows' => 'required|array|min:1', 'rows.*.name' => 'required|string|max:255', 'rows.*.note' => 'nullable|string']);
        foreach ($data['rows'] as $row) { $this->importRow($row); }
        return response()->json(['message' => 'Import thành công.', 'items' => Recognition::orderBy('name')->get()]);
    }

    private function importRow(array $row): void
    {
        $name = trim($row['name']);
        if ($name === '') return;
        $existing = Recognition::where('name', $name)->first();
        if ($existing) { $existing->update(['note' => $row['note'] ?? '']); return; }
        Recognition::create(['id' => $this->makeUniqueId($name), 'name' => $name, 'note' => $row['note'] ?? '']);
    }

    private function parseSpreadsheet(string $path): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows = [];
        foreach ($sheet->getRowIterator(8) as $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) { $cells[] = trim((string) $cell->getValue()); }
            $name = $cells[0] ?? '';
            if ($name === '' || in_array(mb_strtolower($name), ['tên việc ghi nhận', 'việc ghi nhận'], true)) continue;
            $rows[] = ['name' => $name, 'note' => $cells[1] ?? ''];
        }
        if ($rows !== []) return $rows;
        foreach ($sheet->toArray() as $line) {
            $name = trim((string) ($line['Tên việc ghi nhận'] ?? $line['Việc ghi nhận'] ?? $line[0] ?? ''));
            if ($name === '' || in_array(mb_strtolower($name), ['tên việc ghi nhận', 'việc ghi nhận'], true)) continue;
            $rows[] = ['name' => $name, 'note' => trim((string) ($line['Ghi chú'] ?? $line[1] ?? ''))];
        }
        return $rows;
    }

    private function makeUniqueId(string $name): string
    {
        $base = Str::slug($name) ?: 'recognition';
        $id = $base; $n = 1;
        while (Recognition::where('id', $id)->exists()) { $id = $base.'-'.$n; $n++; }
        return $id;
    }

    private function respond(Request $request, Recognition $item, string $message, string $route): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) return response()->json(['message' => $message, 'item' => $item]);
        return redirect()->route($route)->with('success', $message);
    }
}
