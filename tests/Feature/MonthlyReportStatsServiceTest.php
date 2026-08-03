<?php

namespace Tests\Feature;

use App\Models\BuildingBlock;
use App\Models\DailySchedule;
use App\Models\Employee;
use App\Models\User;
use App\Services\MonthlyReportStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MonthlyReportStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_campus_filters_offline_while_department_filters_online_and_cvht_online(): void
    {
        BuildingBlock::create([
            'id' => 'cs1',
            'code' => 'CS1',
            'name' => 'Cơ sở 1',
            'note' => 'Cơ sở Tân Hưng',
        ]);

        $this->schedule([
            'id' => 'offline-change-room',
            'date' => '05/07/2026',
            'building' => 'Dãy nhà M',
            'room' => 'M.101',
            'department' => 'Khoa CNTT',
            'incident' => 'Chuyển phòng',
            'recognition_date' => '05/07/2026',
            'employee' => 'Thầy Phúc',
        ]);

        $this->schedule([
            'id' => 'online-other-dept-ignored',
            'date' => '06/07/2026',
            'building' => 'Học trực tuyến',
            'department' => 'Khoa Luật',
            'content' => 'Toán rời rạc',
            'incident' => 'Báo nghỉ',
            'recognition_date' => '06/07/2026',
            'employee' => 'Thầy Phúc',
        ]);

        $this->schedule([
            'id' => 'online-matching-dept',
            'date' => '07/07/2026',
            'building' => 'Học trực tuyến',
            'department' => 'Khoa CNTT',
            'content' => 'Lập trình web',
            'incident' => 'Báo nghỉ',
            'recognition_date' => '07/07/2026',
            'employee' => 'Thầy Phúc',
        ]);

        $this->schedule([
            'id' => 'online-unrecorded-ignored',
            'date' => '08/07/2026',
            'building' => 'Học trực tuyến',
            'department' => 'Khoa CNTT',
            'content' => 'CSDL',
        ]);

        $this->schedule([
            'id' => 'homeroom-online-matching-dept',
            'date' => '09/07/2026',
            'building' => 'Học trực tuyến',
            'department' => 'Khoa CNTT',
            'content' => 'SHCN lớp 22DTH1',
            'incident' => 'Có SH',
            'recognition_date' => '09/07/2026',
            'employee' => 'Thầy Phúc',
        ]);

        $this->schedule([
            'id' => 'homeroom-offline-other-campus',
            'date' => '10/07/2026',
            'building' => 'Dãy nhà A',
            'department' => 'Khoa CNTT',
            'content' => 'SHCN lớp 22DTH2',
            'incident' => 'Có SH',
            'recognition_date' => '10/07/2026',
            'employee' => 'Thầy Phúc',
        ]);

        // Campus aliases: map "Cơ sở Tân Hưng" / note to Dãy nhà M via BuildingBlock
        BuildingBlock::query()->where('id', 'cs1')->update([
            'code' => 'M',
            'name' => 'Dãy nhà M',
            'note' => 'Cơ sở Tân Hưng',
        ]);

        $data = app(MonthlyReportStatsService::class)->buildReportData(
            '01/07/2026',
            '31/07/2026',
            'Cơ sở Tân Hưng',
            [],
            ['Khoa CNTT'],
        );

        $this->assertSame(1, $data['metrics'][7]); // in-person notable at campus
        $this->assertSame(1, $data['metrics'][8]); // chuyển phòng
        $this->assertSame(1, $data['metrics'][19]); // online recorded for Khoa CNTT only
        $this->assertSame(1, $data['metrics'][23]); // online báo nghỉ
        $this->assertSame(1, $data['metrics'][39]); // homeroom recorded: online Khoa CNTT (offline other campus excluded)
        $this->assertSame(0, $data['metrics'][40]); // onsite
        $this->assertSame(1, $data['metrics'][41]); // online CVHT
        $this->assertSame(1, $data['metrics'][42]); // Có SH
    }

    public function test_employee_aliases_filter_recorded_schedules(): void
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
            'department' => 'Khoa CNTT',
            'content' => 'Lập trình web',
            'incident' => 'Báo nghỉ',
            'recognition_date' => '10/07/2026',
            'employee' => 'Thầy Phúc',
        ]);

        $this->schedule([
            'id' => 'online-by-other',
            'date' => '11/07/2026',
            'building' => 'Trực tuyến',
            'department' => 'Khoa CNTT',
            'content' => 'CSDL',
            'incident' => 'Báo nghỉ',
            'recognition_date' => '11/07/2026',
            'employee' => 'Thầy Hải',
        ]);

        $data = app(MonthlyReportStatsService::class)->buildReportData(
            '01/07/2026',
            '31/07/2026',
            '',
            ['Nguyễn Vĩnh Phúc'],
            ['Khoa CNTT'],
        );

        $this->assertSame(1, $data['metrics'][19]);
        $this->assertSame(1, $data['metrics'][23]);
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
