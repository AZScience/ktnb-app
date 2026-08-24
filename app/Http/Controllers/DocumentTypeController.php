<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Services\CatalogExcelService;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentTypeController extends Controller
{
    public function __construct(
        private ReportExportService $export,
        private CatalogExcelService $catalogExcel,
    ) {}

    public function index(): View
    {
        return view('personnel.document-types.index', ['items' => DocumentType::orderBy('name')->get()]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'note' => 'nullable|string']);
        $item = DocumentType::create(['id' => $this->makeUniqueId($data['name']), 'name' => $data['name'], 'note' => $data['note'] ?? '']);
        return $this->respond($request, $item, 'Đã thêm loại văn bản.', 'document-types.index');
    }

    public function create(): View { return view('personnel.document-types.form', ['item' => null]); }
    public function edit(DocumentType $document_type): View { return view('personnel.document-types.form', ['item' => $document_type]); }

    public function update(Request $request, DocumentType $document_type): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'note' => 'nullable|string']);
        $document_type->update(['name' => $data['name'], 'note' => $data['note'] ?? '']);
        return $this->respond($request, $document_type->fresh(), 'Đã cập nhật loại văn bản.', 'document-types.index');
    }

    public function destroy(Request $request, DocumentType $document_type): JsonResponse|RedirectResponse
    {
        $document_type->delete();
        if ($request->wantsJson()) return response()->json(['message' => 'Đã xóa loại văn bản.']);
        return redirect()->route('document-types.index')->with('success', 'Đã xóa loại văn bản.');
    }

    public function export(): StreamedResponse
    {
        return $this->export->download(DocumentType::orderBy('name')->get(), ['name' => 'Loại văn bản', 'note' => 'Ghi chú'], 'DS_LoaiVanBan_'.now()->format('Ymd').'.xlsx');
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
        return response()->json(['message' => 'Import thành công.', 'items' => DocumentType::orderBy('name')->get()]);
    }

    private function importRow(array $row): void
    {
        $name = trim($row['name']);
        if ($name === '') return;
        $existing = DocumentType::where('name', $name)->first();
        if ($existing) { $existing->update(['note' => $row['note'] ?? '']); return; }
        DocumentType::create(['id' => $this->makeUniqueId($name), 'name' => $name, 'note' => $row['note'] ?? '']);
    }

    private function parseSpreadsheet(string $path): array
    {
        return $this->catalogExcel->parseNameNoteRows($path, 'Loại văn bản', ['Tên']);
    }

    private function makeUniqueId(string $name): string
    {
        $base = Str::slug($name) ?: 'document-type';
        $id = $base; $n = 1;
        while (DocumentType::where('id', $id)->exists()) { $id = $base.'-'.$n; $n++; }
        return $id;
    }

    private function respond(Request $request, DocumentType $item, string $message, string $route): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) return response()->json(['message' => $message, 'item' => $item]);
        return redirect()->route($route)->with('success', $message);
    }
}
