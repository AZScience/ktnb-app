<?php

namespace App\Http\Controllers;

use App\Models\AssetReception;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Gift;
use App\Services\BuildingBlockOptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AssetCheckController extends Controller
{
    public function __construct(
        private BuildingBlockOptionService $buildingBlocks,
    ) {}

    public function index(Request $request): View
    {
        $tab = $request->get('tab', 'reception');

        $buildingOptions = $this->buildingBlocks->options();
        $buildingNames = $this->buildingBlocks->filterValues();

        $officerFilterOptions = Employee::query()
            ->orderBy('name')
            ->pluck('name')
            ->filter(fn ($name) => filled($name))
            ->values()
            ->all();

        $mapOption = fn (string $name) => ['value' => $name, 'label' => $name];

        $departmentOptions = Department::orderBy('name')->pluck('name')->map($mapOption)->values()->all();

        return view('monitoring.asset-check.index', [
            'tab' => $tab,
            'receptionItems' => $this->itemsForTab($tab, 'reception'),
            'returnItems' => $this->itemsForTab($tab, 'return'),
            'gratitudeItems' => $this->itemsForTab($tab, 'gratitude'),
            'giftOptions' => Gift::orderBy('name')->pluck('name')
                ->map(fn ($name) => ['value' => $name, 'label' => $name])->values()->all(),
            'buildingOptions' => $buildingOptions,
            'departmentOptions' => $departmentOptions,
            'staffDefault' => $this->staffDefault(),
            'assetAdvancedFilterOptions' => [
                'buildings' => $buildingNames,
                'officers' => $officerFilterOptions,
            ],
        ]);
    }

    private function itemsForTab(string $activeTab, string $tab): Collection
    {
        if ($activeTab !== $tab) {
            return collect();
        }

        return match ($tab) {
            'return' => AssetReception::where('return_status', 'Chưa trả')->orderByDesc('created_at')->get(),
            'gratitude' => AssetReception::where('return_status', 'Đã trả')->orderByDesc('created_at')->get(),
            default => AssetReception::query()->orderByDesc('created_at')->get()->map(function (AssetReception $item) {
                $row = $item->toArray();
                $row['building_block_label'] = $this->buildingBlocks->displayLabel($item->building_block);

                return $row;
            }),
        };
    }

    private function staffDefault(): string
    {
        $user = auth()->user();
        if (! $user) {
            return '';
        }

        $employee = Employee::where('email', $user->email)->first();

        return Employee::nicknameFor($employee?->nickname ?: $employee?->name ?: $user->name);
    }
}
