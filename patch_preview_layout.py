import re

path = 'resources/views/components/system-parameters-settings.blade.php'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

target = '''<div class="lg:col-span-2">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Xem tr\xc6\xb0\xe1\xbb\x9bc tr\xc3\xaan thanh menu</p>
                    <div class="overflow-hidden rounded-lg border bg-white shadow-sm">
                        <div class="flex items-center gap-3 border-b bg-[var(--nttu-table-head)] px-4 py-2.5">
                            <div class="flex h-8 min-w-[4rem] items-center justify-center overflow-hidden rounded bg-white/10 px-2">
                                <template x-if="hasBanner()">
                                    <img :src="localParams.bannerUrl" alt="Logo" class="max-w-full object-contain" :style="height:px">
                                </template>
                                <template x-if="!hasBanner()">
                                    <span class="text-[10px] font-medium text-white/70">LOGO</span>
                                </template>
                            </div>
                            <span class="text-xs text-white/80">Menu h\xe1\xbb\x87 th\xe1\xbb\x91ng</span>
                        </div>
                        <div class="bg-slate-50 px-4 py-6 text-center text-xs italic text-gray-400">N\xe1\xbb\x99i dung trang</div>
                    </div>
                </div>'''

replacement = '''<div class="lg:col-span-2">
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
                </div>'''

# Ensure correct decoding by converting the target string from a literal matching the bytes
target_decoded = target.encode('latin1').decode('utf-8')

content = content.replace(target_decoded, replacement)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated preview layout")
