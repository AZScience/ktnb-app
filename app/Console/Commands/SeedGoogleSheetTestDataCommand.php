<?php

namespace App\Console\Commands;

use App\Models\AssetReception;
use App\Models\Petition;
use App\Models\ServiceRequest;
use Illuminate\Console\Command;

class SeedGoogleSheetTestDataCommand extends Command
{
    protected $signature = 'nttu:seed-sheet-test-data
                            {--date= : Ngày tiếp nhận (dd/mm/yyyy), mặc định hôm nay}
                            {--force : Ghi đè bản ghi mẫu đã tạo trước đó}';

    protected $description = 'Tạo dữ liệu mẫu thật cho Nhận-Trả tài sản, Tiếp nhận yêu cầu và Tiếp nhận đơn thư (kiểm tra Đẩy Google Sheet)';

    public function handle(): int
    {
        $date = trim((string) $this->option('date'));
        if ($date === '' || ! preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
            $date = date('d/m/Y');
        }

        $staff = 'Nguyễn Vĩnh Phúc';
        $prefix = 'sheet-demo-';

        $assets = [
            [
                'id' => $prefix.'asset-1',
                'entry_number' => 'TS/'.date('y').'/001-DEMO',
                'reception_date' => $date,
                'building_block' => 'A',
                'giver_name' => 'Lê Minh Khôi',
                'giver_id' => '21120001',
                'giver_class' => 'CNTT01',
                'giver_unit' => 'Khoa Công nghệ thông tin',
                'giver_phone' => '0903123456',
                'content' => 'Ví da đen, bên trong có thẻ sinh viên tên Nguyễn Thị Lan',
                'asset_state' => 'Nguyên vẹn',
                'return_status' => 'Chưa trả',
                'is_gratitude' => false,
                'receiving_staff' => $staff,
                'witness' => 'Trần Văn Hùng',
                'note' => 'Nhặt tại hành lang A.801, ca 2',
            ],
            [
                'id' => $prefix.'asset-2',
                'entry_number' => 'TS/'.date('y').'/002-DEMO',
                'reception_date' => $date,
                'building_block' => 'L',
                'giver_name' => 'Phạm Quốc Bảo',
                'giver_id' => '22110045',
                'giver_class' => 'QTKD02',
                'giver_unit' => 'Khoa Quản trị kinh doanh',
                'giver_phone' => '0918765432',
                'content' => 'Tai nghe Bluetooth màu trắng, hộp đựng có nhãn hiệu Sony',
                'asset_state' => 'Trầy xước nhẹ',
                'return_status' => 'Đã trả',
                'is_gratitude' => true,
                'receiving_staff' => $staff,
                'resolution_date' => $date,
                'return_staff' => $staff,
                'receiver_name' => 'Hoàng Thị Mai',
                'receiver_id' => '20109012',
                'receiver_class' => 'QTKD02',
                'receiver_unit' => 'Khoa Quản trị kinh doanh',
                'receiver_phone' => '0987654321',
                'return_asset_state' => 'Đã kiểm tra, SV xác nhận đúng tài sản',
                'receiver_feedback' => 'Sinh viên cảm ơn và ký nhận',
                'gratitude_number' => 'TÂ/'.date('y').'/001-DEMO',
                'gratitude_gift' => 'Thư khen',
                'gratitude_date' => $date,
                'gratitude_staff' => $staff,
                'gratitude_status' => 'Đã trao',
                'note' => 'Đã trả chủ nhân và ghi nhận tri ân',
            ],
            [
                'id' => $prefix.'asset-3',
                'entry_number' => 'TS/'.date('y').'/003-DEMO',
                'reception_date' => $date,
                'building_block' => 'A',
                'giver_name' => 'Võ Thanh Tùng',
                'giver_id' => '23120088',
                'giver_class' => 'CNTT03',
                'giver_unit' => 'Khoa Công nghệ thông tin',
                'giver_phone' => '0933111222',
                'content' => 'Chìa khóa xe máy (2 chiếc), móc treo hình gấu',
                'asset_state' => 'Nguyên vẹn',
                'return_status' => 'Đang xử lý',
                'is_gratitude' => false,
                'receiving_staff' => $staff,
                'note' => 'Đang tìm chủ nhân qua PAHT',
            ],
        ];

        $requests = [
            [
                'id' => $prefix.'request-1',
                'ticket_number' => 'YC/'.date('y').'/001-DEMO',
                'building_block' => 'A',
                'student_name' => 'Nguyễn Thị Hương',
                'student_id' => '21120015',
                'class' => 'CNTT01',
                'department' => 'Khoa Công nghệ thông tin',
                'phone' => '0905111222',
                'content' => 'Đề nghị hỗ trợ mở cửa phòng A.801 do quên thẻ từ trong phòng học nhóm',
                'request_date' => $date,
                'reception_date' => $date,
                'recipient' => $staff,
                'is_processed_immediately' => true,
                'resolution_date' => $date,
                'resolver_name' => $staff,
                'feedback' => 'Đã phối hợp bảo vệ mở cửa, SV lấy được thẻ',
                'status' => 'resolved',
                'note' => 'Xử lý ngay tại chỗ',
            ],
            [
                'id' => $prefix.'request-2',
                'ticket_number' => 'YC/'.date('y').'/002-DEMO',
                'building_block' => 'L',
                'student_name' => 'Trần Đức Anh',
                'student_id' => '22110077',
                'class' => 'QTKD01',
                'department' => 'Khoa Quản trị kinh doanh',
                'phone' => '0912333444',
                'content' => 'Phản ánh máy lạnh phòng L.101 không hoạt động, ảnh hưởng buổi học ca 3',
                'request_date' => $date,
                'reception_date' => $date,
                'recipient' => $staff,
                'is_processed_immediately' => false,
                'appointment_date' => $date,
                'status' => 'pending',
                'note' => 'Đã chuyển bộ phận CSVC kiểm tra',
            ],
            [
                'id' => $prefix.'request-3',
                'ticket_number' => 'YC/'.date('y').'/003-DEMO',
                'building_block' => 'A',
                'student_name' => 'Lê Hoài Phương',
                'student_id' => '20109033',
                'class' => 'CNTT02',
                'department' => 'Khoa Công nghệ thông tin',
                'phone' => '0977888999',
                'content' => 'Yêu cầu cấp lại biên bản xác nhận vi phạm nội quy để làm thủ tục tốt nghiệp',
                'request_date' => $date,
                'reception_date' => $date,
                'recipient' => $staff,
                'is_processed_immediately' => false,
                'appointment_date' => $date,
                'resolution_date' => $date,
                'resolver_name' => $staff,
                'feedback' => 'Đã cấp bản sao có dấu Phòng Kiểm tra nội bộ',
                'status' => 'resolved',
                'note' => 'SV nhận tại quầy',
            ],
        ];

        $petitions = [
            [
                'id' => $prefix.'petition-1',
                'reception_date' => $date,
                'building_block' => 'A',
                'recipient' => $staff,
                'citizen_name' => 'Nguyễn Văn Thành',
                'citizen_id' => '079198001234',
                'citizen_address' => 'Quận 7, TP. Hồ Chí Minh',
                'citizen_phone' => '0909000111',
                'summary' => 'Khiếu nại về việc thu phí gửi xe không đúng quy định tại cổng A',
                'petition_type' => 'Khiếu nại',
                'number_of_people' => 1,
                'previous_authority' => '',
                'is_accepted' => true,
                'is_returned' => false,
                'is_forwarded' => false,
                'resolution_follow_up' => 'Đang thụ lý, hẹn trả lời trong 15 ngày',
                'note' => 'Đơn gốc lưu tại Phòng KTNB',
            ],
            [
                'id' => $prefix.'petition-2',
                'reception_date' => $date,
                'building_block' => 'L',
                'recipient' => $staff,
                'citizen_name' => 'Phạm Thị Hồng',
                'citizen_id' => '22120056',
                'citizen_address' => 'Huyện Hóc Môn, TP. Hồ Chí Minh',
                'citizen_phone' => '0934555666',
                'summary' => 'Đề nghị hướng dẫn thủ tục xin xác nhận sinh viên để làm hồ sơ vay vốn',
                'petition_type' => 'Đơn thư',
                'number_of_people' => 1,
                'previous_authority' => 'Phòng Đào tạo',
                'is_accepted' => false,
                'is_returned' => true,
                'is_forwarded' => false,
                'resolution_follow_up' => 'Đã trả đơn và hướng dẫn nộp tại Phòng Đào tạo',
                'note' => 'Không thuộc thẩm quyền KTNB',
            ],
            [
                'id' => $prefix.'petition-3',
                'reception_date' => $date,
                'building_block' => 'A',
                'recipient' => $staff,
                'citizen_name' => 'Trương Minh Đức',
                'citizen_id' => '079095007890',
                'citizen_address' => 'Quận Bình Thạnh, TP. Hồ Chí Minh',
                'citizen_phone' => '0911222333',
                'summary' => 'Tố cáo hành vi gây rối trong khu ký túc xá, đề nghị xử lý theo quy chế',
                'petition_type' => 'Tố cáo',
                'number_of_people' => 3,
                'previous_authority' => 'Ban quản lý KTX',
                'is_accepted' => false,
                'is_returned' => false,
                'is_forwarded' => true,
                'resolution_follow_up' => 'Đã chuyển Ban quản lý KTX và Công an phường phối hợp',
                'note' => 'Có kèm danh sách 3 người ký',
            ],
        ];

        if (! $this->option('force')) {
            $existing = AssetReception::query()->where('id', $prefix.'asset-1')->exists();
            if ($existing) {
                $this->warn('Dữ liệu mẫu đã tồn tại. Dùng --force để ghi đè.');

                return self::SUCCESS;
            }
        }

        foreach ($assets as $row) {
            AssetReception::updateOrCreate(['id' => $row['id']], $row);
        }

        foreach ($requests as $row) {
            ServiceRequest::updateOrCreate(['id' => $row['id']], $row);
        }

        foreach ($petitions as $row) {
            Petition::updateOrCreate(['id' => $row['id']], $row);
        }

        $this->info("Đã tạo dữ liệu mẫu ngày {$date}:");
        $this->line('  • 3 bản ghi Nhận - Trả tài sản (asset_receptions)');
        $this->line('  • 3 phiếu Tiếp nhận yêu cầu (service_requests)');
        $this->line('  • 3 đơn Tiếp nhận đơn thư (petitions)');
        $this->newLine();
        $this->comment('Kiểm tra tại:');
        $this->line('  /monitoring/asset-check');
        $this->line('  /reports/good-deeds');
        $this->line('  /reports/request-reports');
        $this->line('  /reports/incident-reports');
        $this->comment('Bộ lọc ngày mặc định là hôm nay — chọn đúng ngày '.$date.' nếu chạy lệnh với --date khác.');

        return self::SUCCESS;
    }
}
