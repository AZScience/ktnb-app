<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_accepts_empty_optional_dates(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('announcements.store'), [
            'title' => 'Thông báo test',
            'body' => '<p>Nội dung</p>',
            'published_from' => '',
            'published_until' => '',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('announcements', [
            'title' => 'Thông báo test',
        ]);
    }

    public function test_store_accepts_media_only_body(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('announcements.store'), [
            'title' => 'Thông báo có ảnh',
            'body' => '<figure class="image"><img src="/storage/ckeditor/test.jpg" alt="Ảnh"></figure>',
            'published_from' => null,
            'published_until' => null,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('announcements', [
            'title' => 'Thông báo có ảnh',
        ]);
    }
}
