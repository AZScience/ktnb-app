@extends('layouts.nttu')

@section('title', 'Nhận - Trả tài sản')

@section('page-section', 'Nhận - Trả tài sản')

@section('content')

@php

    $tabs = [

        'reception' => ['label' => 'Sổ Tiếp nhận Tài sản', 'icon' => '📋'],

        'return'    => ['label' => 'Sổ Trao trả Tài sản', 'icon' => '↩️'],

        'gratitude' => ['label' => 'Sổ Người tốt việc tốt', 'icon' => '🤝'],

    ];

    $returnStatusOptions = [

        ['value' => 'Chưa trả', 'label' => 'Chưa trả'],

        ['value' => 'Đã trả', 'label' => 'Đã trả'],

        ['value' => 'Đang xử lý', 'label' => 'Đang xử lý'],

    ];

    $assetRoutes = [

        'store' => route('asset-receptions.store'),

        'update' => route('asset-receptions.update', ['asset_reception' => '__ID__']),

        'destroy' => route('asset-receptions.destroy', ['asset_reception' => '__ID__']),

        'export' => route('asset-receptions.export', ['tab' => $tab]),

        'importPreview' => route('asset-receptions.import-preview', ['tab' => $tab]),

        'import' => route('asset-receptions.import', ['tab' => $tab]),

    ];

    $assetImportColumns = (match ($tab) {
        'return' => collect([
            'entry_number' => 'Số tiếp nhận',
            'reception_date' => 'Ngày tiếp',
            'giver_name' => 'Người giao',
            'content' => 'Nội dung',
            'receiver_name' => 'Người nhận lại',
            'receiver_id' => 'MSSV/CCCD người nhận',
            'receiver_class' => 'Lớp người nhận',
            'receiver_unit' => 'Khoa/đơn vị người nhận',
            'receiver_phone' => 'Điện thoại người nhận',
            'resolution_date' => 'Ngày trả',
            'return_status' => 'Trạng thái trả',
            'return_staff' => 'Cán bộ bàn giao',
            'return_witness' => 'Người chứng kiến',
            'return_asset_state' => 'Tình trạng tài sản',
            'receiver_feedback' => 'Ý kiến người nhận',
            'note' => 'Ghi chú',
        ]),
        'gratitude' => collect([
            'gratitude_number' => 'Số thư tri ân',
            'entry_number' => 'Số KTNB',
            'giver_name' => 'Người nhận quà',
            'giver_id' => 'MSSV/CCCD',
            'giver_class' => 'Lớp',
            'giver_unit' => 'Khoa/đơn vị',
            'giver_phone' => 'Điện thoại',
            'content' => 'Nội dung',
            'gratitude_gift' => 'Quà tặng',
            'gratitude_date' => 'Ngày phát quà',
            'gratitude_staff' => 'Cán bộ phát quà',
            'return_status' => 'Trạng thái',
            'reception_date' => 'Ngày tiếp',
            'note' => 'Ghi chú',
        ]),
        default => collect([
            'entry_number' => 'Số tiếp nhận',
            'reception_date' => 'Ngày tiếp',
            'building_block' => 'Dãy nhà',
            'giver_name' => 'Người giao',
            'giver_employee_code' => 'Mã nhân viên',
            'giver_id' => 'Mã số Sinh viên',
            'giver_class' => 'Lớp',
            'giver_unit' => 'Khoa/đơn vị',
            'giver_phone' => 'Điện thoại',
            'content' => 'Nội dung',
            'asset_state' => 'Tình trạng tài sản',
            'return_status' => 'Trạng thái trả',
            'receiving_staff' => 'Cán bộ tiếp nhận',
            'witness' => 'Người chứng kiến',
            'note' => 'Ghi chú',
        ]),
    })->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values()->all();

    $sharedAdvanced = array_merge($assetAdvancedFilterOptions, [
        'dateField' => 'reception_date',
        'staffField' => 'receiving_staff',
        'buildingFilterField' => 'building_block',
    ]);

@endphp



<div class="mb-4 flex flex-wrap items-center gap-2 border-b pb-3">

    @foreach ($tabs as $key => $info)

        <a href="{{ route('asset-check.index', ['tab' => $key]) }}"

            class="flex items-center gap-1.5 rounded-md px-4 py-2 text-sm font-medium transition {{ $tab === $key ? 'bg-[var(--nttu-table-head)] text-white shadow' : 'bg-white border hover:bg-slate-50' }}">

            <span>{{ $info['icon'] }}</span>{{ $info['label'] }}

        </a>

    @endforeach

</div>



@if ($tab === 'gratitude')

    <x-catalog-data-table

        title="Sổ Người tốt việc tốt"

        entity-label="bản ghi tri ân"

        storage-key="asset-gratitude"

        modal-wide

        form-mode="asset-gratitude"

        modal-title='Tặng thư tri ân "Người tốt - Việc tốt"'

        :allow-add="false"
        :can-delete="false"
        :allowed-dialog-modes="['view', 'edit']"

        edit-action-label="Tri ân NT/VT"

        advanced-filter-mode="assets"

        :advanced-filter-options="array_merge($sharedAdvanced, ['dateField' => 'gratitude_date', 'staffField' => 'gratitude_staff'])"

        :staff-default="$staffDefault"

        :items="$gratitudeItems"

        :columns="[

            'gratitude_number' => ['label' => 'Số thư tri ân'],

            'giver_name' => ['label' => 'Người nhận quà', 'primary' => true],

            'content' => ['label' => 'Nội dung'],

            'reception_date' => ['label' => 'Ngày tiếp'],

            'gratitude_date' => ['label' => 'Ngày phát quà'],

            'gratitude_gift' => ['label' => 'Quà tặng'],

            'return_status' => ['label' => 'Trạng thái'],

        ]"

        :form-fields="[

            ['key' => 'content', 'type' => 'hidden'],

            ['key' => 'is_gratitude', 'label' => 'Gửi thư tri ân', 'type' => 'checkbox', 'checkboxStyle' => 'switch', 'alignInput' => true, 'icon' => 'gift', 'iconTone' => 'orange'],

            ['key' => 'gratitude_number', 'label' => 'Số thư tri ân', 'icon' => 'id-card', 'readonlyOnModes' => ['edit', 'view']],

            ['key' => 'entry_number', 'label' => 'Số KTNB', 'icon' => 'id-card', 'readonlyOnModes' => ['edit', 'view']],

            ['key' => 'giver_name', 'label' => 'Họ và tên người nhận quà', 'required' => true, 'icon' => 'user', 'iconTone' => 'orange', 'readonlyOnModes' => ['edit', 'view']],

            ['key' => 'giver_id', 'label' => 'MSSV/CCCD người nhận quà', 'icon' => 'id-card', 'iconTone' => 'orange', 'readonlyOnModes' => ['edit', 'view']],

            ['key' => 'giver_class', 'label' => 'Lớp người nhận quà', 'icon' => 'users', 'iconTone' => 'orange', 'readonlyOnModes' => ['edit', 'view']],

            ['key' => 'giver_unit', 'label' => 'Khoa/đơn vị/địa chỉ', 'icon' => 'landmark', 'iconTone' => 'orange', 'readonlyOnModes' => ['edit', 'view']],

            ['key' => 'giver_phone', 'label' => 'Số điện thoại', 'icon' => 'phone', 'iconTone' => 'orange', 'readonlyOnModes' => ['edit', 'view']],

            ['key' => 'gratitude_gift', 'label' => 'Quà trao tặng', 'type' => 'select', 'options' => array_merge([['value' => '', 'label' => '--- Chưa có ---']], $giftOptions), 'icon' => 'gift', 'iconTone' => 'orange'],

            ['key' => 'gratitude_date', 'type' => 'hidden'],

            ['key' => 'gratitude_staff', 'type' => 'hidden'],

            ['key' => 'return_status', 'type' => 'hidden'],

            ['key' => 'gratitude_evidence', 'label' => 'Minh chứng tri ân', 'type' => 'evidence', 'colSpan' => 3, 'collapsible' => true, 'icon' => 'paperclip', 'iconTone' => 'orange'],

            ['key' => 'note', 'label' => 'Ghi chú', 'type' => 'textarea', 'colSpan' => 3, 'icon' => 'note'],

        ]"

        :form-sections="[

            ['id' => 'gratitude-info', 'title' => 'THÔNG TIN TRI ÂN', 'icon' => 'gift', 'tone' => 'orange', 'columns' => 3, 'fields' => ['gratitude_number', 'entry_number', 'giver_name', 'giver_id', 'giver_class', 'giver_unit', 'giver_phone', 'gratitude_gift', 'is_gratitude', 'note', 'gratitude_evidence']],

        ]"

        :filter-fields="[

            ['key' => 'giver_name', 'label' => 'Người nhận quà'],

            ['key' => 'gratitude_number', 'label' => 'Số thư tri ân'],

            ['key' => 'content', 'label' => 'Nội dung'],

        ]"

        :routes="$assetRoutes"
        :import-columns="$assetImportColumns"

    />

@elseif ($tab === 'return')

    <x-catalog-data-table

        title="Sổ Trao trả Tài sản"

        entity-label="bản ghi trả"

        storage-key="asset-return"

        modal-wide

        form-mode="asset-return"

        modal-title="Giao trả Tài sản/Đồ vật"

        :allow-add="false"
        :can-delete="false"
        :allowed-dialog-modes="['view', 'edit']"

        edit-action-label="Trả tài sản"

        advanced-filter-mode="assets"

        :advanced-filter-options="array_merge($sharedAdvanced, ['dateField' => 'resolution_date', 'staffField' => 'return_staff'])"

        :staff-default="$staffDefault"

        :department-options="$departmentOptions"

        :items="$returnItems"

        :columns="[

            'entry_number' => ['label' => 'Số TN'],

            'reception_date' => ['label' => 'Ngày tiếp'],

            'giver_name' => ['label' => 'Người giao', 'primary' => true],

            'content' => ['label' => 'Nội dung'],

            'receiver_name' => ['label' => 'Người nhận lại'],

            'resolution_date' => ['label' => 'Ngày trả'],

            'return_status' => ['label' => 'Trạng thái'],

        ]"

        :form-fields="[

            ['key' => 'giver_name', 'type' => 'hidden'],

            ['key' => 'entry_number', 'label' => 'Số tiếp nhận', 'icon' => 'id-card', 'readonlyOnModes' => ['edit', 'view']],

            ['key' => 'content', 'label' => 'Nội dung giao nộp', 'colSpan' => 3, 'icon' => 'note', 'readonlyOnModes' => ['edit', 'view']],

            ['key' => 'return_evidence', 'label' => 'Minh chứng trao trả', 'type' => 'evidence', 'colSpan' => 3, 'collapsible' => true, 'icon' => 'paperclip', 'iconTone' => 'orange'],

            ['key' => 'receiver_name', 'label' => 'Họ và tên người nhận lại', 'required' => true, 'icon' => 'user', 'iconTone' => 'blue'],

            ['key' => 'receiver_id', 'label' => 'MSSV/CCCD người nhận lại', 'required' => true, 'icon' => 'id-card', 'iconTone' => 'blue'],

            ['key' => 'receiver_class', 'label' => 'Lớp người nhận lại', 'icon' => 'users', 'iconTone' => 'blue'],

            ['key' => 'receiver_unit', 'label' => 'Khoa/đơn vị/địa chỉ', 'type' => 'department_select', 'required' => true, 'icon' => 'landmark', 'iconTone' => 'blue'],

            ['key' => 'receiver_phone', 'label' => 'Số điện thoại', 'required' => true, 'icon' => 'phone', 'iconTone' => 'blue'],

            ['key' => 'return_asset_state', 'label' => 'Tình trạng tài sản', 'icon' => 'briefcase', 'iconTone' => 'blue'],

            ['key' => 'receiver_feedback', 'label' => 'Ý kiến của người nhận lại', 'icon' => 'note', 'iconTone' => 'blue'],

            ['key' => 'resolution_date', 'type' => 'hidden'],

            ['key' => 'return_staff', 'type' => 'hidden'],

            ['key' => 'return_status', 'type' => 'hidden'],

            ['key' => 'return_witness', 'label' => 'Người chứng kiến trả lại', 'icon' => 'user', 'iconTone' => 'orange'],

        ]"

        :form-sections="[

            ['id' => 'return-info', 'title' => 'THÔNG TIN TRAO TRẢ', 'icon' => 'gift', 'tone' => 'orange', 'columns' => 3, 'fields' => ['entry_number', 'receiver_name', 'receiver_id', 'receiver_class', 'receiver_unit', 'receiver_phone', 'return_asset_state', 'receiver_feedback', 'return_witness', 'content', 'return_evidence']],

        ]"

        :filter-fields="[

            ['key' => 'giver_name', 'label' => 'Người giao'],

            ['key' => 'receiver_name', 'label' => 'Người nhận'],

            ['key' => 'entry_number', 'label' => 'Số TN'],

        ]"

        :routes="$assetRoutes"
        :import-columns="$assetImportColumns"

    />

@else

    <x-catalog-data-table

        title="Sổ Tiếp nhận Tài sản"

        entity-label="bản ghi tiếp nhận"

        storage-key="asset-reception"

        modal-wide

        form-mode="asset-reception"

        form-layout="asset-reception"

        advanced-filter-mode="assets"

        :advanced-filter-options="$sharedAdvanced"

        :staff-default="$staffDefault"

        :building-options="$buildingOptions"

        :department-options="$departmentOptions"

        :form-defaults="['is_gratitude' => false, 'return_status' => 'Chưa trả', 'reception_date' => date('d/m/Y'), 'receiving_staff' => $staffDefault]"

        :items="$receptionItems"

        :columns="[

            'entry_number' => ['label' => 'Số tiếp nhận'],

            'reception_date' => ['label' => 'Ngày tiếp'],

            'giver_name' => ['label' => 'Người giao', 'primary' => true],

            'content' => ['label' => 'Nội dung'],

            'return_status' => ['label' => 'Trạng thái'],

            'building_block_label' => ['label' => 'Dãy nhà'],

        ]"

        :form-fields="[

            ['key' => 'is_gratitude', 'type' => 'hidden'],

            ['key' => 'return_status', 'type' => 'hidden'],

            ['key' => 'building_block', 'type' => 'hidden'],

            ['key' => 'reception_date', 'type' => 'hidden'],

            ['key' => 'entry_number', 'type' => 'hidden'],

            ['key' => 'giver_name', 'type' => 'hidden'],

            ['key' => 'giver_employee_code', 'type' => 'hidden'],

            ['key' => 'giver_id', 'type' => 'hidden'],

            ['key' => 'giver_class', 'type' => 'hidden'],

            ['key' => 'giver_unit', 'type' => 'hidden'],

            ['key' => 'giver_phone', 'type' => 'hidden'],

            ['key' => 'asset_state', 'type' => 'hidden'],

            ['key' => 'content', 'type' => 'hidden'],

            ['key' => 'receiving_staff', 'type' => 'hidden'],

            ['key' => 'witness', 'type' => 'hidden'],

            ['key' => 'note', 'type' => 'hidden'],

            ['key' => 'evidence', 'label' => 'Minh chứng tiếp nhận', 'type' => 'evidence'],

        ]"

        :filter-fields="[

            ['key' => 'giver_name', 'label' => 'Người giao'],

            ['key' => 'entry_number', 'label' => 'Số tiếp nhận'],

            ['key' => 'content', 'label' => 'Nội dung'],

        ]"

        :routes="$assetRoutes"
        :import-columns="$assetImportColumns"

    />

@endif

@endsection


