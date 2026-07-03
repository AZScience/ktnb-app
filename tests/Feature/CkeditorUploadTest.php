<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CkeditorUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('ckeditor.upload'), [
            'upload' => UploadedFile::fake()->image('banner.jpg'),
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['url', 'fileName', 'mimeType']);
        $this->assertStringContainsString('/storage/ckeditor/', $response->json('url'));
    }
}
