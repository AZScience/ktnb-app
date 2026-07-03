@props(['field'])

@php
    $key = $field['key'];
    $type = $field['type'] ?? 'text';
    $icon = $field['icon'] ?? 'user';
    $iconTone = $field['iconTone'] ?? 'primary';
    $colSpan = (int) ($field['colSpan'] ?? ($field['wide'] ?? false ? 3 : 1));
    $colClass = match ($colSpan) {
        3 => 'md:col-span-2 lg:col-span-3',
        2 => 'md:col-span-2',
        default => '',
    };
    $checkboxStyle = $field['checkboxStyle'] ?? 'default';
@endphp

<div @class(['space-y-1', $colClass]) x-show="fieldVisible('{{ $key }}')" x-cloak>
    @if ($type === 'evidence')
        @php $collapsible = !empty($field['collapsible']); @endphp
        @if ($collapsible)
            <div class="flex items-center justify-between border-b border-dashed border-gray-300 pb-1">
                <label class="mb-0 flex items-center gap-2 text-sm font-medium text-gray-700">
                    <x-form-field-icon :name="$icon" :tone="$iconTone" />
                    <span x-text="text(@js($field['label']))"></span>
                </label>
                <button type="button"
                    class="text-[10px] font-bold uppercase tracking-wider text-[var(--nttu-primary)] hover:opacity-80"
                    @click="evidencePanelExpanded = !evidencePanelExpanded"
                    x-text="evidencePanelExpanded ? 'Thu gọn' : 'Mở rộng'"></button>
            </div>
            <div x-show="evidencePanelExpanded" x-cloak class="mt-3" :class="isViewMode && 'pointer-events-none opacity-80'">
                <x-evidence-input-panel />
            </div>
        @else
        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
            <x-form-field-icon :name="$icon" :tone="$iconTone" />
            <span x-text="text(@js($field['label']))"></span>
        </label>
        <div class="rounded-lg border bg-white p-3" :class="isViewMode && 'pointer-events-none opacity-80'">
            <x-evidence-input-panel />
        </div>
        @endif
    @elseif ($type === 'violation_student_id')
        <x-violation-student-id-field :field="$field" />
    @elseif ($type === 'violation_class')
        <x-violation-creatable-select-field :field="$field" list-id="violation-class-options" placeholder="Chọn hoặc nhập lớp..." />
    @elseif ($type === 'violation_department')
        <x-violation-creatable-select-field :field="$field" list-id="violation-department-options" placeholder="Chọn hoặc nhập khoa..." />
    @elseif ($type === 'department_select')
        <x-department-select-field :field="$field" />
    @elseif ($type === 'signature_pad')
        <x-violation-signature-field :field="$field" />
    @elseif ($type === 'violation_verification')
        <x-violation-verification-panel :field="$field" />
    @elseif ($type === 'checkbox' && $checkboxStyle === 'switch')
        @if (!empty($field['alignInput']))
            <label class="pointer-events-none flex items-center gap-2 text-sm text-gray-500 opacity-0" aria-hidden="true">
                <x-form-field-icon :name="$icon" :tone="$iconTone" />
                <span x-text="text(@js($field['label']))"></span>
            </label>
        @endif
        <div @class([
            'flex h-10 items-center justify-between rounded-md border border-gray-200 px-3',
            empty($field['alignInput']) ? 'mt-auto' : null,
        ])>
            <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                <x-form-field-icon :name="$icon" :tone="$field['iconTone'] ?? ($key === 'is_inactive' ? 'destructive' : 'primary')" />
                <span x-text="text(@js($field['label']))"></span>
            </label>
            <template x-if="fieldReadOnly('{{ $key }}')">
                <span class="text-sm font-bold" x-text="fieldDisplayValue('{{ $key }}')"></span>
            </template>
            <template x-if="!fieldReadOnly('{{ $key }}')">
                <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-[var(--nttu-primary)]"
                       :checked="!!form['{{ $key }}']"
                       @change="form['{{ $key }}'] = $event.target.checked">
            </template>
        </div>
    @elseif ($type === 'checkbox')
        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
            <template x-if="!fieldReadOnly('{{ $key }}')">
                <input type="checkbox" class="h-4 w-4 rounded border-gray-300"
                       :checked="!!form['{{ $key }}']"
                       @change="form['{{ $key }}'] = $event.target.checked">
            </template>
            <x-form-field-icon :name="$icon" :tone="$iconTone" />
            <span x-text="text(@js($field['label']))"></span>
            <template x-if="fieldReadOnly('{{ $key }}')">
                <span class="font-bold" x-text="fieldDisplayValue('{{ $key }}')"></span>
            </template>
        </label>
    @elseif ($type === 'avatar')
        <x-avatar-input :field-key="$key" :label="$field['label']" />
    @elseif ($type === 'hidden')
        <input type="hidden" x-model="form['{{ $key }}']">
    @elseif ($type === 'ckeditor')
        <label class="flex items-center gap-2 text-sm text-gray-500">
            <x-form-field-icon :name="$icon" :tone="$iconTone" />
            <span>
                <span x-text="text(@js($field['label']))"></span>@if (!empty($field['required'])) *@endif
            </span>
        </label>
        <div x-show="fieldReadOnly('{{ $key }}')" x-cloak class="prose prose-sm max-w-none text-slate-800" x-html="form['{{ $key }}'] || ''"></div>
        <div x-show="!fieldReadOnly('{{ $key }}')" class="relative">
            <div x-show="ckeditorMounting"
                 x-cloak
                 class="absolute inset-0 z-10 flex min-h-[220px] items-center justify-center rounded-md border border-slate-200 bg-slate-50 text-sm text-slate-500">
                Đang tải trình soạn thảo...
            </div>
            <div class="nttu-ckeditor-compose min-h-[220px] rounded-md border border-slate-200 bg-white"
                 data-catalog-ckeditor-field="{{ $key }}"></div>
        </div>
    @else
        <label class="flex items-center gap-2 text-sm text-gray-500">
            <x-form-field-icon :name="$icon" :tone="$iconTone" />
            <span>
                <span x-text="text(@js($field['label']))"></span>@if (!empty($field['required'])) *@endif
            </span>
        </label>

        <template x-if="fieldReadOnly('{{ $key }}')">
            <p class="text-sm font-bold text-slate-800" x-text="fieldDisplayValue('{{ $key }}')"></p>
        </template>

        <template x-if="!fieldReadOnly('{{ $key }}')">
            @if ($type === 'select')
                <select class="nttu-form-control"
                        x-model="form['{{ $key }}']"
                        @if (!empty($field['required'])) required @endif>
                    @if (empty($field['required']))
                        <option value="">---</option>
                    @endif
                    @foreach ($field['options'] ?? [] as $option)
                        <option value="{{ $option['value'] }}" class="i18n-auto">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            @elseif ($type === 'number')
                <input type="number" class="nttu-form-control"
                       x-model="form['{{ $key }}']"
                       @if (!empty($field['required'])) required @endif
                       @if(!empty($field['placeholder'])) placeholder="{{ $field['placeholder'] }}" @endif>
            @elseif ($type === 'date')
                <input type="date" class="nttu-form-control"
                       x-model="form['{{ $key }}']"
                       @if (!empty($field['required'])) required @endif>
            @elseif ($type === 'password')
                <input type="password" class="nttu-form-control"
                       x-model="form['{{ $key }}']" autocomplete="new-password">
            @elseif ($type === 'textarea')
                <textarea rows="{{ $field['rows'] ?? 2 }}" class="nttu-form-control min-h-[4.5rem]"
                          x-model="form['{{ $key }}']"
                          @if (!empty($field['required'])) required @endif
                          @if(!empty($field['placeholder'])) placeholder="{{ $field['placeholder'] }}" @endif></textarea>
            @else
                <input type="{{ $type === 'email' ? 'email' : 'text' }}"
                       class="nttu-form-control"
                       x-model="form['{{ $key }}']"
                       @if (!empty($field['required'])) required @endif
                       @if(!empty($field['placeholder'])) placeholder="{{ $field['placeholder'] }}" @endif>
            @endif
        </template>
    @endif
</div>
