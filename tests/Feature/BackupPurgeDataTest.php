<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BackupPurgeDataTest extends TestCase
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
            'email' => $user->email,
            'role_id' => 'system',
        ]);

        return $user;
    }

    public function test_purge_activity_logs_within_date_range(): void
    {
        $user = $this->systemUser();

        DB::table('activity_logs')->insert([
            [
                'id' => (string) Str::uuid(),
                'logged_at' => '2026-07-10 10:00:00',
                'user_id' => $user->id,
                'user_email' => $user->email,
                'action' => 'login',
                'target_type' => 'user',
                'details' => 'Test log in range',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'logged_at' => '2026-06-01 10:00:00',
                'user_id' => $user->id,
                'user_email' => $user->email,
                'action' => 'login',
                'target_type' => 'user',
                'details' => 'Test log out of range',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($user)->postJson(route('backup.purge-data'), [
            'from_date' => '2026-07-01',
            'to_date' => '2026-07-31',
            'types' => ['activity_logs'],
        ]);

        $response->assertOk();
        $response->assertJsonPath('deleted', 1);
        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertDatabaseHas('activity_logs', [
            'details' => 'Test log out of range',
        ]);
        $this->assertDatabaseMissing('activity_logs', [
            'details' => 'Test log in range',
        ]);
    }
}
