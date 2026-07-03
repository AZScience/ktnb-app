@extends('layouts.public')
@section('title', ($exam->title ?? 'Bài kiểm tra') . ' — NTTU')
@section('content')
<div class="min-h-screen p-4 pb-20" x-data="examPage(@js($exam->toApiFormat()))">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white sticky top-4 z-10 shadow-xl rounded-2xl p-4 flex justify-between items-center mb-6 border">
            <div>
                <h1 class="text-sm font-black uppercase" x-text="exam.title"></h1>
                <p class="text-[10px] text-slate-400 font-bold uppercase" x-text="exam.classId + ' • ' + (exam.questions?.length || 0) + ' câu'"></p>
            </div>
            <div class="font-mono font-black text-lg px-4 py-2 rounded-xl border" :class="timeLeft < 60 ? 'text-red-600 bg-red-50' : 'text-slate-700 bg-slate-50'"
                x-text="formatTime(timeLeft)"></div>
        </div>

        <template x-if="submitted">
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden text-center">
                <div class="bg-emerald-600 p-8 text-white">
                    <h2 class="text-2xl font-black">HOÀN THÀNH!</h2>
                    <p class="text-emerald-100 text-sm mt-1">Bài kiểm tra đã được nộp</p>
                </div>
                <div class="p-8">
                    <div class="text-5xl font-black" x-text="score.toFixed(1)"></div>
                    <div class="text-slate-400 font-bold">/ 10.0</div>
                </div>
            </div>
        </template>

        <template x-if="!submitted && !student">
            <div class="bg-white rounded-3xl shadow-xl p-8 text-center space-y-4">
                <p class="text-sm text-slate-600">Nhập thông tin sinh viên để làm bài.</p>
                <input type="text" x-model="studentName" placeholder="Họ tên" class="w-full border rounded-lg px-3 py-2">
                <input type="email" x-model="studentEmail" placeholder="Email" class="w-full border rounded-lg px-3 py-2">
                <button @click="startExam()" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl">Bắt đầu</button>
            </div>
        </template>

        <template x-if="!submitted && student">
            <div class="space-y-4">
                <p class="text-xs font-bold text-emerald-600 uppercase" x-text="'✓ ' + student.name"></p>
                <template x-for="(q, qi) in exam.questions" :key="qi">
                    <div class="bg-white rounded-3xl shadow-lg overflow-hidden">
                        <div class="p-5 border-b font-bold" x-text="(qi+1) + '. ' + q.question"></div>
                        <div class="p-4 space-y-2 bg-slate-50">
                            <template x-for="(opt, oi) in q.options" :key="oi">
                                <button @click="answers[qi] = oi"
                                    class="w-full text-left p-4 rounded-2xl border-2 font-bold text-sm transition-all"
                                    :class="answers[qi] === oi ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-white'">
                                    <span x-text="String.fromCharCode(65+oi) + '. ' + opt"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
                <button @click="submit()" :disabled="Object.keys(answers).length < exam.questions.length"
                    class="w-full h-14 bg-blue-600 disabled:bg-slate-300 text-white font-black text-lg rounded-2xl">
                    NỘP BÀI KIỂM TRA
                </button>
            </div>
        </template>
    </div>
</div>
<script>
function examPage(exam) {
    return {
        exam,
        student: null,
        studentName: '',
        studentEmail: '',
        answers: {},
        timeLeft: (exam.duration || 15) * 60,
        submitted: false,
        score: 0,
        init() {
            const t = setInterval(() => {
                if (this.submitted || !this.student) return;
                this.timeLeft--;
                if (this.timeLeft <= 0) { clearInterval(t); this.submit(); }
            }, 1000);
        },
        formatTime(s) {
            return String(Math.floor(s/60)).padStart(2,'0') + ':' + String(s%60).padStart(2,'0');
        },
        startExam() {
            if (!this.studentName.trim() || !this.studentEmail.trim()) return alert('Nhập họ tên và email.');
            this.student = { name: this.studentName.trim(), email: this.studentEmail.trim() };
        },
        submit() {
            if (this.submitted) return;
            let correct = 0;
            (this.exam.questions || []).forEach((q, i) => { if (this.answers[i] === q.correct) correct++; });
            this.score = (this.exam.questions?.length ? correct / this.exam.questions.length : 0) * 10;
            this.submitted = true;
        }
    };
}
</script>
@endsection
