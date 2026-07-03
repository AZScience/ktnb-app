@extends('layouts.public')
@section('title', 'Bình chọn — NTTU')
@section('content')
<div class="min-h-screen flex flex-col items-center justify-center p-4 gap-6" x-data="pollPage(@js($poll->toApiFormat()))">
    <template x-if="loading">
        <div class="text-blue-600 font-medium">Đang tải...</div>
    </template>
    <template x-if="error">
        <div class="text-center text-red-500 max-w-sm">
            <p x-text="error"></p>
            <button @click="location.reload()" class="mt-4 text-blue-600 font-bold text-sm">Thử lại</button>
        </div>
    </template>
    <template x-if="!loading && !error && poll">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
                <div class="p-2 text-center text-[10px] font-black uppercase tracking-widest text-white"
                    :class="isExpired ? 'bg-red-500' : 'bg-amber-500'">
                    <span x-text="isExpired ? 'Cuộc bình chọn đã đóng' : 'Thời gian còn lại: ' + timeLeft"></span>
                </div>
                <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-6 text-white">
                    <div class="flex justify-between items-center mb-2">
                        <span class="bg-white/20 px-2 py-1 rounded text-[10px] font-bold uppercase">Hệ thống bình chọn</span>
                        <span class="text-[10px] font-bold opacity-80" x-text="totalVotes + ' phiếu'"></span>
                    </div>
                    <h1 class="text-xl font-black leading-tight" x-html="poll.question"></h1>
                    <p class="text-blue-100 text-[10px] font-bold uppercase mt-2"
                        x-text="(poll.classId || '') + ' • ' + (poll.lecturer || '')"></p>
                </div>
                <div class="p-6">
                    <template x-if="!voter">
                        <div class="space-y-4 text-center">
                            <p class="text-sm text-slate-600">Nhập email trường để bình chọn (1 email = 1 phiếu).</p>
                            <input type="text" x-model="voterName" placeholder="Họ tên" class="w-full border rounded-lg px-3 py-2 text-sm">
                            <input type="email" x-model="voterEmail" placeholder="Email (@ntt.edu.vn)" class="w-full border rounded-lg px-3 py-2 text-sm">
                            <button @click="confirmVoter()" class="inline-flex w-full items-center justify-center gap-2 bg-blue-600 text-white font-bold py-3 rounded-xl">
                                <x-form-field-icon name="check" tone="green" class="h-5 w-5 text-white" />
                                Xác nhận
                            </button>
                        </div>
                    </template>
                    <template x-if="voter">
                        <div class="space-y-3">
                            <div class="flex justify-between items-center bg-emerald-50 p-3 rounded-xl text-sm">
                                <span class="font-bold text-emerald-700" x-text="voter.name"></span>
                                <button @click="voter = null" class="text-xs text-slate-400">Đổi</button>
                            </div>
                            <template x-for="(option, index) in poll.options" :key="index">
                                <button @click="vote(index)" :disabled="submitting || isExpired"
                                    class="w-full text-left border-2 rounded-2xl p-4 transition-all"
                                    :class="currentVote === index ? 'border-blue-500 bg-blue-50' : 'border-slate-100 hover:border-blue-200'">
                                    <div class="flex justify-between gap-3">
                                        <span class="font-bold text-sm" x-html="option"></span>
                                        <span class="text-blue-600 font-black text-sm" x-text="votePercent(index) + '%'"></span>
                                    </div>
                                </button>
                            </template>
                            <button x-show="!isLecturer" @click="isLecturer = true"
                                class="w-full text-[10px] text-slate-400 font-bold uppercase mt-4">Xem thống kê (Giảng viên)</button>
                        </div>
                    </template>
                </div>
            </div>
            <div x-show="isLecturer" class="mt-6 bg-white rounded-2xl shadow-lg p-4 text-sm">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="font-black uppercase text-slate-700">Thống kê giảng viên</h2>
                    <button @click="downloadCsv()" class="text-xs bg-emerald-600 text-white px-3 py-1.5 rounded-lg font-bold">Tải CSV</button>
                </div>
                <p class="text-slate-500 text-xs" x-text="'Đã bình chọn: ' + totalVotes"></p>
            </div>
        </div>
    </template>
</div>
<script>
function pollPage(initial) {
    return {
        poll: initial,
        loading: false,
        error: null,
        voter: null,
        voterName: '',
        voterEmail: '',
        submitting: false,
        isExpired: false,
        timeLeft: '--:--',
        isLecturer: new URLSearchParams(location.search).get('role') === 'lecturer',
        init() {
            this.tick();
            setInterval(() => this.tick(), 1000);
            if (new URLSearchParams(location.search).get('role') === 'lecturer') this.isLecturer = true;
        },
        tick() {
            if (!this.poll?.endTime) return;
            const diff = new Date(this.poll.endTime) - Date.now();
            if (diff <= 0) { this.isExpired = true; this.timeLeft = '00:00'; return; }
            const m = Math.floor(diff / 60000), s = Math.floor((diff % 60000) / 1000);
            this.timeLeft = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
        },
        get voters() { return this.poll?.voters || {}; },
        get totalVotes() { return Object.keys(this.voters).length; },
        get currentVote() {
            if (!this.voter?.email) return undefined;
            const k = this.voter.email.replace(/\./g, '_');
            const v = this.voters[k];
            return typeof v === 'object' ? v.index : v;
        },
        votePercent(idx) {
            const n = Object.values(this.voters).filter(v => (typeof v === 'object' ? v.index : v) === idx).length;
            return this.totalVotes ? Math.round(n / this.totalVotes * 100) : 0;
        },
        confirmVoter() {
            if (!this.voterEmail.trim() || !this.voterName.trim()) return alert('Vui lòng nhập họ tên và email.');
            this.voter = { name: this.voterName.trim(), email: this.voterEmail.trim() };
        },
        async vote(index) {
            if (!this.voter || this.isExpired) return;
            this.submitting = true;
            try {
                const res = await fetch(`/api/v1/polls/${this.poll.id}`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ optionIndex: index, voterName: this.voter.name, voterEmail: this.voter.email })
                });
                const json = await res.json();
                if (json.success) {
                    const r = await fetch(`/api/v1/polls/${this.poll.id}`);
                    const d = await r.json();
                    if (d.success) this.poll = d.data;
                }
            } finally { this.submitting = false; }
        },
        downloadCsv() {
            let csv = '\uFEFFEmail,Họ Tên,Lựa chọn\n';
            Object.entries(this.voters).forEach(([k, v]) => {
                const email = k.replace(/_/g, '.');
                const name = typeof v === 'object' ? v.name : email;
                const idx = typeof v === 'object' ? v.index : v;
                csv += `${email},${name},${idx + 1}\n`;
            });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
            a.download = `ket_qua_${this.poll.id}.csv`;
            a.click();
        }
    };
}
</script>
@endsection
