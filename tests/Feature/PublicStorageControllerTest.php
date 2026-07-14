<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicStorageControllerTest extends TestCase
{
    public function test_serves_file_from_public_disk_without_symlink(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('evidence/2026/07/demo.txt', 'hello-evidence');

        $response = $this->get('/storage/evidence/2026/07/demo.txt');

        $response->assertOk();
        $this->assertSame('hello-evidence', $response->streamedContent());
    }

    public function test_missing_file_returns_404(): void
    {
        Storage::fake('public');

        $this->get('/storage/evidence/missing.jpg')->assertNotFound();
    }
}
