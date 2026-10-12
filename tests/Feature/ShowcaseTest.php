<?php

namespace Tests\Feature;

use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowcaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_has_a_spot_for_every_photo_and_shows_placeholders_until_they_are_added(): void
    {
        $this->seed(DummySeeder::class);
        $html = $this->get('/')->assertOk()->getContent();
        foreach (['Take a look around', 'Meet your trainer', 'Train for what you want', 'Weight management', 'Muscle building', 'General fitness'] as $w) {
            $this->assertStringContainsString(str_replace(' a look', ' a look', $w), preg_replace('/<br>\s*/', ' ', $html), $w);
        }
        $this->assertGreaterThanOrEqual(10, substr_count($html, 'class="photo '), 'photo spots are on the page');
        $this->assertStringNotContainsString('<img src="/img/gym/', $html, 'no photos yet, so only placeholders');
    }

    public function test_a_photo_dropped_in_the_folder_shows_up_by_itself(): void
    {
        $this->seed(DummySeeder::class);
        $file = public_path('img/gym/machines.png');
        file_put_contents($file, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
        try {
            $this->assertNotNull(gym_img('machines'));
            $this->get('/')->assertOk()->assertSee('/img/gym/machines.png', false);
        } finally { @unlink($file); }
        $this->assertNull(gym_img('machines'));
    }

    public function test_owner_sees_which_photo_spots_are_filled(): void
    {
        $this->seed(DummySeeder::class);
        $owner = \App\Models\User::where('role', 'admin')->first();
        $this->actingAs($owner)->get(route('admin.settings'))->assertOk()->assertSee('Website photos')->assertSee('hero-1.jpg')->assertSee('trainer.jpg');
    }
}
