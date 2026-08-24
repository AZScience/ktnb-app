<?php

namespace Tests\Feature;

use App\Models\BuildingBlock;
use App\Models\DailySchedule;
use App\Models\Employee;
use App\Models\StudentViolation;
use App\Models\User;
use App\Services\MonthlyReportStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MonthlyReportStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_stats_count_recorded_in_person_incidents_and_filter_campus(): void
    {
        BuildingBlock::create([
            'id' => 'cs1',
            'code' => 'CS1',
            'name' => 'Cơ sở 1',
            'note' => 'Quận 4',
        ]);

        $this->schedule([
            'id' => 'in-person-cs1-change-room',
            'date' => '05/07/2026',
            'building' => 'CS1',
            'room' => 'A101',
            'department' => 'Khoa CNTT',
            'incident' => 'Chuyển phòng',
            'incident_detail' => 'Đổi sang A102',
            'recognition_date' => '05/07/2026',
            'employee' => 'Nguyễn A',
        ]);

        $this->schedule([
            'id' => 'in-person-cs1-teaching-substitute',
            'date' => '06/07/2026',
            'building' => 'Cơ sở 1',
            'department' => 'Khoa QTKD',
            'incident' => 'Dạy thay',
            'recognition_date' => '06/07/2026',
            'employee' => 'Nguyễn A',
        ]);

        $this->schedule([
            'id' => 'in-person-unrecorded-is-ignored',
            'date' => '07/07/2026',
            'building' => 'CS1',
            'department' => 'Khoa Luật',
            'incident' => 'Báo nghỉ',
        ]);

        $this->schedule([
            'id' => 'in-person-other-campus-is-filtered',
            'date' => '08/07/2026',
            'building' => 'CS2',
            'department' => 'Khoa Ngoại ngữ',
            'incident' => 'Báo nghỉ',
            'recognition_date' => '08/07/2026',
            'employee' => 'Nguyễn A',
        ]);

        $data = app(MonthlyReportStatsService::class)->buildReportData('01/07/2026', '31/07/2026', 'CS1');

        $this->assertSame(2, $data['metrics'][7]); // Tổng việc phát sinh trực tiếp
        $this->assertSame(1, $data['metrics'][8]); // Chuyển phòng
        $this->assertSame(1, $data['metrics'][9]); // Dạy thay
        $this->assertSame(0, $data['metrics'][10]); // Báo nghỉ bị loại do chưa ghi nhận / khác campus
        $this->assertStringContainsString('Khoa CNTT - Đổi sang A102', $data['redTexts'][0]);
        $this->assertSame('Khoa QTKD', $data['redTexts'][1]);

        $filtered = app(MonthlyReportStatsService::class)->filterSchedulesForReport(
            DailySchedule::query()->whereNotNull('incident')->where('incident', '!=', '')->get(),
            'CS1',
        );
        $this->assertCount(3, $filtered);
        $this->assertEqualsCanonicalizing([
            'in-person-cs1-change-room',
            'in-person-cs1-teaching-substitute',
            'in-person-unrecorded-is-ignored',
        ], $filtered->pluck('id')->all());
    }

    public function test_online_and_homeroom_use_recorded_counts_and_ignore_physical_campus(): void
    {
        BuildingBlock::create([
            'id' => 'cs1-online-campus',
            'code' => 'CS1',
            'name' => 'Cơ sở 1',
            'note' => 'Quận 4',
        ]);

        // Lịch online chưa ghi nhận — không được tính vào tổng lớp đã kiểm tra.
        $this->schedule([
            'id' => 'online-unrecorded',
            'date' => '09/07/2026',
            'building' => 'Trực tuyến',
            'content' => 'Toán rời rạc',
        ]);

        $this->schedule([
            'id' => 'online-recorded-ok',
            'date' => '10/07/2026',
            'building' => 'Trực tuyến',
            'content' => 'Lập trình web',
            'incident' => 'Báo nghỉ',
            'department' => 'Khoa CNTT',
            'recognition_date' => '10/07/2026',
            'employee' => 'Thầy Phúc',
        ]);

        $this->schedule([
            'id' => 'online-recorded-issue',
            'date' => '11/07/2026',
            'building' => 'Học trực tuyến',
            'content' => 'Cơ sở dữ liệu',
            'incident' => 'Ghi nhận khác',
            'incident_detail' => 'Không có link LCMS',
            'department' => 'Khoa QTKD',
            'recognition_date' => '11/07/2026',
            'employee' => 'Thầy Phúc',
        ]);

        $this->schedule([
            'id' => 'homeroom-online-sh',
            'date' => '12/07/2026',
            'building' => 'Học trực tuyến',
            'content' => 'SHCN lớp 22DTH1',
            'incident' => 'Có SH',
            'department' => 'Khoa CNTT',
            'recognition_date' => '12/07/2026',
            'employee' => 'Thầy Phúc',
        ]);

        $this->schedule([
            'id' => 'homeroom-online-other',
            'date' => '13/07/2026',
            'building' => 'Học trực tuyến',
            'content' => 'SHCN lớp 22DTH2',
            'incident' => 'Ghi nhận khác',
            'incident_detail' => 'Không có mặt GVCN',
            'department' => 'Khoa CNTT',
            'recognition_date' => '13/07/2026',
            'employee' => 'Thầy Phúc',
        ]);

        $data = app(MonthlyReportStatsService::class)->buildReportData('01/07/2026', '31/07/2026', 'CS1');

        $this->assertSame(2, $data['metrics'][19]); // Tổng lớp online đã ghi nhận (không gồm unrecorded)
        $this->assertSame(1, $data['metrics'][21]); // Không LCMS
        $this->assertSame(1, $data['metrics'][23]); // Báo nghỉ
        $this->assertSame(2, $data['metrics'][39]); // Tổng CVHT đã ghi nhận
        $this->assertSame(0, $data['metrics'][40]); // Trực tiếp
        $this->assertSame(2, $data['metrics'][41]); // Trực tuyến
        $this->assertSame(1, $data['metrics'][42]); // Có SH
        $this->assertSame(1, $data['metrics'][44]); // Ghi nhận khác
    }

    public function test_monthly_stats_filter_by_recording_employee_aliases(): void
    {
        $user = User::factory()->create(['name' => 'Nguyễn Vĩnh Phúc']);
        Employee::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'employee_id' => 'NV001',
            'name' => 'Nguyễn Vĩnh Phúc',
            'nickname' => 'Thầy Phúc',
            'email' => $user->email,
        ]);
        Employee::forgetRecipientNicknameLookup();

        $this->schedule([
            'id' => 'online-by-phuc',
            'date' => '10/07/2026',
            'building' => 'Trực tuyến',
            'content' => 'Lập trình web',
            'incident' => 'Báo nghỉ',
            'recognition_date' => '10/07/2026',
            'employee' => 'Thầy Phúc',
        ]);

        $this->schedule([
            'id' => 'online-by-other',
            'date' => '11/07/2026',
            'building' => 'Trực tuyến',
            'content' => 'Cơ sở dữ liệu',
            'incident' => 'Báo nghỉ',
            'recognition_date' => '11/07/2026',
            'employee' => 'Thầy Hải',
        ]);

        $this->schedule([
            'id' => 'homeroom-by-phuc',
            'date' => '12/07/2026',
            'building' => 'Học trực tuyến',
            'content' => 'SHCN lớp 22DTH1',
            'incident' => 'Có SH',
            'recognition_date' => '12/07/2026',
            'employee' => 'Thầy Phúc',
        ]);

        $data = app(MonthlyReportStatsService::class)->buildReportData(
            '01/07/2026',
            '31/07/2026',
            '',
            ['Nguyễn Vĩnh Phúc'],
        );

        $this->assertSame(1, $data['metrics'][19]); // chỉ online của Thầy Phúc
        $this->assertSame(1, $data['metrics'][23]);
        $this->assertSame(1, $data['metrics'][39]); // chỉ CVHT của Thầy Phúc
        $this->assertSame(1, $data['metrics'][42]);

        $filtered = app(MonthlyReportStatsService::class)->filterSchedulesForReport(
            DailySchedule::query()->whereNotNull('incident')->where('incident', '!=', '')->get(),
            '',
            ['Nguyễn Vĩnh Phúc'],
        );
        $this->assertEqualsCanonicalizing([
            'online-by-phuc',
            'homeroom-by-phuc',
        ], $filtered->pluck('id')->all());
    }

    public function test_monthly_stats_classify_online_exam_violation_and_homeroom_rows(): void
    {
        $this->schedule([
            'id' => 'online-no-lcms',
            'date' => '10/07/2026',
            'building' => 'Trực tuyến',
            'content' => 'Lập trình web',
            'incident' => 'Ghi nhận khác',
            'incident_detail' => 'Không có link LCMS',
            'department' => 'Khoa CNTT',
            'recognition_date' => '10/07/2026',
            'employee' => 'Nguyễn A',
        ]);

        $this->assertDatabaseHas('daily_schedules', [
            'id' => 'online-no-lcms',
            'date_iso' => '2026-07-10',
            'recognition_date_iso' => '2026-07-10',
        ]);

        $this->schedule([
            'id' => 'online-cancelled',
            'date' => '11/07/2026',
            'building' => 'Trực tuyến',
            'content' => 'Cơ sở dữ liệu',
            'incident' => 'Báo nghỉ',
            'department' => 'Khoa QTKD',
            'recognition_date' => '11/07/2026',
            'employee' => 'Nguyễn A',
        ]);

        $this->schedule([
            'id' => 'exam-proctor-absent',
            'date' => '12/07/2026',
            'building' => 'CS1',
            'room' => 'P.Thi 01',
            'status' => 'Phòng thi',
            'incident' => 'CBCT không đến coi thi',
            'department' => 'Khoa CNTT',
            'recognition_date' => '12/07/2026',
            'employee' => 'Nguyễn A',
        ]);

        StudentViolation::create([
            'id' => (string) Str::uuid(),
            'full_name' => 'Trần Văn B',
            'class' => '22DTH1',
            'student_id' => '2200001',
            'violation_date' => '12/07/2026',
            'violation_type' => 'Mang tài liệu vào phòng thi',
            'building' => 'CS1',
            'department' => 'Khoa CNTT',
            'note' => 'Vi phạm quy chế thi',
        ]);

        $this->schedule([
            'id' => 'homeroom-onsite-done',
            'date' => '13/07/2026',
            'building' => 'CS1',
            'content' => 'SHCN lớp 22DTH1',
            'incident' => 'Có SH',
            'department' => 'Khoa CNTT',
            'recognition_date' => '13/07/2026',
            'employee' => 'Nguyễn A',
        ]);

        $data = app(MonthlyReportStatsService::class)->buildReportData('2026-07-01', '2026-07-31');

        $this->assertSame(2, $data['metrics'][19]); // Tổng lớp online đã ghi nhận
        $this->assertSame(1, $data['metrics'][21]); // Không có LCMS/link
        $this->assertSame(1, $data['metrics'][23]); // Online báo nghỉ
        $this->assertSame(1, $data['metrics'][25]); // Tổng ca thi đã ghi nhận
        $this->assertSame(1, $data['metrics'][26]); // Ca thi có phát sinh
        $this->assertSame(1, $data['metrics'][29]); // CBCT không đến
        $this->assertSame(1, $data['metrics'][33]); // Tổng SV vi phạm thi
        $this->assertSame(1, $data['metrics'][35]); // Mang tài liệu
        $this->assertSame(1, $data['metrics'][39]); // Tổng CVHT đã ghi nhận
        $this->assertSame(1, $data['metrics'][40]); // CVHT trực tiếp
        $this->assertSame(1, $data['metrics'][42]); // Có SH
    }

    /** @param array<string, mixed> $overrides */
    private function schedule(array $overrides): DailySchedule
    {
        return DailySchedule::create(array_merge([
            'id' => (string) Str::uuid(),
            'date' => '01/07/2026',
            'building' => 'CS1',
            'room' => 'A101',
            'period' => '1-3',
            'type' => 'LT',
            'department' => '',
            'class' => '22DTH1',
            'student_count' => 40,
            'lecturer' => 'GV A',
            'content' => 'Môn học',
            'status' => '',
            'incident' => '',
            'recognition_date' => '',
            'employee' => '',
        ], $overrides));
    }
}
