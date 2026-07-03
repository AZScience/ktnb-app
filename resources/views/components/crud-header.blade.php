@props(['title', 'createRoute', 'iconName' => 'folder', 'iconTone' => 'primary'])

<div class="flex flex-wrap justify-between items-center gap-3 px-4 py-3 border-b border-gray-100 mb-0">
    <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
        <x-form-field-icon :name="$iconName" :tone="$iconTone" class="h-5 w-5" />
        <span class="i18n-auto">{{ $title }}</span>
    </h2>
    @if ($createRoute)
        <a href="{{ $createRoute }}"
           class="inline-flex items-center gap-1.5 rounded-md bg-[var(--nttu-table-head)] px-3 py-1.5 text-sm font-medium text-white hover:opacity-90 transition-opacity">
            <x-form-field-icon name="add" tone="lime" class="h-4 w-4" />
            <span class="i18n-auto">Thêm mới</span>
        </a>
    @endif
</div>
