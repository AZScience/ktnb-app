<?php

namespace App\Http\Controllers;

use App\Models\Gift;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GiftController extends Controller
{
    public function __construct(private ReportExportService $export) {}

    public function index(): View
    {
        return view('personnel.gifts.index', ['items' => Gift::orderBy('name')->get()]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'note' => 'nullable|string']);
        $item = Gift::create(['id' => $this->makeUniqueId($data['name']), 'name' => $data['name'], 'note' => $data['note'] ?? '']);
        return $this->respond($request, $item, 'Đã thêm quà tặng.', 'gifts.index');
    }

    public function create(): View { return view('personnel.gifts.form', ['item' => null]); }
    public function edit(Gift $gift): View { return view('personnel.gifts.form', ['item' => $gift]); }

    public function update(Request $request, Gift $gift): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'note' => 'nullable|string']);
        $gift->update(['name' => $data['name'], 'note' => $data['note'] ?? '']);
        return $this->respond($request, $gift->fresh(), 'Đã cập nhật quà tặng.', 'gifts.index');
    }

    public function destroy(Request $request, Gift $gift): JsonResponse|RedirectResponse
    {
        $gift->delete();
        if ($request->wantsJson()) return response()->json(['message' => 'Đã xóa quà tặng.']);
        return redirect()->route('gifts.index')->with('success', 'Đã xóa quà tặng.');
    }

    public function export(): StreamedResponse
    {
        return $this->export->download(Gift::orderBy('name')->get(), ['name' => 'Tên quà tặng', 'note' => 'Ghi chú'], 'DS_QuaTang_'.now()->format('Ymd').'.xlsx');
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
        return response()->json(['message' => 'Import thành công.', 'items' => Gift::orderBy('name')->get()]);
    }

    private function importRow(array $row): void
    {
        $name = trim($row['name']);
        if ($name === '') return;
        $existing = Gift::where('name', $name)->first();
        if ($existing) { $existing->update(['note' => $row['note'] ?? '']); return; }
        Gift::create(['id' => $this->makeUniqueId($name), 'name' => $name, 'note' => $row['note'] ?? '']);
    }

    private function parseSpreadsheet(string $path): array
    {
        return $this->parseNameNoteSheet($path, ['tên quà tặng', 'tên'], ['Tên quà tặng', 'Tên']);
    }

    private function parseNameNoteSheet(string $path, array $skipHeaders, array $nameKeys): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows = [];
        foreach ($sheet->getRowIterator(8) as $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) { $cells[] = trim((string) $cell->getValue()); }
            $name = $cells[0] ?? '';
            if ($name === '' || in_array(mb_strtolower($name), $skipHeaders, true)) continue;
            $rows[] = ['name' => $name, 'note' => $cells[1] ?? ''];
        }
        if ($rows !== []) return $rows;
        foreach ($sheet->toArray() as $line) {
            $name = '';
            foreach ($nameKeys as $key) { $name = trim((string) ($line[$key] ?? '')); if ($name !== '') break; }
            if ($name === '') $name = trim((string) ($line[0] ?? ''));
            if ($name === '' || in_array(mb_strtolower($name), $skipHeaders, true)) continue;
            $rows[] = ['name' => $name, 'note' => trim((string) ($line['Ghi chú'] ?? $line[1] ?? ''))];
        }
        return $rows;
    }

    private function makeUniqueId(string $name): string
    {
        $base = Str::slug($name) ?: 'gift';
        $id = $base; $n = 1;
        while (Gift::where('id', $id)->exists()) { $id = $base.'-'.$n; $n++; }
        return $id;
    }

    private function respond(Request $request, Gift $item, string $message, string $route): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) return response()->json(['message' => $message, 'item' => $item]);
        return redirect()->route($route)->with('success', $message);
    }
}
