<?php

namespace Tests\Feature;

use App\Models\DailySchedule;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringScheduleCopyTest extends TestCase
{
    use RefreshDatabase;

    private function systemUser(): User
    {
        Role::create([
            'id' => 'system',
            'name' => 'Hệ thống',
            'permissions' => [],
        ]);

        $user = User::factory()->create([
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        Employee::create([
            'id' => 'admin-employee',
            'user_id' => $user->id,
            'employee_id' => 'NTT-00001',
            'name' => $user->name,
            'nickname' => 'Admin',
            'email' => $user->email,
            'role_id' => 'system',
        ]);

        return $user;
    }

    public function test_store_without_recording_fields_does_not_mark_row_handled(): void
    {
        $user = $this->systemUser();

        $response = $this->actingAs($user)->postJson(route('monitoring.schedules.store', ['module' => 'in-person']), [
            'date' => '17/07/2026',
            'building' => 'CS1',
            'room' => 'A101',
            'period' => '1-3',
            'class' => '22DTH1',
            'lecturer' => 'GV A',
            'content' => 'Lập trình web',
            'status' => 'Phòng học',
        ]);

        $response->assertCreated();

        $item = $response->json('item');
        $this->assertSame('', trim((string) ($item['employee'] ?? '')));
        $this->assertSame('', trim((string) ($item['recognition_date'] ?? '')));

        $this->assertDatabaseHas('daily_schedules', [
            'id' => $item['id'],
            'employee' => '',
            'recognition_date' => '',
        ]);

        $this->assertFalse(DailySchedule::isRecorded(DailySchedule::find($item['id'])));
    }

    public function test_update_with_recording_fields_marks_row_handled(): void
    {
        $user = $this->systemUser();

        $schedule = DailySchedule::create([
            'id' => 'sched-copy-test-1',
            'date' => '17/07/2026',
            'building' => 'CS1',
            'room' => 'A101',
            'period' => '1-3',
            'class' => '22DTH1',
            'content' => 'Lập trình web',
            'status' => 'Phòng học',
            'employee' => '',
            'recognition_date' => '',
        ]);

        $response = $this->actingAs($user)->putJson(
            route('monitoring.schedules.update', ['module' => 'in-person', 'schedule' => $schedule->id]),
            [
                'employee' => 'Thầy Phúc',
                'recognition_date' => '17/07/2026',
                'incident' => 'Bình thường',
                'attending_students' => 30,
            ]
        );

        $response->assertOk();
        $this->assertTrue(DailySchedule::isRecorded($schedule->fresh()));
    }

    public function test_clear_recording_unmarks_handled_row(): void
    {
        $user = $this->systemUser();

        $schedule = DailySchedule::create([
            'id' => 'sched-clear-recording-1',
            'date' => '17/07/2026',
            'building' => 'CS1',
            'room' => 'A101',
            'period' => '1-3',
            'class' => '22DTH1',
            'content' => 'Lập trình web',
            'status' => 'Phòng học',
            'employee' => 'Thầy Phúc',
            'recognition_date' => '17/07/2026',
            'incident' => 'Bình thường',
            'attending_students' => 30,
            'is_notification' => true,
        ]);

        $this->assertTrue(DailySchedule::isRecorded($schedule));

        $response = $this->actingAs($user)->postJson(
            route('monitoring.schedules.clear-recording', ['module' => 'in-person', 'schedule' => $schedule->id]),
        );

        $response->assertOk()
            ->assertJsonPath('message', 'Đã hủy ghi nhận.');

        $item = $response->json('item');
        $this->assertSame('', trim((string) ($item['employee'] ?? '')));
        $this->assertSame('', trim((string) ($item['recognition_date'] ?? '')));

        $fresh = $schedule->fresh();
        $this->assertFalse(DailySchedule::isRecorded($fresh));
        $this->assertSame('', (string) $fresh->incident);
        $this->assertNull($fresh->attending_students);
        $this->assertFalse((bool) $fresh->is_notification);
    }
}
