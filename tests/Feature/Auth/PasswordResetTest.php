<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\SystemSmtpMailerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        $this->mockSmtpSender();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');
    }

    public function test_reset_password_link_requires_configured_smtp(): void
    {
        $this->mock(SystemSmtpMailerService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(false);
        });

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasErrors('email');
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $response = $this->get('/reset-password/'.$token.'?email='.urlencode($user->email));

        $response->assertStatus(200);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));
    }

    private function mockSmtpSender(): void
    {
        $this->mock(SystemSmtpMailerService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('credentialsFromParameters')->andReturn([
                'host' => 'smtp.test',
                'port' => '587',
                'user' => 'mailer@test.local',
                'pass' => 'secret',
                'fromName' => 'Test',
            ]);
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(fn (Email $email) => $email->getSubject() !== '');
        });
    }
}
