<div id="incident-record-printable-area" class="px-2 pb-6 pt-4 text-[15px] leading-relaxed text-gray-800" style="font-family: 'Times New Roman', Times, serif;" x-data="{}" x-effect="if (form.participants) { form.creator_name = form.participants[0]?.name || ''; form.witness_name = form.participants[1]?.name || ''; }">
    <style>
@media print {
    body * { visibility: hidden !important; }
    #incident-record-printable-area, #incident-record-printable-area * { visibility: visible !important; }
    #incident-record-printable-area { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 20px; }
    .print\:hidden { display: none !important; }
    .fixed { position: static !important; }
    .overflow-y-auto { overflow: visible !important; }
}
</style>

    <h2 class="mb-6 text-center text-2xl font-bold uppercase tracking-wider text-gray-900">
        BIÊN BẢN GHI NHẬN SỰ VIỆC
    </h2>

    <div class="mb-6 flex flex-wrap items-baseline gap-1" x-data="{
        get iTime() { return form.incident_time ? new Date(form.incident_time) : new Date(); },
        updateTime(f, v) {
            let d = this.iTime;
            if(f === 'h') d.setHours(v);
            if(f === 'm') d.setMinutes(v);
            if(f === 'd') d.setDate(v);
            if(f === 'M') d.setMonth(v - 1);
            if(f === 'y') d.setFullYear(v);
            let pad = (n) => n.toString().padStart(2, '0');
            form.incident_time = d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        }
    }">
        <span>Vào lúc:</span>
        <input type="text" :value="String(iTime.getHours()).padStart(2, '0')" @input="updateTime('h', $event.target.value)" class="w-10 border-transparent border-b border-solid border-b-gray-300 focus:border-transparent focus:border-b-black bg-transparent px-1 text-center font-bold  focus:ring-0"> giờ
        <input type="text" :value="String(iTime.getMinutes()).padStart(2, '0')" @input="updateTime('m', $event.target.value)" class="w-10 border-transparent border-b border-solid border-b-gray-300 focus:border-transparent focus:border-b-black bg-transparent px-1 text-center font-bold  focus:ring-0"> phút,
        <span>ngày</span>
        <input type="text" :value="String(iTime.getDate()).padStart(2, '0')" @input="updateTime('d', $event.target.value)" class="w-10 border-transparent border-b border-solid border-b-gray-300 focus:border-transparent focus:border-b-black bg-transparent px-1 text-center font-bold  focus:ring-0">
        <span>tháng</span>
        <input type="text" :value="String(iTime.getMonth() + 1).padStart(2, '0')" @input="updateTime('M', $event.target.value)" class="w-10 border-transparent border-b border-solid border-b-gray-300 focus:border-transparent focus:border-b-black bg-transparent px-1 text-center font-bold  focus:ring-0">
        <span>năm</span>
        <input type="text" :value="iTime.getFullYear()" @input="updateTime('y', $event.target.value)" class="w-16 border-transparent border-b border-solid border-b-gray-300 focus:border-transparent focus:border-b-black bg-transparent px-1 text-center font-bold  focus:ring-0">, 
        <span class="ml-2">tại:</span>
        <input type="text" x-model="form.location" required placeholder="Ghi rõ phòng, khu vực" 
            class="inline-block flex-1 border-transparent border-b border-solid border-b-gray-300 focus:border-transparent focus:border-b-black bg-transparent px-2 py-0 font-bold  focus:ring-0">
    </div>

    <div class="mb-6" x-init="$watch('form', () => { if(!form.participants || form.participants.length === 0) form.participants = [{name: form.creator_name || '', role: ''}, {name: '', role: ''}] }); if(!form.participants || form.participants.length === 0) form.participants = [{name: form.creator_name || '', role: ''}, {name: '', role: ''}]">
        <p class="mb-2 font-bold">Chúng tôi gồm:</p>
        
        <template x-for="(p, index) in (form.participants || [])" :key="index">
            <div class="mb-2 flex items-baseline gap-2">
                <span x-text="index + 1 + '.'"></span>
                <input type="text" x-model="p.name" placeholder="Họ và tên..." 
                    class="inline-block flex-1 border-transparent border-b border-solid border-b-gray-300 focus:border-transparent focus:border-b-black bg-transparent px-2 py-0 text-[15px]  focus:ring-0">
                <span class="ml-4">Chức vụ:</span>
                <input type="text" x-model="p.role" placeholder="Chức vụ..." 
                    class="inline-block flex-1 border-transparent border-b border-solid border-b-gray-300 focus:border-transparent focus:border-b-black bg-transparent px-2 py-0 text-[15px]  focus:ring-0">
                <button x-show="index >= 2" type="button" @click="form.participants.splice(index, 1)" class="ml-2 font-sans text-red-500 hover:text-red-700" title="Xóa">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </template>
        <button type="button" @click="if(!Array.isArray(form.participants)) form.participants = [{name: '', role: ''}]; if(form.participants.length < 10) form.participants.push({name: '', role: ''})" class="mt-1 font-sans text-sm text-blue-600 hover:underline">
            + Thêm người tham gia
        </button>
    </div>

    <div class="mb-6">
        <p class="mb-2 font-bold">Nội dung ghi nhận:</p>
        <textarea x-model="form.content" rows="6" required placeholder="Ghi chép chi tiết sự việc..."
            class="w-full resize-none border-none bg-transparent p-0 text-[15px] leading-8 text-gray-900 focus:outline-none focus:ring-0"
            style="background-image: repeating-linear-gradient(transparent, transparent 31px, #9ca3af 31px, #9ca3af 32px); line-height: 32px;"
        ></textarea>
    </div>

    <div class="mb-8 flex flex-wrap items-baseline gap-1" x-data="{
        get cTime() { return form.conclusion_time ? new Date(form.conclusion_time) : new Date(); },
        updateTime(f, v) {
            let d = this.cTime;
            if(f === 'h') d.setHours(v);
            if(f === 'm') d.setMinutes(v);
            if(f === 'd') d.setDate(v);
            if(f === 'M') d.setMonth(v - 1);
            if(f === 'y') d.setFullYear(v);
            let pad = (n) => n.toString().padStart(2, '0');
            form.conclusion_time = d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        }
    }">
        <span class="italic">Biên bản lập xong lúc:</span>
        <input type="text" :value="String(cTime.getHours()).padStart(2, '0')" @input="updateTime('h', $event.target.value)" class="w-10 border-transparent border-b border-solid border-b-gray-300 focus:border-transparent focus:border-b-black bg-transparent px-1 text-center font-bold  focus:ring-0"> giờ
        <input type="text" :value="String(cTime.getMinutes()).padStart(2, '0')" @input="updateTime('m', $event.target.value)" class="w-10 border-transparent border-b border-solid border-b-gray-300 focus:border-transparent focus:border-b-black bg-transparent px-1 text-center font-bold  focus:ring-0">,
        <span class="italic">ngày</span>
        <input type="text" :value="String(cTime.getDate()).padStart(2, '0')" @input="updateTime('d', $event.target.value)" class="w-10 border-transparent border-b border-solid border-b-gray-300 focus:border-transparent focus:border-b-black bg-transparent px-1 text-center font-bold  focus:ring-0">
        <span class="italic">tháng</span>
        <input type="text" :value="String(cTime.getMonth() + 1).padStart(2, '0')" @input="updateTime('M', $event.target.value)" class="w-10 border-transparent border-b border-solid border-b-gray-300 focus:border-transparent focus:border-b-black bg-transparent px-1 text-center font-bold  focus:ring-0">
        <span class="italic">năm</span>
        <input type="text" :value="cTime.getFullYear()" @input="updateTime('y', $event.target.value)" class="w-16 border-transparent border-b border-solid border-b-gray-300 focus:border-transparent focus:border-b-black bg-transparent px-1 text-center font-bold  focus:ring-0">
    </div>

    <div class="mb-8 mt-4 text-left" x-data="{ openEvidence: false }">
        <div class="rounded-lg border bg-white px-4">
            <button type="button" class="flex w-full items-center justify-between py-3 text-left" @click="openEvidence = !openEvidence">
                <span class="flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-blue-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    THÔNG TIN MINH CHỨNG
                </span>
                <svg class="h-4 w-4 text-gray-400 transition-transform" :class="openEvidence ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="openEvidence" x-cloak class="pb-4">
                <x-evidence-input-panel />
            </div>
        </div>
    </div>

    <div class="flex justify-between text-center pb-12">
        <div class="w-1/2 flex flex-col items-center">
            <p class="font-bold">NGƯỜI CHỨNG KIẾN</p>
            <p class="italic mb-2">(Ký và ghi rõ họ tên)</p>
            
            <div class="w-48 h-24 relative border border-dashed border-gray-400 bg-gray-50 flex flex-col items-center justify-center cursor-pointer rounded hover:bg-gray-100 transition-colors"
                 @click="$dispatch('open-signature-pad', { target: 'form.witness_signature', title: 'Chữ ký Người chứng kiến' })">
                <template x-if="form.witness_signature">
                    <img :src="form.witness_signature" class="max-h-full max-w-full">
                </template>
                <template x-if="!form.witness_signature">
                    <span class="text-sm text-gray-500 font-sans">Nhấn để ký tên</span>
                </template>
                <template x-if="form.witness_signature">
                    <button type="button" @click.stop="form.witness_signature = null" class="absolute -right-2 top-0 translate-x-full text-xs text-red-500 hover:underline font-sans">Xóa</button>
                </template>
            </div>
            
            <input type="text" x-model="form.witness_name" readonly placeholder="Nhập tên người chứng kiến" 
                class="mt-4 inline-block w-48 border-transparent border-b border-solid border-b-gray-300 focus:border-transparent focus:border-b-black bg-transparent px-2 py-0 text-center text-[15px] font-bold uppercase  focus:ring-0">
        </div>
        <div class="w-1/2 flex flex-col items-center">
            <p class="font-bold">NGƯỜI LẬP BIÊN BẢN</p>
            <p class="italic mb-2">(Ký và ghi rõ họ tên)</p>
            
            <div class="w-48 h-24 relative border border-dashed border-gray-400 bg-gray-50 flex flex-col items-center justify-center cursor-pointer rounded hover:bg-gray-100 transition-colors"
                 @click="$dispatch('open-signature-pad', { target: 'form.creator_signature', title: 'Chữ ký Người lập biên bản' })">
                <template x-if="form.creator_signature">
                    <img :src="form.creator_signature" class="max-h-full max-w-full">
                </template>
                <template x-if="!form.creator_signature">
                    <span class="text-sm text-gray-500 font-sans">Nhấn để ký tên</span>
                </template>
                <template x-if="form.creator_signature">
                    <button type="button" @click.stop="form.creator_signature = null" class="absolute -right-2 top-0 translate-x-full text-xs text-red-500 hover:underline font-sans">Xóa</button>
                </template>
            </div>
            
            <input type="text" x-model="form.creator_name" required readonly placeholder="Nhập tên người lập" 
                class="mt-4 inline-block w-48 border-transparent border-b border-solid border-b-gray-300 focus:border-transparent focus:border-b-black bg-transparent px-2 py-0 text-center text-[15px] font-bold uppercase  focus:ring-0">
        </div>
    </div>
    
    <!-- Signature Pad Modal -->
    <div x-data="{
        sigOpen: false, targetKey: null, title: 'Bảng Ký Tên',
        drawing: false, ctx: null,
        init() {
            this.ctx = this.$refs.sigCanvas.getContext('2d');
        },
        openPad(target, title) {
            this.targetKey = target;
            this.title = title;
            this.sigOpen = true;
            setTimeout(() => {
                this.$refs.sigCanvas.width = this.$refs.sigCanvas.offsetWidth;
                this.$refs.sigCanvas.height = this.$refs.sigCanvas.offsetHeight;
                this.ctx.lineWidth = 3; 
                this.ctx.lineCap = 'round'; 
                this.ctx.strokeStyle = '#002060';
                
                let keys = this.targetKey.split('.');
                let val = keys.reduce((o, i) => o[i], this);
                if(val) {
                    let img = new Image();
                    img.onload = () => this.ctx.drawImage(img, 0, 0, img.width, img.height, 0, 0, this.$refs.sigCanvas.width, this.$refs.sigCanvas.height);
                    img.src = val;
                } else {
                    this.ctx.clearRect(0, 0, this.$refs.sigCanvas.width, this.$refs.sigCanvas.height);
                }
            }, 100);
        },
        startDraw(e) {
            this.drawing = true;
            let rect = this.$refs.sigCanvas.getBoundingClientRect();
            let clientX = e.clientX || (e.touches && e.touches[0].clientX);
            let clientY = e.clientY || (e.touches && e.touches[0].clientY);
            this.ctx.beginPath();
            this.ctx.moveTo(clientX - rect.left, clientY - rect.top);
        },
        draw(e) {
            if (!this.drawing) return;
            let rect = this.$refs.sigCanvas.getBoundingClientRect();
            let clientX = e.clientX || (e.touches && e.touches[0].clientX);
            let clientY = e.clientY || (e.touches && e.touches[0].clientY);
            this.ctx.lineTo(clientX - rect.left, clientY - rect.top);
            this.ctx.stroke();
        },
        stopDraw() {
            this.drawing = false;
        },
        clearPad() {
            this.ctx.clearRect(0, 0, this.$refs.sigCanvas.width, this.$refs.sigCanvas.height);
        },
        savePad() {
            let dataUrl = null;
            let blank = document.createElement('canvas');
            blank.width = this.$refs.sigCanvas.width;
            blank.height = this.$refs.sigCanvas.height;
            if (this.$refs.sigCanvas.toDataURL() !== blank.toDataURL()) {
                dataUrl = this.$refs.sigCanvas.toDataURL();
            }
            if (this.targetKey === 'form.witness_signature') {
                this.form.witness_signature = dataUrl;
            } else {
                this.form.creator_signature = dataUrl;
            }
            this.sigOpen = false;
        }
    }"
    @open-signature-pad.window="openPad($event.detail.target, $event.detail.title)"
    x-show="sigOpen" 
    class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 font-sans" 
    style="display: none;">
        <div class="w-full max-w-lg bg-white rounded-xl shadow-2xl p-6 m-4" @click.away="sigOpen = false" x-transition>
            <h3 class="text-xl font-bold mb-4 text-center text-gray-800" x-text="title"></h3>
            <div class="relative bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg touch-none" style="height: 250px;">
                <canvas x-ref="sigCanvas" class="w-full h-full cursor-crosshair rounded-lg"
                    @mousedown="startDraw" @mousemove="draw" @mouseup="stopDraw" @mouseleave="stopDraw"
                    @touchstart.prevent="startDraw" @touchmove.prevent="draw" @touchend.prevent="stopDraw"></canvas>
            </div>
            <p class="text-center text-sm text-gray-500 mt-2">Dùng chuột hoặc ngón tay để ký vào khung trên</p>
            <div class="mt-6 flex justify-between items-center">
                <button type="button" @click="clearPad" class="px-4 py-2 text-red-600 font-semibold hover:bg-red-50 rounded transition-colors">Xóa ký lại</button>
                <div class="flex gap-3">
                    <button type="button" @click="sigOpen = false" class="px-5 py-2 text-gray-700 font-medium border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">Hủy</button>
                    <button type="button" @click="savePad" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg shadow-sm hover:bg-blue-700 transition-colors">Lưu chữ ký</button>
                </div>
            </div>
        </div>
    </div>
</div>