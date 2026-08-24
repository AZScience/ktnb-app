@props([
    'params' => [],
    'saveUrl' => '',
    'verifyUrls' => [],
    'lecturerPortalUrl' => '',
])

@php
    $tabs = [
        ['id' => 'interface', 'label' => 'Giao diện', 'icon' => 'layout', 'tone' => 'rose'],
        ['id' => 'integration', 'label' => 'Tích hợp', 'icon' => 'database', 'tone' => 'green'],
        ['id' => 'lcms', 'label' => 'Trang LCMS', 'icon' => 'globe', 'tone' => 'orange'],
        ['id' => 'lecturer-portal', 'label' => 'Cổng Giảng viên', 'icon' => 'user-circle', 'tone' => 'indigo'],
        ['id' => 'email', 'label' => 'Email', 'icon' => 'mail', 'tone' => 'blue'],
        ['id' => 'ai', 'label' => 'AI', 'icon' => 'sparkles', 'tone' => 'purple'],
        ['id' => 'evidence', 'label' => 'Minh chứng', 'icon' => 'camera', 'tone' => 'amber'],
    ];
@endphp

@php $pagePerms = $nttuPage('/settings/parameters'); @endphp

<div
    x-data="systemParametersPage({
        params: @js($params),
        saveUrl: @js($saveUrl),
        verifyUrls: @js($verifyUrls),
        lecturerPortalUrl: @js($lecturerPortalUrl),
        canEdit: @js($pagePerms['edit']),
    })"
    class="space-y-4"
>
    <div x-show="toast" x-cloak
        class="fixed top-4 right-4 z-[70] max-w-md rounded-lg px-4 py-3 text-sm text-white shadow-lg flex items-center justify-between gap-3"
        :class="toast?.type === 'success' ? 'bg-green-600' : 'bg-red-600'">
        <span x-text="toast?.message"></span>
        <button type="button" @click="toast = null" class="shrink-0 rounded-full p-1 text-white/70 hover:bg-black/10 hover:text-white transition-colors" title="Đóng">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <div x-show="isChanged" x-cloak class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        Bạn có thay đổi chưa lưu. Hãy nhấn <strong>Lưu tất cả thay đổi</strong> ở cuối trang trước khi rời đi.
    </div>

    <div class="nttu-card shadow-sm overflow-hidden">
        <div class="flex flex-col gap-4 border-b px-4 py-4 md:flex-row md:items-center md:justify-between md:px-6">
            <h2 class="flex items-center gap-2 text-xl font-semibold text-gray-900">
                <x-form-field-icon name="settings" tone="lime" class="h-6 w-6" />
                <span>Cấu hình tham số</span>
            </h2>
            <div class="flex flex-wrap gap-1 rounded-lg bg-slate-200/60 p-1">
                @foreach ($tabs as $tabItem)
                    <button type="button" @click="tab = '{{ $tabItem['id'] }}'"
                        class="inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-xs font-semibold transition"
                        :class="tab === '{{ $tabItem['id'] }}' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:bg-white/70'">
                        <x-form-field-icon :name="$tabItem['icon']" :tone="$tabItem['tone']" class="h-4 w-4" />
                        <span>{{ $tabItem['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Giao diện --}}
        <div x-show="tab === 'interface'" x-cloak class="space-y-8 p-6">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-500/10">
                    <x-form-field-icon name="layout" tone="rose" class="h-5 w-5" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Cấu hình Giao diện</h3>
                    <p class="mt-0.5 text-sm text-gray-500">Thiết lập logo, trang đăng nhập và thông tin liên hệ hiển thị trên hệ thống.</p>
                </div>
            </div>

            {{-- 1. Thương hiệu ứng dụng --}}
            <section class="overflow-hidden rounded-xl border border-slate-200">
                <div class="flex items-center gap-3 border-b border-slate-200 bg-slate-50/80 px-5 py-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-rose-500 text-xs font-bold text-white">1</span>
                    <div>
                        <h4 class="text-sm font-bold text-gray-900">Thương hiệu ứng dụng</h4>
                        <p class="text-xs text-gray-500">Logo hiển thị trên thanh điều hướng và tiêu đề trang.</p>
                    </div>
                </div>
                <div class="grid gap-6 p-5 lg:grid-cols-5">
                    <div class="space-y-4 lg:col-span-3">
                        <div class="space-y-2">
                            <x-form-label icon="layout" tone="rose">Logo / Banner</x-form-label>
                            <input type="text" class="nttu-form-control w-full" placeholder="https://... hoặc tải ảnh bên dưới"
                                x-model="localParams.bannerUrl">
                            <p class="text-xs text-gray-500">Nên dùng PNG nền trong suốt. Kích thước gợi ý: 800×400 px.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50" :class="isUploadingImage ? 'pointer-events-none opacity-50' : ''">
                                <x-form-field-icon name="upload" tone="blue" class="h-4 w-4" />
                                <span x-text="isUploadingImage ? 'Đang xử lý...' : 'Tải ảnh lên'"></span>
                                <input type="file" accept="image/*" class="hidden" @change="handleImageUpload($event, 'bannerUrl', 800, 400, 0.8)">
                            </label>
                            <button type="button" x-show="hasBanner()" @click="patchParam('bannerUrl', '')"
                                class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-red-600 hover:bg-red-50">
                                <x-form-field-icon name="delete" tone="destructive" class="h-4 w-4" />
                                Xóa ảnh
                            </button>
                        </div>
                        <div class="max-w-xs">
                            <x-form-label icon="activity" tone="orange">Chiều cao hiển thị (px)</x-form-label>
                            <input type="number" min="24" max="120" step="1" class="nttu-form-control w-full" x-model="localParams.bannerHeight">
                            <p class="mt-1 text-xs text-gray-500">Phạm vi khuyến nghị: 32–80 px.</p>
                        </div>
                    </div>
                    <div class="lg:col-span-2">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Xem trước Sidebar Menu</p>
                    <div class="overflow-hidden rounded-lg border bg-white shadow-sm flex h-40">
                        <!-- Sidebar preview -->
                        <div class="w-48 shrink-0 border-r bg-white flex flex-col">
                            <div class="flex h-16 shrink-0 items-center justify-center overflow-hidden border-b px-2">
                                <template x-if="hasBanner()">
                                    <img :src="localParams.bannerUrl" alt="Logo" class="w-full object-contain" :style="height:px; max-height:px">
                                </template>
                                <template x-if="!hasBanner()">
                                    <span class="text-xl font-bold text-[var(--nttu-primary)]">N</span>
                                </template>
                            </div>
                            <div class="flex-1 p-3 space-y-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-4 w-4 rounded bg-indigo-500"></div>
                                    <div class="h-4 flex-1 rounded bg-gray-100"></div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-4 w-4 rounded bg-orange-500"></div>
                                    <div class="h-4 w-2/3 rounded bg-gray-100"></div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-4 w-4 rounded bg-green-500"></div>
                                    <div class="h-4 w-3/4 rounded bg-gray-100"></div>
                                </div>
                            </div>
                        </div>
                        <!-- Main content preview -->
                        <div class="flex-1 bg-slate-50 flex flex-col">
                            <div class="h-12 shrink-0 border-b bg-white flex items-center justify-between px-4">
                                <div class="h-4 w-4 rounded bg-gray-200"></div>
                                <div class="h-6 w-6 rounded-full bg-blue-100"></div>
                            </div>
                            <div class="flex-1 p-4 flex items-center justify-center text-xs italic text-gray-400">
                                Nội dung trang
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </section>

            {{-- 2. Trang đăng nhập --}}
            <section class="overflow-hidden rounded-xl border border-slate-200">
                <div class="flex items-center gap-3 border-b border-slate-200 bg-slate-50/80 px-5 py-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-500 text-xs font-bold text-white">2</span>
                    <div>
                        <h4 class="text-sm font-bold text-gray-900">Trang đăng nhập</h4>
                        <p class="text-xs text-gray-500">Hình nền và câu trích dẫn ở màn hình đăng nhập.</p>
                    </div>
                </div>
                <div class="grid gap-6 p-5 xl:grid-cols-2">
                    <div class="space-y-5">
                        <div class="space-y-2">
                            <x-form-label icon="camera" tone="blue">Hình nền (nửa trái màn hình)</x-form-label>
                            <input type="text" class="nttu-form-control w-full" placeholder="https://... hoặc tải ảnh"
                                x-model="localParams.loginImageUrl">
                            <p class="text-xs text-gray-500">Ảnh ngang, gợi ý 1200×800 px. Hiển thị trên desktop.</p>
                            <div class="flex flex-wrap gap-2">
                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50" :class="isUploadingImage ? 'pointer-events-none opacity-50' : ''">
                                    <x-form-field-icon name="upload" tone="blue" class="h-4 w-4" />
                                    Tải ảnh lên
                                    <input type="file" accept="image/*" class="hidden" @change="handleImageUpload($event, 'loginImageUrl', 1200, 800, 0.7)">
                                </label>
                                <button type="button" x-show="hasLoginImage()" @click="patchParam('loginImageUrl', '')"
                                    class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <x-form-field-icon name="delete" tone="destructive" class="h-4 w-4" />
                                    Xóa ảnh
                                </button>
                            </div>
                        </div>
                        <div>
                            <x-form-label icon="note" tone="teal">Câu trích dẫn</x-form-label>
                            <textarea rows="3" class="nttu-form-control w-full" x-model="localParams.loginQuote" placeholder="Nhập câu trích dẫn truyền cảm hứng..."></textarea>
                        </div>
                        <div>
                            <x-form-label icon="user" tone="indigo">Tác giả trích dẫn</x-form-label>
                            <input type="text" class="nttu-form-control w-full" x-model="localParams.loginQuoteAuthor" placeholder="Tên tác giả hoặc nguồn trích dẫn">
                        </div>
                    </div>
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Xem trước trang đăng nhập</p>
                        <div class="overflow-hidden rounded-lg border bg-white shadow-sm">
                            <div class="grid min-h-[220px] grid-cols-2">
                                <div class="relative overflow-hidden bg-slate-200">
                                    <template x-if="hasLoginImage()">
                                        <img :src="localParams.loginImageUrl" alt="Login bg" class="absolute inset-0 h-full w-full object-cover">
                                    </template>
                                    <div class="absolute inset-0 bg-gradient-to-t from-zinc-900/70 to-transparent"></div>
                                    <div class="absolute inset-x-0 bottom-0 space-y-1 p-3 text-[10px] leading-snug text-white">
                                        <p class="italic">&ldquo;<span x-text="localParams.loginQuote || 'Câu trích dẫn...'"></span>&rdquo;</p>
                                        <p class="text-right font-semibold">— <span x-text="localParams.loginQuoteAuthor || 'Tác giả'"></span></p>
                                    </div>
                                </div>
                                <div class="flex flex-col items-center justify-center gap-2 bg-white p-4">
                                    <div class="h-6 w-16 rounded bg-slate-100"></div>
                                    <div class="h-2 w-20 rounded bg-slate-100"></div>
                                    <div class="mt-2 h-7 w-full max-w-[88px] rounded bg-[var(--nttu-table-head)]/80"></div>
                                    <span class="text-[9px] text-gray-400">Form đăng nhập</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- 3. Liên hệ & hỗ trợ --}}
            <section class="overflow-hidden rounded-xl border border-slate-200">
                <div class="flex items-center gap-3 border-b border-slate-200 bg-slate-50/80 px-5 py-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-teal-500 text-xs font-bold text-white">3</span>
                    <div>
                        <h4 class="text-sm font-bold text-gray-900">Liên hệ &amp; hỗ trợ</h4>
                        <p class="text-xs text-gray-500">Thông tin hiển thị khi người dùng cần trợ giúp hoặc liên hệ quản trị.</p>
                    </div>
                </div>
                <div class="grid gap-4 p-5 md:grid-cols-2">
                    <div>
                        <x-form-label icon="mail" tone="blue">Email quản trị</x-form-label>
                        <input type="email" class="nttu-form-control w-full" x-model="localParams.adminEmail" placeholder="admin@ntt.edu.vn">
                        <p class="mt-1 text-xs text-gray-500">Email nhận yêu cầu hỗ trợ kỹ thuật.</p>
                    </div>
                    <div>
                        <x-form-label icon="phone" tone="green">Số điện thoại hỗ trợ</x-form-label>
                        <input type="text" class="nttu-form-control w-full" x-model="localParams.supportPhone" placeholder="0xxx xxx xxx">
                    </div>
                    <div class="md:col-span-2">
                        <x-form-label icon="tag" tone="primary">Website</x-form-label>
                        <input type="url" class="nttu-form-control w-full" x-model="localParams.website" placeholder="https://ntt.edu.vn">
                    </div>
                </div>
            </section>
        </div>

        {{-- Tích hợp --}}
        <div x-show="tab === 'integration'" x-cloak class="space-y-4 p-6">
            <div class="flex items-center gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-green-500/10">
                    <x-form-field-icon name="database" tone="green" class="h-5 w-5" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Tích hợp Google Sheets (AI Assistant)</h3>
                    <p class="text-sm text-gray-500">Cấu hình kết nối để Trợ lý AI có thể đọc dữ liệu nội bộ.</p>
                </div>
            </div>
            <div>
                <x-form-label icon="tag" tone="green">Google Sheet ID</x-form-label>
                <input type="text" class="nttu-form-control w-full" x-model="localParams.googleSheetId" placeholder="1a2b3c4d5e6f7g8h9i0j...">
            </div>
            <div>
                <x-form-label icon="file-text" tone="blue">Tên Tab Hỏi đáp (FAQ)</x-form-label>
                <input type="text" class="nttu-form-control w-full" x-model="localParams.faqSheetTabName" placeholder="FAQ">
            </div>
            <hr class="border-slate-200">
            <div>
                <x-form-label icon="mail" tone="teal">Service Account Email</x-form-label>
                <input type="text" class="nttu-form-control w-full" x-model="localParams.googleServiceAccountEmail" placeholder="example@project-id.iam.gserviceaccount.com">
            </div>
            <div>
                <x-form-label icon="lock" tone="orange">Private Key</x-form-label>
                <textarea rows="5" class="nttu-form-control w-full font-mono text-xs" x-model="localParams.googlePrivateKey"
                    placeholder="-----BEGIN PRIVATE KEY-----&#10;...&#10;-----END PRIVATE KEY-----"></textarea>
            </div>
            <div class="flex justify-end">
                <button type="button" @click="verifyGoogle()" :disabled="isVerifyingGoogle"
                    class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-[var(--nttu-primary)] hover:bg-slate-50 disabled:opacity-50">
                    <x-form-field-icon name="refresh" tone="green" class="h-4 w-4" />
                    <span x-text="isVerifyingGoogle ? 'Đang kiểm tra...' : 'Kiểm tra kết nối Google Sheet'"></span>
                </button>
            </div>

            <hr class="my-6 border-slate-200">

            <div class="flex items-center gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10">
                    <x-form-field-icon name="layout" tone="green" class="h-5 w-5" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Google Sheet – Báo cáo tổng hợp học kỳ</h3>
                    <p class="text-sm text-gray-500">Dùng cho đẩy dữ liệu từ Người tốt việc tốt, Tiếp nhận yêu cầu và Tiếp nhận đơn thư (tách với Sheet báo cáo cuối ngày).</p>
                </div>
            </div>
            <div>
                <x-form-label icon="tag" tone="green">Google Sheet ID (báo cáo tổng hợp)</x-form-label>
                <input type="text" class="nttu-form-control w-full" x-model="localParams.summaryReportGoogleSheetId"
                    placeholder="18eUrHg297CBKNRkpIqLzQICatmmDeGNK-D16minE7Uo">
                <p class="mt-1 text-xs text-gray-500">Sheet mặc định: <strong>TỔNG HỢP GHI NHẬN KIỂM TRA HỌC KỲ</strong> (<code>18eUrHg297CBKNRkpIqLzQICatmmDeGNK-D16minE7Uo</code>). Có thể dán cả URL Google Sheet. Dùng chung Service Account bên trên.</p>
            </div>
            <div class="flex justify-end">
                <button type="button" @click="verifySummaryGoogle()" :disabled="isVerifyingSummaryGoogle"
                    class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-[var(--nttu-primary)] hover:bg-slate-50 disabled:opacity-50">
                    <x-form-field-icon name="refresh" tone="green" class="h-4 w-4" />
                    <span x-text="isVerifyingSummaryGoogle ? 'Đang kiểm tra...' : 'Kiểm tra Sheet tổng hợp học kỳ'"></span>
                </button>
            </div>
        </div>

        {{-- Trang LCMS --}}
        <div x-show="tab === 'lcms'" x-cloak class="space-y-4 p-6">
            <div class="flex items-center gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-orange-500/10">
                    <x-form-field-icon name="globe" tone="orange" class="h-5 w-5" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Trang E-Learning (LCMS)</h3>
                    <p class="text-sm text-gray-500">Cấu hình tài khoản đăng nhập để cào dữ liệu và lấy Link Google Meet từ Moodle.</p>
                </div>
            </div>
            <div>
                <x-form-label icon="link" tone="blue">Đường dẫn trang LCMS (URL)</x-form-label>
                <input type="text" class="nttu-form-control w-full" x-model="localParams.lcmsUrl" placeholder="https://lcms.ntt.edu.vn">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-form-label icon="user" tone="orange">Tên đăng nhập LCMS</x-form-label>
                    <input type="text" class="nttu-form-control w-full" x-model="localParams.lcmsUser" placeholder="Tài khoản...">
                </div>
                <div>
                    <x-form-label icon="key" tone="red">Mật khẩu LCMS</x-form-label>
                    <input type="password" class="nttu-form-control w-full" x-model="localParams.lcmsPass" placeholder="Mật khẩu...">
                </div>
            </div>
            <div class="rounded bg-slate-50 p-4 border border-slate-200 mt-2 text-sm text-slate-700">
                <p><strong>Lưu ý:</strong> Mật khẩu bạn nhập ở đây sẽ được lưu trữ dưới dạng bản rõ (plaintext) trong cơ sở dữ liệu để hệ thống có thể giả lập thao tác đăng nhập. Vui lòng sử dụng tài khoản chung / tài khoản dành riêng cho bot nếu có thể.</p>
            </div>
        </div>

        {{-- Cổng Giảng viên --}}
        <div x-show="tab === 'lecturer-portal'" x-cloak class="space-y-4 p-6">
            <div class="flex items-center gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-500/10">
                    <x-form-field-icon name="user-circle" tone="indigo" class="h-5 w-5" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Cổng Check-in Giảng viên</h3>
                    <p class="text-sm text-gray-500">Cấu hình đăng nhập Google cho trang công khai <strong>/lecturer-portal</strong> (TH ngoài trường).</p>
                </div>
            </div>

            <div class="rounded-xl border px-4 py-3 text-sm"
                :class="hasGoogleClientId() ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-900'">
                <span x-text="hasGoogleClientId()
                    ? 'Đã có Google Client ID — giảng viên có thể đăng nhập Google trên cổng check-in.'
                    : 'Chưa cấu hình Google Client ID — cổng check-in sẽ báo lỗi cho giảng viên cho đến khi bạn lưu Client ID bên dưới.'"></span>
            </div>

            <div>
                <x-form-label icon="key" tone="indigo">Google Client ID (OAuth Web)</x-form-label>
                <input type="text" class="nttu-form-control w-full font-mono text-sm" x-model="localParams.googleClientId"
                    placeholder="123456789-xxxx.apps.googleusercontent.com">
                <p class="mt-1 text-xs text-gray-500">Lấy từ Google Cloud Console → mục <strong>Clients</strong> (ứng dụng Web). Chỉ cần Client ID, không cần Client Secret.</p>
            </div>

            <div>
                <x-form-label icon="mail" tone="blue">Miền email Google được phép</x-form-label>
                <input type="text" class="nttu-form-control w-full" x-model="localParams.lecturerPortalEmailDomains"
                    placeholder="nttu.edu.vn,ntt.edu.vn,gmail.com">
                <p class="mt-1 text-xs text-gray-500">Phân tách bằng dấu phẩy. Email có trong <strong>Quản lý giảng viên</strong> luôn được chấp nhận dù miền khác.</p>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2">
                <a :href="lecturerPortalUrl" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-slate-50">
                    <x-form-field-icon name="eye" tone="indigo" class="h-4 w-4" />
                    Mở cổng check-in ↗
                </a>
                <button type="button" @click="verifyLecturerPortal()" :disabled="isVerifyingLecturerPortal"
                    class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-[var(--nttu-primary)] hover:bg-slate-50 disabled:opacity-50">
                    <x-form-field-icon name="refresh" tone="indigo" class="h-4 w-4" />
                    <span x-text="isVerifyingLecturerPortal ? 'Đang kiểm tra...' : 'Kiểm tra cấu hình'"></span>
                </button>
            </div>

            <div class="rounded-xl border border-indigo-100 bg-indigo-50/80 p-4 text-xs leading-relaxed text-indigo-900">
                <p class="mb-2 font-bold">Hướng dẫn tạo Client ID (Google đã đổi menu — 2025/2026)</p>
                <p class="mb-2">Google không còn dùng menu cũ <em>APIs &amp; Services → Credentials → Create Credentials</em> trên nhiều tài khoản. Dùng một trong các cách sau:</p>

                <p class="mb-1 font-semibold">Cách 1 — Link trực tiếp (nhanh nhất)</p>
                <ol class="mb-3 list-decimal space-y-1.5 pl-4">
                    <li>Mở <a href="https://console.cloud.google.com/auth/clients" target="_blank" rel="noopener" class="font-medium underline">console.cloud.google.com/auth/clients</a> (trang <strong>Clients</strong>)</li>
                    <li>Chọn đúng <strong>Project</strong> ở thanh trên (nếu chưa có project → tạo project trước)</li>
                    <li>Nếu thấy <strong>Get started</strong> / <strong>Bắt đầu</strong> → làm bước đồng ý OAuth (Branding) rồi quay lại trang Clients</li>
                    <li>Bấm <strong>+ Create client</strong> (hoặc <strong>Tạo client</strong>)</li>
                    <li>Loại ứng dụng: <strong>Web application</strong> / <strong>Ứng dụng web</strong></li>
                    <li>Mục <strong>Authorized JavaScript origins</strong> / <strong>Nguồn gốc JavaScript được ủy quyền</strong> — thêm URL (không có dấu <code>/</code> cuối):
                        <code class="rounded bg-white/80 px-1">http://127.0.0.1:8000</code>,
                        <code class="rounded bg-white/80 px-1">http://localhost:8000</code>,
                        <code class="rounded bg-white/80 px-1">https://kiemtranoibo-ccks.ntt.edu.vn</code>
                    </li>
                    <li><strong>Authorized redirect URIs</strong>: để trống (cổng này không cần)</li>
                    <li>Bấm <strong>Create</strong> → copy <strong>Client ID</strong> (dạng <code>xxx.apps.googleusercontent.com</code>)</li>
                </ol>

                <p class="mb-1 font-semibold">Cách 2 — Tìm bằng ô search trên Console</p>
                <ol class="mb-3 list-decimal space-y-1 pl-4">
                    <li>Vào <a href="https://console.cloud.google.com/" target="_blank" rel="noopener" class="font-medium underline">console.cloud.google.com</a></li>
                    <li>Ở thanh tìm kiếm trên cùng, gõ: <strong>Google Auth Platform</strong> hoặc <strong>Clients</strong></li>
                    <li>Chọn kết quả <strong>Google Auth Platform → Clients</strong></li>
                </ol>

                <p class="mb-1 font-semibold">Cách 3 — Menu trái (nếu vẫn thấy giao diện cũ)</p>
                <ol class="list-decimal space-y-1 pl-4">
                    <li><strong>APIs &amp; Services</strong> → <strong>Credentials</strong></li>
                    <li><strong>+ Create Credentials</strong> → <strong>OAuth client ID</strong> → <strong>Web application</strong></li>
                </ol>

                <p class="mt-3 rounded-lg border border-indigo-200 bg-white/60 px-3 py-2 text-indigo-800">
                    Sau khi có Client ID: dán vào ô trên → <strong>Lưu tất cả thay đổi</strong> → bấm <strong>Mở cổng check-in ↗</strong> để thử.
                    Giá trị trong <code>.env</code> vẫn dùng được nếu chưa lưu ở đây.
                </p>
            </div>

            <div class="rounded-xl border border-red-200 bg-red-50/80 p-4 text-xs leading-relaxed text-red-900">
                <p class="mb-2 font-bold">Lỗi 「origin_mismatch」 / Error 400</p>
                <p class="mb-2">Google báo <em>“đăng ký mã nguồn JavaScript”</em> khi URL trên trình duyệt <strong>không khớp</strong> với <strong>Authorized JavaScript origins</strong>.</p>
                <ol class="list-decimal space-y-1 pl-4">
                    <li>Mở <a href="https://console.cloud.google.com/auth/clients" target="_blank" rel="noopener" class="font-medium underline">Clients</a> → sửa OAuth client Web</li>
                    <li>Thêm <strong>đúng</strong> origin (xem dòng “Origin trang” trên cổng check-in), ví dụ:
                        <code class="rounded bg-white px-1">http://127.0.0.1:8000</code>,
                        <code class="rounded bg-white px-1">http://localhost:8000</code>,
                        <code class="rounded bg-white px-1">https://kiemtranoibo-ccks.ntt.edu.vn</code>
                    </li>
                    <li>Không thêm path (<code>/lecturer-portal</code>), không dấu <code>/</code> cuối</li>
                    <li>Save → đợi 1–2 phút → thử lại trên <strong>cùng URL</strong> đã khai báo</li>
                </ol>
                <p class="mt-2">Nếu app OAuth đang <strong>Testing</strong>, thêm <code>ngviphuc@gmail.com</code> vào Audience → Test users.</p>
            </div>
        </div>

        {{-- Email --}}
        <div x-show="tab === 'email'" x-cloak class="space-y-4 p-6">
            <div class="flex items-center gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-500/10">
                    <x-form-field-icon name="mail" tone="blue" class="h-5 w-5" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Cấu hình SMTP Email</h3>
                    <p class="text-sm text-gray-500">Cài đặt để hệ thống có thể gửi thông báo qua Email khi có tin nhắn mới.</p>
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-form-label icon="tag" tone="blue">SMTP Host</x-form-label>
                    <input type="text" class="nttu-form-control w-full" x-model="localParams.smtpHost" placeholder="smtp.gmail.com">
                </div>
                <div>
                    <x-form-label icon="hash" tone="indigo">SMTP Port</x-form-label>
                    <input type="text" class="nttu-form-control w-full" x-model="localParams.smtpPort" placeholder="587">
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-form-label icon="mail" tone="blue">Email đăng nhập (Username)</x-form-label>
                    <input type="email" class="nttu-form-control w-full" x-model="localParams.smtpUser">
                </div>
                <div>
                    <x-form-label icon="lock" tone="orange">Mật khẩu ứng dụng (App Password)</x-form-label>
                    <input type="password" class="nttu-form-control w-full" x-model="localParams.smtpPass">
                </div>
            </div>
            <div>
                <x-form-label icon="user" tone="primary">Tên người gửi hiển thị</x-form-label>
                <input type="text" class="nttu-form-control w-full" x-model="localParams.smtpFromName" placeholder="Phòng Kiểm tra nội bộ">
            </div>
            <div>
                <button type="button" @click="testEmail()" :disabled="isTestingEmail"
                    class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-[var(--nttu-primary)] hover:bg-slate-50 disabled:opacity-50">
                    <x-form-field-icon name="send" tone="blue" class="h-4 w-4" />
                    <span x-text="isTestingEmail ? 'Đang gửi...' : 'Gửi thử Email kiểm tra kết nối'"></span>
                </button>
            </div>
            <div class="rounded-xl border border-blue-100 bg-blue-50/80 p-4 text-xs leading-relaxed text-blue-800">
                <p class="mb-1 font-bold">Hướng dẫn</p>
                <ul class="list-disc space-y-1 pl-4">
                    <li>Nếu dùng Gmail, hãy bật 2FA và tạo Mật khẩu ứng dụng (App Password).</li>
                    <li>Host thường là <strong>smtp.gmail.com</strong> và Port là <strong>587</strong>.</li>
                    <li>Hệ thống dùng SMTP này khi gửi <strong>tin nhắn nội bộ</strong> và email <strong>Quên mật khẩu</strong> trên trang đăng nhập.</li>
                </ul>
            </div>
        </div>

        {{-- AI --}}
        <div x-show="tab === 'ai'" x-cloak class="space-y-4 p-6">
            <div class="flex items-center gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-500/10">
                    <x-form-field-icon name="sparkles" tone="purple" class="h-5 w-5" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Cấu hình Trí tuệ nhân tạo (AI)</h3>
                    <p class="text-sm text-gray-500">Thiết lập các tham số cho trợ lý AI và các chức năng tự động hóa.</p>
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-form-label icon="tag" tone="purple">Nhà cung cấp AI</x-form-label>
                    <input type="text" class="nttu-form-control w-full" x-model="localParams.aiProvider" placeholder="google, openai...">
                </div>
                <div>
                    <x-form-label icon="tag" tone="indigo">Model Name</x-form-label>
                    <select class="nttu-form-control w-full" x-model="localParams.aiModel">
                        <template x-for="opt in aiModelOptions" :key="opt.value">
                            <option :value="opt.value" x-text="opt.label"></option>
                        </template>
                    </select>
                </div>
            </div>
            <div>
                <x-form-label icon="key" tone="amber">AI API Key</x-form-label>
                <input type="password" class="nttu-form-control w-full" x-model="localParams.aiApiKey" placeholder="Dán API Key của bạn vào đây">
            </div>
            <div>
                <x-form-label icon="note" tone="teal">System Prompt (Chỉ dẫn hệ thống)</x-form-label>
                <textarea rows="5" class="nttu-form-control w-full" x-model="localParams.aiSystemPrompt"
                    placeholder="Mô tả cách AI nên cư xử và trả lời..."></textarea>
            </div>
            <div class="flex justify-end">
                <button type="button" @click="verifyAi()" :disabled="isVerifyingAi"
                    class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-[var(--nttu-primary)] hover:bg-slate-50 disabled:opacity-50">
                    <x-form-field-icon name="refresh" tone="purple" class="h-4 w-4" />
                    <span x-text="isVerifyingAi ? 'Đang kiểm tra...' : 'Kiểm tra kết nối AI'"></span>
                </button>
            </div>
            <div class="rounded-xl border border-purple-100 bg-purple-50/80 p-4 text-xs leading-relaxed text-purple-800">
                <p class="mb-1 font-bold">Lưu ý</p>
                <p>Các thông số này sẽ được sử dụng cho trợ lý AI hỗ trợ phân tích báo cáo và trả lời câu hỏi nghiệp vụ kiểm tra nội bộ. Hãy cẩn thận khi thay đổi System Prompt vì nó ảnh hưởng trực tiếp đến chất lượng câu trả lời.</p>
            </div>
        </div>

        {{-- Minh chứng --}}
        <div x-show="tab === 'evidence'" x-cloak class="space-y-4 p-6">
            <div class="flex items-center gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-500/10">
                    <x-form-field-icon name="camera" tone="amber" class="h-5 w-5" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Minh chứng Báo cáo kết thúc ca trực</h3>
                    <p class="text-sm text-gray-500">Cấu hình nơi lưu trữ file và dữ liệu báo cáo minh chứng.</p>
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-form-label icon="mail" tone="teal">Service Account Email</x-form-label>
                    <input type="text" class="nttu-form-control w-full" x-model="localParams.evidenceServiceAccountEmail"
                        placeholder="minh-chung@project-id.iam.gserviceaccount.com">
                </div>
                <div>
                    <x-form-label icon="lock" tone="orange">Private Key</x-form-label>
                    <textarea rows="4" class="nttu-form-control w-full font-mono text-xs" x-model="localParams.evidencePrivateKey"
                        placeholder="-----BEGIN PRIVATE KEY-----"></textarea>
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-form-label icon="tag" tone="amber">Google Sheet ID (Lưu dữ liệu báo cáo)</x-form-label>
                    <input type="text" class="nttu-form-control w-full" x-model="localParams.feedbackSheetId" placeholder="1lneRw5i8mq651ezFpjIquKevK-aR6J4WPRgMCPo2vNA">
                </div>
                <div>
                    <x-form-label icon="file-text" tone="blue">Tên Tab trong Sheet</x-form-label>
                    <input type="text" class="nttu-form-control w-full" x-model="localParams.feedbackTabName" placeholder="Biểu mẫu 1">
                </div>
            </div>
            <hr class="border-slate-200">
            <div>
                <x-form-label icon="folder" tone="pink">Google Drive Folder ID (Lưu ảnh minh chứng)</x-form-label>
                <input type="text" class="nttu-form-control w-full" x-model="localParams.googleDriveFolderId" placeholder="1Xjw3fA-iu4ipVWv-GhrOBq5F8YlQ42iG">
                <p class="mt-1 text-xs italic text-gray-500">* Mở thư mục trên Drive, ID là chuỗi ký tự cuối cùng trên thanh địa chỉ URL. Tất cả ảnh minh chứng tải lên sẽ được lưu vào đây.</p>
            </div>
            <div class="flex justify-end">
                <button type="button" @click="verifyEvidence()" :disabled="isVerifyingEvidence"
                    class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-[var(--nttu-primary)] hover:bg-slate-50 disabled:opacity-50">
                    <x-form-field-icon name="refresh" tone="amber" class="h-4 w-4" />
                    <span x-text="isVerifyingEvidence ? 'Đang kiểm tra...' : 'Kiểm tra kết nối Minh chứng'"></span>
                </button>
            </div>
            <div class="rounded-xl border border-amber-100 bg-amber-50/80 p-4 text-xs leading-relaxed text-amber-900">
                <p class="mb-1 font-bold">Hướng dẫn cấu hình</p>
                <ul class="list-disc space-y-1 pl-4">
                    <li>Dùng tài khoản Service Account riêng để tách biệt với hệ thống AI nếu cần.</li>
                    <li>Đảm bảo đã chia sẻ quyền <strong>Editor</strong> cho Service Account trên cả tệp Sheet và Thư mục Drive này.</li>
                </ul>
            </div>
        </div>

        <div class="flex flex-col justify-end gap-2 border-t bg-slate-50/50 px-4 py-4 sm:flex-row md:px-6">
            <button type="button" x-show="canEdit" x-cloak @click="undo()" :disabled="!isChanged"
                class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-slate-50 disabled:opacity-40">
                <x-form-field-icon name="undo" tone="blue" class="h-4 w-4" />
                Hoàn tác
            </button>
            <button type="button" x-show="canEdit" x-cloak @click="save()" :disabled="!isChanged || isSaving"
                class="inline-flex items-center gap-2 rounded-md bg-[var(--nttu-table-head)] px-6 py-2 text-sm font-medium text-white hover:opacity-90 disabled:opacity-50">
                <x-form-field-icon name="save" tone="green" class="h-4 w-4 text-white" />
                <span x-text="isSaving ? 'Đang lưu...' : 'Lưu tất cả thay đổi'"></span>
            </button>
        </div>
    </div>
</div>
