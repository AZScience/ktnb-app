<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicStorageControllerTest extends TestCase
{
    use RefreshDatabase;
    public function test_guest_cannot_access_evidence_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('evidence/2026/07/demo.txt', 'hello-evidence');

        $this->get('/storage/evidence/2026/07/demo.txt')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_serve_evidence_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('evidence/2026/07/demo.txt', 'hello-evidence');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/storage/evidence/2026/07/demo.txt');

        $response->assertOk();
        $this->assertSame('hello-evidence', $response->streamedContent());
    }

    public function test_missing_file_returns_404(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)->get('/storage/evidence/missing.jpg')->assertNotFound();
    }

    public function test_path_traversal_is_rejected(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('evidence/secret.txt', 'secret');

        $user = User::factory()->create();

        $this->actingAs($user)->get('/storage/evidence/../secret.txt')->assertNotFound();
    }
}
