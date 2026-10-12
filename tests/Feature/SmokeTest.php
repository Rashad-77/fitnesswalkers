<?php

namespace Tests\Feature;

use App\Models\{Member, User};
use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_page_loads_for_owner_and_member(): void
    {
        $this->seed(DummySeeder::class);

        $owner = User::where('role', 'admin')->first();
        $this->actingAs($owner);
        $pages = 0;
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (! $name || ! str_starts_with($name, 'admin.') || ! in_array('GET', $route->methods())) continue;
            if (in_array($name, ['admin.export.csv', 'admin.quiet'])) continue; // csv is a download, quiet is a redirect
            $uri = $route->uri();
            $params = [];
            foreach ($route->parameterNames() as $p) $params[$p] = $this->sampleKey($p);
            $url = route($name, $params);
            $res = $this->get($url);
            $res->assertOk();
            $pages++;
        }
        foreach (['members', 'memberships', 'payments', 'expenses', 'foods'] as $t) {
            $this->get(route('admin.export.csv', $t))->assertOk();
        }

        // member pages
        $member = Member::first();
        $this->actingAs($member->user);
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (! $name || ! str_starts_with($name, 'member.') || ! in_array('GET', $route->methods())) continue;
            if (in_array($name, ['member.checkin', 'member.bmi'])) continue; // both redirect
            $params = [];
            foreach ($route->parameterNames() as $p) $params[$p] = $p === 'day' ? 1 : $this->sampleKey($p);
            $this->get(route($name, $params))->assertOk();
            $pages++;
        }
        $this->assertGreaterThan(40, $pages);
    }

    private function sampleKey(string $param)
    {
        return match ($param) {
            'member' => Member::first()->id,
            'plan' => \App\Models\Plan::first()->id,
            'payment' => \App\Models\Payment::first()->id,
            'expense' => \App\Models\Expense::first()->id,
            'planRequest' => \App\Models\PlanRequest::first()->id,
            'template' => \App\Models\WorkoutTemplate::first()->id,
            'exercise' => \App\Models\Exercise::first()->id,
            'announcement' => \App\Models\Announcement::first()->id,
            'diet' => \App\Models\DietPlan::where('source', 'app')->first()->id,
            'day' => 1,
            default => 1,
        };
    }
}
