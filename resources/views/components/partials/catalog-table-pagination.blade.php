@props([
    'summaryExpr' => 'paginationSummary()',
    'pageVar' => 'safePage',
    'totalPagesVar' => 'totalPages',
    'rowsPerPageVar' => 'normalizedRowsPerPage',
    'rowsLabelExpr' => "typeof text === 'function' ? text('Số dòng') : 'Số dòng'",
    'rowOptionsExpr' => '[5,10,15,20,25,30,35,40,45,50]',
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 border-t bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between']) }}>
    <p class="text-sm text-gray-500" x-text="{{ $summaryExpr }}"></p>
    <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <span x-text="{{ $rowsLabelExpr }}"></span>
            <input type="number" class="nttu-rows-per-page-input [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" list="{{ $datalistId = uniqid('dl_') }}" :value="{{ $rowsPerPageVar }}" @change="setRowsPerPage($event.target.value)">
<datalist id="{{ $datalistId }}">
    <template x-for="n in {{ $rowOptionsExpr }}" :key="n">
        <option :value="n"></option>
    </template>
</datalist>
        </div>
        <div class="flex items-center gap-1">
            <button type="button" class="h-8 w-8 rounded border text-sm disabled:opacity-40" :disabled="{{ $pageVar }} === 1" @click="goPage(1)">«</button>
            <button type="button" class="h-8 w-8 rounded border text-sm disabled:opacity-40" :disabled="{{ $pageVar }} === 1" @click="goPage({{ $pageVar }} - 1)">‹</button>
            <span class="flex items-center gap-1 text-sm font-medium">
                <input type="number" class="h-8 w-12 rounded border text-center text-sm" :value="{{ $pageVar }}" @change="goPage($event.target.value)"> / <span x-text="{{ $totalPagesVar }}"></span>
            </span>
            <button type="button" class="h-8 w-8 rounded border text-sm disabled:opacity-40" :disabled="{{ $pageVar }} === {{ $totalPagesVar }}" @click="goPage({{ $pageVar }} + 1)">›</button>
            <button type="button" class="h-8 w-8 rounded border text-sm disabled:opacity-40" :disabled="{{ $pageVar }} === {{ $totalPagesVar }}" @click="goPage({{ $totalPagesVar }})">»</button>
        </div>
    </div>
</div>
