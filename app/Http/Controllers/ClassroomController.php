<?php

namespace App\Http\Controllers;

use App\Models\BuildingBlock;
use App\Models\Classroom;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClassroomController extends Controller
{
    public function __construct(private ReportExportService $export) {}

    public function index(): View
    {
        return view('personnel.classrooms.index', [
            'blocks' => BuildingBlock::orderBy('name')->get(),
            'items' => $this->hydratedItems(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $this->validatedData($request);

        $classroom = Classroom::create([
            'id' => $this->makeUniqueId($data['name']),
            'name' => $data['name'],
            'building_block_id' => $data['building_block_id'],
            'seating_capacity' => $data['seating_capacity'] ?? null,
            'table_count' => $data['table_count'] ?? null,
            'exam_capacity' => $data['exam_capacity'] ?? null,
            'room_type' => $data['room_type'] ?? 'Lý thuyết',
            'subject_nature' => $data['subject_nature'] ?? '',
            'has_projector' => $request->boolean('has_projector'),
            'is_inactive' => $request->boolean('is_inactive'),
            'note' => $data['note'] ?? '',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã thêm phòng học.',
                'item' => $this->hydrateItem($classroom->fresh('buildingBlock')),
            ]);
        }

        return redirect()->route('classrooms.index')->with('success', 'Đã thêm phòng học.');
    }

    public function create(): View
    {
        return view('personnel.classrooms.form', [
            'item' => null,
            'blocks' => BuildingBlock::orderBy('code')->get(),
        ]);
    }

    public function edit(Classroom $classroom): View
    {
        return view('personnel.classrooms.form', [
            'item' => $classroom,
            'blocks' => BuildingBlock::orderBy('code')->get(),
        ]);
    }

    public function update(Request $request, Classroom $classroom): JsonResponse|RedirectResponse
    {
        $data = $this->validatedData($request);

        $classroom->update([
            'name' => $data['name'],
            'building_block_id' => $data['building_block_id'],
            'seating_capacity' => $data['seating_capacity'] ?? null,
            'table_count' => $data['table_count'] ?? null,
            'exam_capacity' => $data['exam_capacity'] ?? null,
            'room_type' => $data['room_type'] ?? 'Lý thuyết',
            'subject_nature' => $data['subject_nature'] ?? '',
            'has_projector' => $request->boolean('has_projector'),
            'is_inactive' => $request->boolean('is_inactive'),
            'note' => $data['note'] ?? '',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Đã cập nhật phòng học.',
                'item' => $this->hydrateItem($classroom->fresh('buildingBlock')),
            ]);
        }

        return redirect()->route('classrooms.index')->with('success', 'Đã cập nhật phòng học.');
    }

    public function destroy(Request $request, Classroom $classroom): JsonResponse|RedirectResponse
    {
        $classroom->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đã xóa phòng học.']);
        }

        return redirect()->route('classrooms.index')->with('success', 'Đã xóa phòng học.');
    }

    public function export(): StreamedResponse
    {
        $rows = Classroom::with('buildingBlock')->orderBy('name')->get()->map(fn (Classroom $c) => [
            'name' => $c->name,
            'building_block_name' => $c->buildingBlock?->name ?? '',
            'room_type' => $c->room_type,
            'seating_capacity' => $c->seating_capacity,
            'table_count' => $c->table_count,
            'exam_capacity' => $c->exam_capacity,
            'subject_nature' => $c->subject_nature,
            'has_projector' => $c->has_projector ? 'Có' : 'Không',
            'status' => $c->is_inactive ? 'Ngưng sử dụng' : 'Đang sử dụng',
            'note' => $c->note,
        ]);

        return $this->export->download($rows, [
            'name' => 'Tên phòng',
            'building_block_name' => 'Dãy nhà',
            'room_type' => 'Loại phòng',
            'seating_capacity' => 'Số chỗ ngồi',
            'table_count' => 'Số bàn',
            'exam_capacity' => 'Số chỗ thi',
            'subject_nature' => 'Tính chất môn',
            'has_projector' => 'Máy chiếu',
            'status' => 'Trạng thái',
            'note' => 'Ghi chú',
        ], 'DS_PhongHoc_'.now()->format('Ymd').'.xlsx');
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
            'rows.*.name' => 'required|string|max:100',
            'rows.*.building_block' => 'nullable|string|max:255',
            'rows.*.room_type' => 'nullable|string|max:100',
            'rows.*.seating_capacity' => 'nullable|integer|min:0',
            'rows.*.table_count' => 'nullable|integer|min:0',
            'rows.*.exam_capacity' => 'nullable|integer|min:0',
            'rows.*.note' => 'nullable|string',
        ]);

        foreach ($data['rows'] as $row) {
            $name = trim($row['name']);
            if ($name === '') {
                continue;
            }

            $buildingBlockId = $this->resolveBuildingBlockId($row['building_block'] ?? '');
            $existing = Classroom::where('name', $name)->first();

            if (! $buildingBlockId) {
                if (! $existing) {
                    continue;
                }
            }

            $payload = [
                'name' => $name,
                'room_type' => $row['room_type'] ?? 'Lý thuyết',
                'seating_capacity' => $row['seating_capacity'] ?? null,
                'table_count' => $row['table_count'] ?? null,
                'exam_capacity' => $row['exam_capacity'] ?? null,
                'note' => $row['note'] ?? '',
                'is_inactive' => false,
            ];

            if ($buildingBlockId) {
                $payload['building_block_id'] = $buildingBlockId;
            }

            if ($existing) {
                $existing->update($payload);
            } else {
                Classroom::create(array_merge($payload, [
                    'id' => $this->makeUniqueId($name),
                    'building_block_id' => $buildingBlockId,
                    'subject_nature' => '',
                    'has_projector' => true,
                ]));
            }
        }

        return response()->json([
            'message' => 'Import thành công.',
            'items' => $this->hydratedItems(),
        ]);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'building_block_id' => 'required|exists:building_blocks,id',
            'seating_capacity' => 'nullable|integer|min:0',
            'table_count' => 'nullable|integer|min:0',
            'exam_capacity' => 'nullable|integer|min:0',
            'room_type' => 'nullable|string|max:100',
            'subject_nature' => 'nullable|string|max:255',
            'has_projector' => 'nullable|boolean',
            'is_inactive' => 'nullable|boolean',
            'note' => 'nullable|string',
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

            $name = $cells[0] ?? '';
            if ($name === '' || mb_strtolower($name) === 'tên phòng') {
                continue;
            }

            $rows[] = [
                'name' => $name,
                'building_block' => $cells[1] ?? '',
                'room_type' => $cells[2] ?? 'Lý thuyết',
                'seating_capacity' => $cells[3] !== '' ? (int) $cells[3] : null,
                'table_count' => $cells[4] !== '' ? (int) $cells[4] : null,
                'exam_capacity' => $cells[5] !== '' ? (int) $cells[5] : null,
                'note' => $cells[6] ?? '',
            ];
        }

        if ($rows === []) {
            foreach ($sheet->toArray() as $line) {
                $name = trim((string) ($line['Tên phòng'] ?? $line[0] ?? ''));
                if ($name === '' || mb_strtolower($name) === 'tên phòng') {
                    continue;
                }
                $rows[] = [
                    'name' => $name,
                    'building_block' => trim((string) ($line['Dãy nhà'] ?? $line[1] ?? '')),
                    'room_type' => trim((string) ($line['Loại phòng'] ?? $line[2] ?? 'Lý thuyết')),
                    'seating_capacity' => isset($line['Số chỗ ngồi']) && $line['Số chỗ ngồi'] !== '' ? (int) $line['Số chỗ ngồi'] : null,
                    'table_count' => isset($line['Số bàn']) && $line['Số bàn'] !== '' ? (int) $line['Số bàn'] : null,
                    'exam_capacity' => isset($line['Số chỗ thi']) && $line['Số chỗ thi'] !== '' ? (int) $line['Số chỗ thi'] : null,
                    'note' => trim((string) ($line['Ghi chú'] ?? $line[6] ?? '')),
                ];
            }
        }

        return $rows;
    }

    private function resolveBuildingBlockId(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $block = BuildingBlock::find($value)
            ?? BuildingBlock::where('name', $value)->first()
            ?? BuildingBlock::where('code', $value)->first();

        return $block?->id;
    }

    private function makeUniqueId(string $name): string
    {
        if (! Classroom::where('id', $name)->exists()) {
            return $name;
        }

        $base = 'cr_'.(Str::slug($name) ?: 'room');
        $id = $base;
        $suffix = 1;

        while (Classroom::where('id', $id)->exists()) {
            $id = $base.'-'.$suffix;
            $suffix++;
        }

        return $id;
    }

    private function hydratedItems()
    {
        return Classroom::with('buildingBlock')->orderBy('name')->get()
            ->map(fn (Classroom $c) => $this->hydrateItem($c));
    }

    private function hydrateItem(Classroom $classroom): array
    {
        $data = $classroom->toArray();
        $data['building_block_name'] = $classroom->buildingBlock?->name ?? '';

        return $data;
    }
}
