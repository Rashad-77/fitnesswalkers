<?php

namespace Tests\Feature;

use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_open_without_login(): void
    {
        $this->seed(DummySeeder::class);
        foreach (['/', '/about', '/pricing', '/contact', '/privacy', '/terms'] as $url) {
            $this->get($url)->assertOk()->assertSee('FitnessWalkers');
        }
    }

    public function test_pricing_lists_active_plans_in_rupees(): void
    {
        $this->seed(DummySeeder::class);
        $this->get('/pricing')->assertSee('Monthly')->assertSee('Rs. 5,000')->assertSee('Annual');
    }

    public function test_inactive_plan_is_hidden(): void
    {
        $this->seed(DummySeeder::class);
        \App\Models\Plan::where('name', 'Annual')->update(['active' => false]);
        $this->get('/pricing')->assertDontSee('Annual');
    }

    public function test_contact_has_whatsapp_and_map_links(): void
    {
        $this->seed(DummySeeder::class);
        $this->get('/contact')->assertSee('wa.me/94770000000')->assertSee('google.com/maps');
    }

    public function test_logged_in_user_sees_dashboard_link_and_dashboard_route_redirects(): void
    {
        $this->seed(DummySeeder::class);
        $this->post('/login', ['email' => 'owner@fitnesswalkers.test', 'password' => 'password']);
        $this->get('/')->assertOk()->assertSee('My dashboard');
        $this->get('/dashboard')->assertRedirect('/admin');
    }

    public function test_pages_work_with_empty_database(): void
    {
        foreach (['/', '/pricing', '/contact'] as $url) $this->get($url)->assertOk();
    }
}
