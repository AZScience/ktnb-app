@props([
    'messageEmpty' => 'Chưa có dữ liệu.',
    'messageFiltered' => 'Không có dữ liệu phù hợp.',
    'messageLoading' => 'Đang tải dữ liệu...',
    'subtitleEmpty' => 'Chưa có bản ghi nào trong bảng này. Hãy thêm mới hoặc chọn điều kiện khác.',
    'subtitleFiltered' => 'Không có bản ghi khớp với bộ lọc hiện tại. Hãy điều chỉnh hoặc xóa bộ lọc.',
    'subtitleLoading' => 'Vui lòng đợi trong giây lát.',
    'icon' => 'table',
    'loading' => null,
    'filtersActive' => null,
    'customEmpty' => null,
    'messageExpr' => null,
    'subtitleExpr' => null,
    'clearAction' => 'clearAllFilters()',
])

@php
    $icons = [
        'calendar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
        'table' => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/><path d="M8 12h6"/><path d="M8 8h6"/><circle cx="16" cy="8" r="2"/>',
        'inbox' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>',
    ];
    $iconMarkup = $icons[$icon] ?? $icons['table'];
    $loadingExpr = $loading ?? 'false';
    $filtersExpr = $filtersActive ?? 'false';
    $customEmptyExpr = $customEmpty ?? 'null';

    // i18n an toàn: dùng window.t — tránh method text() của bảng (mất this → x-text trống).
    $i18n = static fn (string $phpExpr): string => '(function (_k) { if (_k == null || _k === "") return ""; var _fn = (typeof window.t === "function" ? window.t : null); return _fn ? (_fn(_k) || _k) : String(_k); })('.$phpExpr.')';

    $resolvedTitleRaw = '(( '.$loadingExpr.') ? '.json_encode($messageLoading, JSON_UNESCAPED_UNICODE)
        .' : (( '.$filtersExpr.') ? '.json_encode($messageFiltered, JSON_UNESCAPED_UNICODE)
        .' : (( '.$customEmptyExpr.') || '.json_encode($messageEmpty, JSON_UNESCAPED_UNICODE).')))';

    $resolvedSubtitleRaw = '(( '.$loadingExpr.') ? '.json_encode($subtitleLoading, JSON_UNESCAPED_UNICODE)
        .' : (( '.$filtersExpr.') ? '.json_encode($subtitleFiltered, JSON_UNESCAPED_UNICODE)
        .' : '.json_encode($subtitleEmpty, JSON_UNESCAPED_UNICODE).'))';

    $titleExpr = $messageExpr ?? $i18n($resolvedTitleRaw);
    $bodySubtitleExpr = $subtitleExpr ?? $i18n($resolvedSubtitleRaw);
@endphp

<div class="nttu-table-empty-state py-2">
  <div
    class="mx-auto max-w-lg rounded-lg border border-dashed px-4 py-6 text-center"
    :class="({{ $loadingExpr }})
      ? 'border-slate-200 bg-slate-50/90'
      : (({{ $filtersExpr }})
          ? 'border-orange-200 bg-orange-50/60'
          : 'border-slate-200 bg-slate-50/90')"
    role="status"
    aria-live="polite"
  >
    <svg
      class="mx-auto mb-3 h-10 w-10"
      :class="({{ $loadingExpr }}) ? 'text-[var(--nttu-primary)] animate-pulse' : (({{ $filtersExpr }}) ? 'text-orange-400' : 'text-slate-400')"
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
      aria-hidden="true"
    >{!! $iconMarkup !!}</svg>

    {{-- Fallback tĩnh khi Alpine chưa chạy / lỗi biểu thức — luôn có nhãn --}}
    <p
      class="font-semibold text-gray-700"
      :class="({{ $filtersExpr }}) && !({{ $loadingExpr }}) ? 'text-orange-800' : 'text-gray-700'"
      x-text="{{ $titleExpr }}"
    >{{ $messageEmpty }}</p>

    <p
      class="mt-1 text-sm text-gray-500"
      x-show="!({{ $loadingExpr }})"
      x-text="{{ $bodySubtitleExpr }}"
    >{{ $subtitleEmpty }}</p>

    @if ($filtersActive)
      <template x-if="({{ $filtersExpr }}) && !({{ $loadingExpr }})">
        <p class="mt-3 text-sm">
          <button
            type="button"
            @click="{{ $clearAction }}"
            class="inline-flex items-center gap-1.5 rounded-md border border-orange-200 bg-white px-3 py-1.5 text-orange-700 hover:bg-orange-50"
          >
            <x-form-field-icon name="x" tone="destructive" class="h-3.5 w-3.5" />
            <span x-text="(typeof window.t === 'function' ? window.t : (k => k))('Xóa tất cả bộ lọc')">Xóa tất cả bộ lọc</span>
          </button>
        </p>
      </template>
    @endif

    {{ $slot }}
  </div>
</div>
