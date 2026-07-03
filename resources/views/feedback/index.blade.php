@extends('layouts.nttu')
@section('title', 'Minh chứng ca trực')
@section('page-section', 'Báo cáo thống kê')
@section('page-title', 'Minh chứng ca trực')
@section('page-description', 'Minh chứng báo cáo kết thúc ca trực')
@section('content')

@php $feedbackCanAdd = $nttuCan('/feedback', 'add'); @endphp

<div
    x-data="feedbackFormPage({ email: @js($email), employeeName: @js($employeeName) })"
    class="min-h-[70vh] flex flex-col items-center py-6"
>
    <template x-if="success">
        <div
            class="w-full max-w-2xl mb-4 rounded-xl border px-6 py-8 text-center"
            :class="sheetWarning ? 'border-amber-200 bg-amber-50' : 'border-green-200 bg-green-50'"
        >
            <div
                class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full text-2xl"
                :class="sheetWarning ? 'bg-amber-100 text-amber-600' : 'bg-green-100 text-green-600'"
                x-text="sheetWarning ? '!' : '✓'"
            ></div>
            <h3
                class="text-lg font-bold"
                :class="sheetWarning ? 'text-amber-800' : 'text-green-800'"
                x-text="sheetWarning ? 'Đã lưu hệ thống — chưa ghi Google Sheet' : 'Đã gửi minh chứng thành công!'"
            ></h3>
            <p
                class="text-sm mt-1"
                :class="sheetWarning ? 'text-amber-800' : 'text-green-700'"
                x-text="successMessage || 'Dữ liệu đã được lưu trên hệ thống.'"
            ></p>
        </div>
    </template>

    <form @submit.prevent="submitForm($event)" method="POST" action="{{ route('feedback.store') }}" enctype="multipart/form-data"
        class="w-full max-w-2xl bg-white rounded-xl shadow-lg border overflow-hidden">
        @csrf
        @unless($feedbackCanAdd)
            <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 m-6 text-sm text-amber-800">
                Bạn không có quyền gửi minh chứng ca trực.
            </div>
        @else
        <div class="h-2 bg-gradient-to-r from-[var(--nttu-primary)] to-emerald-500"></div>
        <div class="p-6 md:p-8 space-y-6">
            <div class="border-b pb-4">
                <h2 class="text-xl font-bold text-slate-800">Biểu mẫu minh chứng ca trực</h2>
                <p class="text-sm text-slate-500 mt-1">Dấu <span class="text-red-500">*</span> là bắt buộc</p>
            </div>

            <div class="space-y-4">
                <div>
                    <x-form-label icon="mail" tone="blue" class="mb-1">Email *</x-form-label>
                    <input name="email" type="email" required x-model="email" class="w-full border-0 border-b-2 border-slate-200 focus:border-[var(--nttu-primary)] px-0 py-2 text-sm bg-transparent outline-none">
                </div>
                <div>
                    <x-form-label icon="user-cog" tone="blue" class="mb-1">Cán bộ thực hiện kiểm tra *</x-form-label>
                    <input name="employee_name" required x-model="employeeName" class="w-full border-0 border-b-2 border-slate-200 focus:border-[var(--nttu-primary)] px-0 py-2 text-sm bg-transparent outline-none">
                </div>
                <div>
                    <x-form-label icon="calendar" tone="orange" class="mb-1">Ngày thực hiện / báo cáo *</x-form-label>
                    <input name="shift_date" type="date" required x-model="shiftDate" class="w-full border-0 border-b-2 border-slate-200 focus:border-[var(--nttu-primary)] px-0 py-2 text-sm bg-transparent outline-none">
                </div>
            </div>

            <template x-for="field in fields" :key="field.key">
                <div class="space-y-2 pt-2 border-t">
                    <label class="block text-sm font-semibold text-slate-800" x-text="field.label"></label>
                    <p class="text-xs text-slate-400" x-text="field.hint"></p>
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border-2 border-dashed border-slate-200 px-4 py-3 text-sm text-slate-500 hover:border-[var(--nttu-primary)] hover:text-[var(--nttu-primary)] transition-colors">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        <span>Thêm tệp (ảnh, video, tài liệu — có thể chọn nhiều)</span>
                        <input type="file" :name="field.key + '[]'" multiple :accept="proofFileAccept" class="hidden" @change="addFiles(field.key, $event)">
                    </label>
                    <div class="flex flex-wrap gap-2" x-show="files[field.key]?.length">
                        <template x-for="(file, idx) in files[field.key]" :key="field.key + '-' + idx + '-' + file.name">
                            <div class="relative group w-20">
                                <template x-if="isImageFile(file)">
                                    <img :src="previewUrl(file)" class="h-16 w-16 rounded-lg object-cover border" alt="">
                                </template>
                                <template x-if="isVideoFile(file)">
                                    <div class="flex h-16 w-16 items-center justify-center rounded-lg border bg-purple-50 text-purple-600">
                                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    </div>
                                </template>
                                <template x-if="isDocumentFile(file)">
                                    <div class="flex h-16 w-16 items-center justify-center rounded-lg border bg-emerald-50 text-emerald-600">
                                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                </template>
                                <button type="button" @click="removeFile(field.key, idx)" class="absolute -top-1 -right-1 h-5 w-5 rounded-full bg-red-500 text-white text-xs opacity-0 group-hover:opacity-100">×</button>
                                <p class="text-[9px] text-slate-500 truncate w-20 mt-0.5" x-text="file.name"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <div class="flex flex-wrap gap-3 pt-4 border-t">
                <x-nttu-button type="submit" action="send" class="flex-1 min-w-[140px]" x-bind:disabled="submitting">
                    <span x-text="submitting ? 'Đang gửi...' : 'GỬI MINH CHỨNG'"></span>
                </x-nttu-button>
                <x-nttu-button type="button" action="clear" variant="outline" @click="clearAll()">Xóa hết câu trả lời</x-nttu-button>
            </div>
        </div>
        @endunless
    </form>
</div>
@endsection
