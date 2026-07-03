<?php

namespace App\Http\Controllers;

use App\Models\BuildingBlock;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BuildingBlockController extends Controller
{
    public function __construct(private ReportExportService $export) {}

    public function index(): View
    {
        return view('personnel.building-blocks.index', [
            'items' => BuildingBlock::orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'note' => 'nullable|string',
            'is_inactive' => 'nullable|boolean',
        ]);

        $block = BuildingBlock::create([
            'id' => $this->makeUniqueId($data['code']),
            'code' => $data['code'],
            'name' => $data['name'],
            'note' => $data['note'] ?? '',
            'is_inactive' => $request->boolean('is_inactive'),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã thêm dãy nhà.',
                'item' => $block,
            ]);
        }

        return redirect()->route('building-blocks.index')->with('success', 'Đã thêm dãy nhà.');
    }

    public function create(): View
    {
        return view('personnel.building-blocks.form', ['item' => null]);
    }

    public function edit(BuildingBlock $building_block): View
    {
        return view('personnel.building-blocks.form', ['item' => $building_block]);
    }

    public function update(Request $request, BuildingBlock $building_block): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'note' => 'nullable|string',
            'is_inactive' => 'nullable|boolean',
        ]);

        $building_block->update([
            'code' => $data['code'],
            'name' => $data['name'],
            'note' => $data['note'] ?? '',
            'is_inactive' => $request->boolean('is_inactive'),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã cập nhật dãy nhà.',
                'item' => $building_block->fresh(),
            ]);
        }

        return redirect()->route('building-blocks.index')->with('success', 'Đã cập nhật dãy nhà.');
    }

    public function destroy(Request $request, BuildingBlock $building_block): JsonResponse|RedirectResponse
    {
        $building_block->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đã xóa dãy nhà.']);
        }

        return redirect()->route('building-blocks.index')->with('success', 'Đã xóa dãy nhà.');
    }

    public function export(): StreamedResponse
    {
        $rows = BuildingBlock::orderBy('code')->get()->map(fn (BuildingBlock $b) => [
            'code' => $b->code,
            'name' => $b->name,
            'status' => $b->is_inactive ? 'Ngưng hoạt động' : 'Đang hoạt động',
            'note' => $b->note,
        ]);

        return $this->export->download($rows, [
            'code' => 'Mã phòng',
            'name' => 'Dãy nhà',
            'status' => 'Trạng thái',
            'note' => 'Ghi chú',
        ], 'DS_DayNha_'.now()->format('Ymd').'.xlsx');
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
            'rows.*.code' => 'required|string|max:20',
            'rows.*.name' => 'required|string|max:255',
            'rows.*.note' => 'nullable|string',
        ]);

        foreach ($data['rows'] as $row) {
            $code = trim($row['code']);
            $name = trim($row['name']);
            if ($code === '' || $name === '') {
                continue;
            }

            $existing = BuildingBlock::where('code', $code)->first();
            if ($existing) {
                $existing->update([
                    'name' => $name,
                    'note' => $row['note'] ?? '',
                ]);
            } else {
                BuildingBlock::create([
                    'id' => $this->makeUniqueId($code),
                    'code' => $code,
                    'name' => $name,
                    'note' => $row['note'] ?? '',
                    'is_inactive' => false,
                ]);
            }
        }

        return response()->json([
            'message' => 'Import thành công.',
            'items' => BuildingBlock::orderBy('code')->get(),
        ]);
    }

    private function parseSpreadsheet(string $path): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows = [];
        $startRow = 8;

        foreach ($sheet->getRowIterator($startRow) as $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $cells[] = trim((string) $cell->getValue());
            }

            $code = $cells[0] ?? '';
            if ($code === '' || mb_strtolower($code) === 'mã phòng') {
                continue;
            }

            $rows[] = [
                'code' => $code,
                'name' => $cells[1] ?? '',
                'note' => $cells[2] ?? '',
            ];
        }

        if ($rows === []) {
            foreach ($sheet->toArray() as $line) {
                $code = trim((string) ($line['Mã phòng'] ?? $line[0] ?? ''));
                if ($code === '' || mb_strtolower($code) === 'mã phòng') {
                    continue;
                }
                $rows[] = [
                    'code' => $code,
                    'name' => trim((string) ($line['Dãy nhà'] ?? $line[1] ?? '')),
                    'note' => trim((string) ($line['Ghi chú'] ?? $line[2] ?? '')),
                ];
            }
        }

        return $rows;
    }

    private function makeUniqueId(string $code): string
    {
        $base = 'block-'.(Str::slug($code) ?: 'block');
        $id = $base;
        $suffix = 1;

        while (BuildingBlock::where('id', $id)->exists()) {
            $id = $base.'-'.$suffix;
            $suffix++;
        }

        return $id;
    }
}
