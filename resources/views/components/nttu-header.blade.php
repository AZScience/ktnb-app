@php
    $user = auth()->user();
    $displayName = $employee?->name ?? $user?->name ?? 'Người dùng';
    $initial = mb_strtoupper(mb_substr($displayName, 0, 1));
    $headerConfig = [
        'unreadCount' => $headerUnreadCount ?? 0,
        'notifications' => collect($headerNotifications ?? [])->map(fn ($n) => [
            'id' => $n['id'],
            'subject' => $n['subject'],
            'body_preview' => $n['body_preview'],
            'sender_name' => $n['sender_name'],
            'sender_avatar' => $n['sender_avatar'],
            'sent_at' => $n['sent_at'],
            'read_url' => route('messaging.read', ['message' => $n['id']]),
        ])->values()->all(),
        'routes' => [
            'markAllRead' => route('messaging.mark-all-read'),
            'messaging' => route('messaging.index'),
            'notifications' => route('messaging.notifications'),
        ],
    ];
@endphp

<header class="sticky top-0 z-50 flex h-16 shrink-0 items-center gap-4 border-b bg-white px-4 sm:px-6">
    <button
        type="button"
        @click="toggleSidebar()"
        class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-md text-[var(--nttu-primary)] transition hover:bg-slate-100 md:inline-flex"
        :title="$root.sidebarOpen ? t('Thu gọn') : t('Mở rộng')"
        :aria-label="$root.sidebarOpen ? t('Thu gọn menu') : t('Mở rộng menu')"
    >
        <svg x-show="sidebarOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
        </svg>
        <svg x-show="!sidebarOpen" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
        </svg>
    </button>

    <button
        type="button"
        @click="toggleSidebar()"
        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-[var(--nttu-primary)] transition hover:bg-slate-100 md:hidden"
        aria-label="Mở menu"
        data-i18n-aria-label="Mở menu"
    >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    <style>
        @keyframes nttu-header-shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        @keyframes nttu-header-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.88; transform: scale(0.985); }
        }
        @keyframes nttu-header-glow {
            0%, 100% { filter: drop-shadow(0 0 1px rgba(255,255,255,0.3)); }
            50% { filter: drop-shadow(0 0 10px currentColor); }
        }
        @keyframes nttu-header-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-2px); }
        }
    </style>

    @php
        $text = !empty($headerTitleText) ? $headerTitleText : 'CÔNG CỤ KIỂM SOÁT';
        $color = !empty($headerTitleColor) ? $headerTitleColor : '#111827';
        $rawSize = !empty($headerTitleFontSize) ? $headerTitleFontSize : '20';
        $displayFontSize = is_numeric($rawSize) ? ($rawSize . 'px') : $rawSize;
        $weight = !empty($headerTitleFontWeight) ? $headerTitleFontWeight : '700';
        $transform = !empty($headerTitleTransform) ? $headerTitleTransform : 'uppercase';
        $spacing = !empty($headerTitleLetterSpacing) ? $headerTitleLetterSpacing : '0.05em';
        $style = !empty($headerTitleStyle) ? $headerTitleStyle : 'normal';
        $effect = !empty($headerTitleEffect) ? $headerTitleEffect : 'none';
        $gradient = !empty($headerTitleGradient) ? $headerTitleGradient : 'none';
        $gradientColor2 = !empty($headerTitleGradientColor2) ? $headerTitleGradientColor2 : '#06b6d4';
        $animation = !empty($headerTitleAnimation) ? $headerTitleAnimation : 'none';
        $badge = !empty($headerTitleBadge) ? $headerTitleBadge : 'none';

        // Text Shadow
        $textShadow = match($effect) {
            'shadow-soft' => '0 2px 4px rgba(0,0,0,0.18)',
            'shadow-3d' => '1px 1px 0px rgba(0,0,0,0.22), 2px 2px 0px rgba(0,0,0,0.16), 3px 3px 3px rgba(0,0,0,0.15)',
            'glow-neon' => "0 0 8px {$color}, 0 0 20px {$color}",
            'shadow-strong' => '2px 3px 6px rgba(0,0,0,0.35)',
            'letterpress' => '0 1px 1px rgba(255,255,255,0.8), 0 -1px 1px rgba(0,0,0,0.25)',
            default => 'none'
        };

        // Gradient background
        $bgGradient = match($gradient) {
            'blue-cyan' => 'linear-gradient(135deg, #0054a6 0%, #06b6d4 100%)',
            'purple-pink' => 'linear-gradient(135deg, #7c3aed 0%, #ec4899 100%)',
            'sunset' => 'linear-gradient(135deg, #dc2626 0%, #ea580c 50%, #f59e0b 100%)',
            'emerald' => 'linear-gradient(135deg, #047857 0%, #10b981 100%)',
            'gold' => 'linear-gradient(135deg, #b45309 0%, #f59e0b 50%, #fde047 100%)',
            'custom' => "linear-gradient(135deg, {$color} 0%, {$gradientColor2} 100%)",
            default => ''
        };

        $isGradientActive = !empty($bgGradient);
        $animationStyle = '';
        if ($animation === 'shimmer') {
            if (!$isGradientActive) {
                $bgGradient = "linear-gradient(90deg, {$color} 0%, #ffffff 50%, {$color} 100%)";
                $isGradientActive = true;
            }
            $animationStyle = 'background-size: 200% auto; animation: nttu-header-shimmer 3s linear infinite;';
        } elseif ($animation === 'pulse') {
            $animationStyle = 'animation: nttu-header-pulse 2.5s ease-in-out infinite;';
        } elseif ($animation === 'glow-pulse') {
            $animationStyle = 'animation: nttu-header-glow 2s ease-in-out infinite;';
        } elseif ($animation === 'float') {
            $animationStyle = 'animation: nttu-header-float 3s ease-in-out infinite;';
        }

        $titleInlineStyles = "font-size: clamp(14px, 4vw, {$displayFontSize}); font-weight: {$weight}; text-transform: {$transform}; letter-spacing: {$spacing}; font-style: {$style}; text-shadow: {$textShadow};";
        if ($isGradientActive) {
            $titleInlineStyles .= " background-image: {$bgGradient}; -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; {$animationStyle}";
        } else {
            $titleInlineStyles .= " color: {$color}; {$animationStyle}";
        }
    @endphp

    <div id="nttu-main-header-title-container" class="flex flex-1 items-center justify-center gap-3 overflow-hidden px-2">
        @if($badge === 'pill-subtle')
            <div class="inline-flex items-center justify-center px-4 py-1 rounded-full bg-slate-100/80 border border-slate-200/90 shadow-xs max-w-full">
                <h1 id="nttu-main-header-title" class="text-center transition-all truncate" style="{{ $titleInlineStyles }}" data-i18n="{{ $text }}">{{ $text }}</h1>
            </div>
        @elseif($badge === 'pill-glass')
            <div class="inline-flex items-center justify-center px-4 py-1.5 rounded-full bg-white/70 backdrop-blur-md border border-white/90 shadow-sm max-w-full">
                <h1 id="nttu-main-header-title" class="text-center transition-all truncate" style="{{ $titleInlineStyles }}" data-i18n="{{ $text }}">{{ $text }}</h1>
            </div>
        @elseif($badge === 'underline-gradient')
            <div class="inline-flex items-center justify-center border-b-2 border-indigo-500/80 pb-0.5 max-w-full">
                <h1 id="nttu-main-header-title" class="text-center transition-all truncate" style="{{ $titleInlineStyles }}" data-i18n="{{ $text }}">{{ $text }}</h1>
            </div>
        @else
            <h1 id="nttu-main-header-title" class="text-center transition-all truncate" style="{{ $titleInlineStyles }}" data-i18n="{{ $text }}">{{ $text }}</h1>
        @endif
    </div>

    <div
        class="flex items-center gap-1"
        x-data="nttuHeader(@js($headerConfig))"
        @keydown.escape.window="closeMenus()"
    >
        {{-- Thông báo --}}
        <div class="relative">
            <button
                type="button"
                @click.stop="toggleNotif()"
                class="relative inline-flex h-10 w-10 items-center justify-center rounded-full text-gray-500 transition hover:bg-slate-100"
                :title="t('Thông báo')"
            >
                <svg
                    class="h-5 w-5 transition-all"
                    :class="unreadCount > 0 ? 'text-yellow-500 animate-bounce-slow' : 'text-gray-500'"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/>
                </svg>
                <span
                    x-show="unreadCount > 0"
                    x-cloak
                    class="absolute -top-0.5 -right-0.5 flex h-5 min-w-[20px] items-center justify-center rounded-full border-2 border-white bg-red-500 px-1 text-[10px] font-bold text-white"
                    x-text="unreadCount"
                ></span>
            </button>

            <div
                x-show="notifOpen"
                x-cloak
                x-transition
                @click.stop
                class="absolute right-0 z-[60] mt-2 w-[calc(100vw-2rem)] sm:w-[340px] max-w-[340px] overflow-hidden rounded-md border bg-white shadow-xl"
            >
                <div class="flex items-center justify-between border-b bg-slate-50/80 p-4">
                    <h3 class="text-sm font-bold tracking-tight text-gray-900" data-i18n="Thông báo">Thông báo</h3>
                    <button
                        type="button"
                        x-show="unreadCount > 0"
                        x-cloak
                        @click="markAllRead()"
                        class="px-2 text-[10px] font-bold uppercase text-[var(--nttu-primary)] hover:text-[var(--nttu-primary)]/80"
                        data-i18n="Đánh dấu tất cả là đã đọc"
                    >
                        Đánh dấu tất cả là đã đọc
                    </button>
                </div>

                <div class="max-h-[400px] divide-y divide-slate-100 overflow-y-auto">
                    <template x-for="msg in notifications" :key="msg.id">
                        <button
                            type="button"
                            @click="openMessage(msg.read_url)"
                            class="flex w-full cursor-pointer items-start gap-3 p-3 text-left transition hover:bg-slate-50"
                        >
                            <div class="relative shrink-0">
                                <img x-show="msg.sender_avatar" :src="msg.sender_avatar" :alt="msg.sender_name" class="h-10 w-10 rounded-full border object-cover shadow-sm">
                                <span x-show="!msg.sender_avatar" class="flex h-10 w-10 items-center justify-center rounded-full border bg-[var(--nttu-primary)]/10 text-sm font-bold text-[var(--nttu-primary)] shadow-sm" x-text="initials(msg.sender_name)"></span>
                                <span class="absolute -top-0.5 -right-0.5 h-3 w-3 animate-pulse rounded-full border-2 border-white bg-red-500"></span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="truncate text-sm font-semibold text-gray-900" x-text="msg.sender_name || t('Hệ thống')"></p>
                                    <span class="whitespace-nowrap text-[10px] text-gray-400" x-text="formatTimeAgo(msg.sent_at)"></span>
                                </div>
                                <p class="truncate text-xs font-medium text-gray-900" x-text="msg.subject"></p>
                                <p class="truncate text-[11px] text-gray-500 opacity-80" x-text="msg.body_preview"></p>
                            </div>
                        </button>
                    </template>

                    <div x-show="notifications.length === 0" class="flex flex-col items-center justify-center gap-2 p-8 text-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                            <svg class="h-6 w-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-500" data-i18n="Không có thông báo mới.">Không có thông báo mới.</p>
                    </div>
                </div>

                <div class="border-t">
                    <a
                        :href="routes.messaging"
                        class="block p-3 text-center text-xs font-bold uppercase tracking-wider text-[var(--nttu-primary)] transition hover:bg-slate-50"
                        data-i18n="Xem tất cả tin nhắn"
                    >
                        Xem tất cả tin nhắn
                    </a>
                </div>
            </div>
        </div>

        {{-- Tài khoản --}}
        <div class="relative">
            <button
                type="button"
                @click.stop="toggleUserMenu()"
                class="relative inline-flex h-10 w-10 items-center justify-center overflow-hidden rounded-full border-2 border-[var(--nttu-primary)]/20 p-0 transition hover:border-[var(--nttu-primary)]/50"
                :title="`${t('Tài khoản')}: {{ $displayName }}`"
            >
                @if (!empty($employee?->avatar_url))
                    <img src="{{ $employee->avatar_url }}" alt="{{ $displayName }}" class="h-9 w-9 rounded-full object-cover">
                @else
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[var(--nttu-primary)]/10 text-sm font-bold text-[var(--nttu-primary)]">{{ $initial }}</span>
                @endif
            </button>

            <div
                x-show="userMenuOpen"
                x-cloak
                x-transition
                @click.stop
                class="absolute right-0 z-[60] mt-2 w-64 max-w-[calc(100vw-2rem)] rounded-md border bg-white py-1 shadow-lg"
            >
                <div class="border-b px-4 py-3">
                    <p class="text-sm font-bold leading-none text-gray-900">{{ $displayName }}</p>
                    <p class="mt-1 truncate text-xs text-gray-500">{{ $user?->email }}</p>
                    @if (!empty($employeePositionName))
                        <p class="mt-1 text-[10px] font-bold uppercase tracking-wider text-[var(--nttu-primary)]">{{ $employeePositionName }}</p>
                    @endif
                </div>

                <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-slate-50">
                    <svg class="mr-2 h-4 w-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span data-i18n="Hồ sơ">Hồ sơ</span>
                </a>
                <a href="{{ route('messaging.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-slate-50">
                    <svg class="mr-2 h-4 w-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    <span data-i18n="Gửi tin">Gửi tin</span>
                </a>
                <a href="{{ route('settings.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-slate-50">
                    <svg class="mr-2 h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span data-i18n="Cài đặt">Cài đặt</span>
                </a>
                <a href="{{ route('manual.view') }}" class="flex items-center px-4 py-2 text-sm font-semibold text-[var(--nttu-primary)] hover:bg-slate-50">
                    <svg class="mr-2 h-4 w-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <span data-i18n="Hướng dẫn">Hướng dẫn</span>
                </a>

                <div class="my-1 border-t"></div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center px-4 py-2 text-left text-sm text-gray-700 hover:bg-slate-50">
                        <svg class="mr-2 h-4 w-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span data-i18n="Đăng xuất">Đăng xuất</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Ngôn ngữ --}}
        <div class="relative">
            <button
                type="button"
                @click.stop="toggleLangMenu()"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full text-[var(--nttu-primary)] transition hover:bg-slate-100"
                :title="t('Ngôn ngữ')"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                </svg>
            </button>

            <div
                x-show="langMenuOpen"
                x-cloak
                x-transition
                @click.stop
                class="absolute right-0 z-[60] mt-2 w-44 rounded-md border bg-white py-1 shadow-lg"
            >
                <button
                    type="button"
                    @click="chooseLanguage('vi')"
                    class="flex w-full items-center px-3 py-2 text-sm text-gray-700 hover:bg-slate-50"
                    :class="language === 'vi' ? 'bg-slate-50 font-semibold' : ''"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 600" width="20" height="14" class="shrink-0">
                        <path fill="#da251d" d="M0 0h900v600H0z"/>
                        <path fill="#ff0" d="M450 150l52.5 162.5H675l-135 97.5 52.5 162.5L450 460l-142.5 112.5 52.5-162.5-135-97.5h172.5z"/>
                    </svg>
                    <span class="ml-2" data-i18n="Tiếng Việt">Tiếng Việt</span>
                </button>
                <button
                    type="button"
                    @click="chooseLanguage('en')"
                    class="flex w-full items-center px-3 py-2 text-sm text-gray-700 hover:bg-slate-50"
                    :class="language === 'en' ? 'bg-slate-50 font-semibold' : ''"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 30" width="20" height="14" class="shrink-0">
                        <clipPath id="s-clip-en-flag"><path d="M0 0v30h60V0z"/></clipPath>
                        <clipPath id="t-clip-en-flag"><path d="M30 15h30v15H30zV15h-30v-15H30z"/></clipPath>
                        <g clip-path="url(#s-clip-en-flag)">
                            <path d="M0 0v30h60V0z" fill="#012169"/>
                            <path d="M0 0l60 30m0-30L0 30" stroke="#fff" stroke-width="6"/>
                            <path d="M0 0l60 30m0-30L0 30" clip-path="url(#t-clip-en-flag)" stroke="#C8102E" stroke-width="4"/>
                            <path d="M30 0v30M0 15h60" stroke="#fff" stroke-width="10"/>
                            <path d="M30 0v30M0 15h60" stroke="#C8102E" stroke-width="6"/>
                        </g>
                    </svg>
                    <span class="ml-2" data-i18n="English">English</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        (function() {
            window.nttuApplyHeaderTitle = function(params) {
                if (!params) return;
                const containerEl = document.getElementById('nttu-main-header-title-container');
                let titleEl = document.getElementById('nttu-main-header-title');
                if (!containerEl || !titleEl) return;

                const text = params.headerTitleText || 'CÔNG CỤ KIỂM SOÁT';
                const color = params.headerTitleColor || '#111827';
                const rawSize = params.headerTitleFontSize || '20';
                const displayFontSize = String(rawSize).includes('px') ? rawSize : (rawSize + 'px');
                const weight = params.headerTitleFontWeight || '700';
                const transform = params.headerTitleTransform || 'uppercase';
                const spacing = params.headerTitleLetterSpacing || '0.05em';
                const style = params.headerTitleStyle || 'normal';
                const effect = params.headerTitleEffect || 'none';
                const gradient = params.headerTitleGradient || 'none';
                const gradientColor2 = params.headerTitleGradientColor2 || '#06b6d4';
                const animation = params.headerTitleAnimation || 'none';
                const badge = params.headerTitleBadge || 'none';

                titleEl.textContent = text;
                titleEl.setAttribute('data-i18n', text);

                let textShadow = 'none';
                if (effect === 'shadow-soft') {
                    textShadow = '0 2px 4px rgba(0,0,0,0.18)';
                } else if (effect === 'shadow-3d') {
                    textShadow = '1px 1px 0px rgba(0,0,0,0.22), 2px 2px 0px rgba(0,0,0,0.16), 3px 3px 3px rgba(0,0,0,0.15)';
                } else if (effect === 'glow-neon') {
                    textShadow = '0 0 8px ' + color + ', 0 0 20px ' + color;
                } else if (effect === 'shadow-strong') {
                    textShadow = '2px 3px 6px rgba(0,0,0,0.35)';
                } else if (effect === 'letterpress') {
                    textShadow = '0 1px 1px rgba(255,255,255,0.8), 0 -1px 1px rgba(0,0,0,0.25)';
                }

                let bgGradient = '';
                if (gradient === 'blue-cyan') {
                    bgGradient = 'linear-gradient(135deg, #0054a6 0%, #06b6d4 100%)';
                } else if (gradient === 'purple-pink') {
                    bgGradient = 'linear-gradient(135deg, #7c3aed 0%, #ec4899 100%)';
                } else if (gradient === 'sunset') {
                    bgGradient = 'linear-gradient(135deg, #dc2626 0%, #ea580c 50%, #f59e0b 100%)';
                } else if (gradient === 'emerald') {
                    bgGradient = 'linear-gradient(135deg, #047857 0%, #10b981 100%)';
                } else if (gradient === 'gold') {
                    bgGradient = 'linear-gradient(135deg, #b45309 0%, #f59e0b 50%, #fde047 100%)';
                } else if (gradient === 'custom') {
                    bgGradient = 'linear-gradient(135deg, ' + color + ' 0%, ' + gradientColor2 + ' 100%)';
                }

                titleEl.style.fontSize = 'clamp(14px, 4vw, ' + displayFontSize + ')';
                titleEl.style.fontWeight = weight;
                titleEl.style.textTransform = transform;
                titleEl.style.letterSpacing = spacing;
                titleEl.style.fontStyle = style;
                titleEl.style.textShadow = textShadow;

                let isGrad = !!bgGradient;
                if (animation === 'shimmer') {
                    if (!isGrad) {
                        bgGradient = 'linear-gradient(90deg, ' + color + ' 0%, #ffffff 50%, ' + color + ' 100%)';
                        isGrad = true;
                    }
                    titleEl.style.backgroundImage = bgGradient;
                    titleEl.style.webkitBackgroundClip = 'text';
                    titleEl.style.webkitTextFillColor = 'transparent';
                    titleEl.style.backgroundClip = 'text';
                    titleEl.style.backgroundSize = '200% auto';
                    titleEl.style.animation = 'nttu-header-shimmer 3s linear infinite';
                } else if (isGrad) {
                    titleEl.style.backgroundImage = bgGradient;
                    titleEl.style.webkitBackgroundClip = 'text';
                    titleEl.style.webkitTextFillColor = 'transparent';
                    titleEl.style.backgroundClip = 'text';
                    titleEl.style.backgroundSize = '100% auto';
                    titleEl.style.animation = '';
                } else {
                    titleEl.style.backgroundImage = 'none';
                    titleEl.style.webkitBackgroundClip = 'unset';
                    titleEl.style.webkitTextFillColor = color;
                    titleEl.style.backgroundClip = 'unset';
                    titleEl.style.color = color;
                    titleEl.style.animation = '';
                }

                if (animation === 'pulse') {
                    titleEl.style.animation = 'nttu-header-pulse 2.5s ease-in-out infinite';
                } else if (animation === 'glow-pulse') {
                    titleEl.style.animation = 'nttu-header-glow 2s ease-in-out infinite';
                } else if (animation === 'float') {
                    titleEl.style.animation = 'nttu-header-float 3s ease-in-out infinite';
                }

                // Wrapper badge
                if (badge === 'pill-subtle') {
                    if (!titleEl.parentElement.classList.contains('bg-slate-100/80')) {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'inline-flex items-center justify-center px-4 py-1 rounded-full bg-slate-100/80 border border-slate-200/90 shadow-xs max-w-full';
                        containerEl.innerHTML = '';
                        wrapper.appendChild(titleEl);
                        containerEl.appendChild(wrapper);
                    }
                } else if (badge === 'pill-glass') {
                    if (!titleEl.parentElement.classList.contains('bg-white/70')) {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'inline-flex items-center justify-center px-4 py-1.5 rounded-full bg-white/70 backdrop-blur-md border border-white/90 shadow-sm max-w-full';
                        containerEl.innerHTML = '';
                        wrapper.appendChild(titleEl);
                        containerEl.appendChild(wrapper);
                    }
                } else if (badge === 'underline-gradient') {
                    if (!titleEl.parentElement.classList.contains('border-b-2')) {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'inline-flex items-center justify-center border-b-2 border-indigo-500/80 pb-0.5 max-w-full';
                        containerEl.innerHTML = '';
                        wrapper.appendChild(titleEl);
                        containerEl.appendChild(wrapper);
                    }
                } else {
                    if (titleEl.parentElement !== containerEl) {
                        containerEl.innerHTML = '';
                        containerEl.appendChild(titleEl);
                    }
                }
            };

            window.addEventListener('nttu-header-title-updated', function(e) {
                if (window.nttuApplyHeaderTitle && e.detail) {
                    window.nttuApplyHeaderTitle(e.detail);
                }
            });
        })();
    </script>
</header>
