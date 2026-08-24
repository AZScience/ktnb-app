@extends(request()->has('popup') ? 'layouts.print' : 'layouts.nttu')
@section('title', 'Xem Biên bản sự việc')
@section('page-section', 'Biên bản sự việc')
@section('content')
<div class="nttu-container">
    @if(!request()->has('popup'))
    <div class="mb-4 flex gap-2 print:hidden">
        <a href="{{ route('incident-records.index') }}" class="nttu-btn nttu-btn-secondary">Quay lại</a>
        <button onclick="window.print()" class="nttu-btn nttu-btn-primary">In Biên bản</button>
        <a href="{{ route('incident-records.export', $incident_record) }}" class="nttu-btn nttu-btn-success text-green-600 bg-green-50">Xuất Word</a>
    </div>
    @endif

    <!-- Print area -->
    <div id="print-area" class="bg-white shadow-lg mx-auto p-12 text-black print:shadow-none print:p-0" style="max-width: 800px; font-family: 'Times New Roman', Times, serif; font-size: 14pt; line-height: 1.5;">
        
        <div class="flex justify-between text-center mb-8">
            <div>
                <p>TRƯỜNG ĐH NGUYỄN TẤT THÀNH</p>
                <p class="font-bold">PHÒNG KIỂM TRA NỘI BỘ</p>
            </div>
            <div>
                <p class="font-bold">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</p>
                <p class="font-bold underline decoration-solid">Độc lập - Tự do - Hạnh phúc</p>
            </div>
        </div>

        <h1 class="text-xl font-bold text-center mb-6">BIÊN BẢN GHI NHẬN SỰ VIỆC</h1>

        <p class="mb-4">
            Vào lúc: {{ $incident_record->incident_time->format('H') }} giờ {{ $incident_record->incident_time->format('i') }} phút, 
            ngày {{ $incident_record->incident_time->format('d') }} 
            tháng {{ $incident_record->incident_time->format('m') }} 
            năm {{ $incident_record->incident_time->format('Y') }}, 
            tại: {{ $incident_record->location }}
        </p>

        <p class="font-bold mb-2">Chúng tôi gồm:</p>
        <div class="mb-4 pl-4">
            @php 
                $participants = is_array($incident_record->participants) ? $incident_record->participants : json_decode($incident_record->participants, true) ?? []; 
                $validParticipants = array_filter($participants, fn($p) => trim($p['name'] ?? '') !== '');
                $count = 1;
            @endphp
            @foreach($validParticipants as $p)
                <p>{{ $count++ }}. {{ $p['name'] }} <span class="ml-4">Chức vụ: {{ $p['role'] }}</span></p>
            @endforeach
        </div>

        <p class="font-bold mb-2">Nội dung ghi nhận:</p>
        <div class="mb-6 whitespace-pre-wrap leading-relaxed text-justify indent-8">
            {{ $incident_record->content }}
        </div>

        <p class="mb-8 italic">
            Biên bản lập xong lúc: {{ $incident_record->conclusion_time->format('H') }} giờ {{ $incident_record->conclusion_time->format('i') }}, 
            ngày {{ $incident_record->conclusion_time->format('d') }} 
            tháng {{ $incident_record->conclusion_time->format('m') }} 
            năm {{ $incident_record->conclusion_time->format('Y') }}
        </p>

        <div class="flex justify-between text-center mt-4">
            <div class="w-1/2 flex flex-col items-center">
                <p class="font-bold">NGƯỜI CHỨNG KIẾN</p>
                <p class="italic">(Ký và ghi rõ họ tên)</p>
                <div class="mt-4 h-24 flex items-center justify-center">
                    @if($incident_record->witness_signature)
                        <img src="{{ $incident_record->witness_signature }}" class="max-h-full">
                    @endif
                </div>
                <div class="mt-2 font-bold uppercase">{{ $incident_record->witness_name }}</div>
            </div>
            <div class="w-1/2 flex flex-col items-center">
                <p class="font-bold">NGƯỜI LẬP BIÊN BẢN</p>
                <p class="italic">(Ký và ghi rõ họ tên)</p>
                <div class="mt-4 h-24 flex items-center justify-center">
                    @if($incident_record->creator_signature)
                        <img src="{{ $incident_record->creator_signature }}" class="max-h-full">
                    @endif
                </div>
                <div class="mt-2 font-bold uppercase">{{ $incident_record->creator_name }}</div>
            </div>
        </div>
    </div>
</div>
<style>
@media print {
    body * {
        visibility: hidden;
    }
    #print-area, #print-area * {
        visibility: visible;
    }
    #print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
    }
}
</style>
@endsection
