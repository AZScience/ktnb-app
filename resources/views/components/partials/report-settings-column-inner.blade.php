@props([
    'floatWhen' => 'settingsOpen',
])

<button type="button" data-float-trigger @click.stop="settingsOpen = !settingsOpen" title="Cài đặt hiển thị" class="report-cog-btn inline-flex h-10 w-10 items-center justify-center rounded-none">
    <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
</button>
<div x-cloak x-float="{{ $floatWhen }}" data-float-close="settingsOpen = false"
    class="nttu-report-settings-panel nttu-floating-panel w-64 max-h-[min(24rem,calc(100vh-1rem))] overflow-y-auto rounded-md border bg-white py-2 shadow-xl"
    :class="['good-deeds', 'incident-reports'].includes(config.variant) ? 'border-blue-100' : 'border-gray-200'">
    <p class="report-settings-title border-b border-gray-100 px-3 py-2" x-text="currentSettingsTitle"></p>
    @include('components.partials.column-settings-bulk-actions')
    <template x-for="group in settingsGroups" :key="'sg-' + (group || 'flat')">
        <div>
            <template x-if="group">
                <div class="report-settings-group px-2 py-1.5 bg-gray-50 border-y border-gray-100" x-text="settingsGroupLabel(group)"></div>
            </template>
            <template x-for="col in columnsForSettingsGroup(group)" :key="'set-' + col.key">
                <label class="flex cursor-pointer items-center gap-2 px-3 py-1.5 hover:bg-slate-50 text-xs">
                    <input type="checkbox" class="rounded border-gray-300" :checked="columnVisibility[col.key] !== false" @change="toggleColumn(col.key)">
                    <span x-text="settingsColumnLabel(col)"></span>
                </label>
            </template>
        </div>
    </template>
</div>
