<?php

namespace App\Support;

use App\Models\{Enquiry, PlanRequest, RenewalRequest};

/**
 * The owner's menu. Five sections; each section has its own row of tabs.
 * Keeping it in one place means the layout never needs to know which page belongs where.
 */
class AdminNav
{
    public static function sections(): array
    {
        $dummy = \Illuminate\Support\Facades\Cache::remember('dummy_total', 30, fn () => Dummy::total());
        $c = self::counts();

        return [
            'today' => ['label' => 'Today', 'icon' => 'calendar', 'route' => 'admin.dashboard', 'tabs' => [
                ['Today', 'admin.dashboard', ['admin.dashboard']],
            ]],
            'members' => ['label' => 'Members', 'icon' => 'users', 'route' => 'admin.members.index', 'tabs' => [
                ['Members', 'admin.members.index', ['admin.members.*']],
                ['Reminders', 'admin.renewals', ['admin.renewals', 'admin.renewals.*', 'admin.reminders.*'], $c['reminders']],
                ['Enquiries', 'admin.enquiries.index', ['admin.enquiries.*'], $c['enquiries']],
                ['Workout requests', 'admin.requests.index', ['admin.requests.*'], $c['requests']],
                ['Announcements', 'admin.announcements.index', ['admin.announcements.*']],
            ]],
            'money' => ['label' => 'Finances', 'icon' => 'card', 'route' => 'admin.payments.index', 'tabs' => [
                ['Payments', 'admin.payments.index', ['admin.payments.*']],
                ['Expenses', 'admin.expenses.index', ['admin.expenses.*']],
                ['Reports', 'admin.finance', ['admin.finance']],
            ]],
            'library' => ['label' => 'Library', 'icon' => 'book', 'route' => 'admin.exercises.index', 'tabs' => [
                ['Exercises', 'admin.exercises.index', ['admin.exercises.*']],
                ['Workout templates', 'admin.templates.index', ['admin.templates.*']],
                ['Equipment', 'admin.equipment.index', ['admin.equipment.*']],
                ['Food prices', 'admin.foods.index', ['admin.foods.*']],
                ['Meal plans', 'admin.diets.index', ['admin.diets.*']],
            ]],
            'settings' => ['label' => 'Settings', 'icon' => 'sliders', 'route' => 'admin.settings', 'tabs' => array_values(array_filter([
                ['Gym profile', 'admin.settings', ['admin.settings']],
                ['Packages', 'admin.plans.index', ['admin.plans.*']],
                ['Check-in QR', 'admin.checkin.poster', ['admin.checkin.*']],
                ['My account', 'admin.account', ['admin.account']],
                ['Data export', 'admin.export', ['admin.export', 'admin.export.*']],
                ['Activity log', 'admin.audit', ['admin.audit']],
                $dummy > 0 ? ['Sample data', 'admin.placeholders', ['admin.placeholders', 'admin.placeholders.*'], $dummy] : null,
            ]))],
        ];
    }

    /** Which section is the current request in? Falls back to 'today'. */
    public static function current(array $sections): string
    {
        foreach ($sections as $key => $s) {
            foreach ($s['tabs'] as $t) {
                if (request()->routeIs(...$t[2])) return $key;
            }
        }
        return 'today';
    }

    public static function counts(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('admin_nav_counts', 10, fn () => [
            'requests' => PlanRequest::where('status', 'pending')->count(),
            'enquiries' => Enquiry::where('status', 'new')->count(),
            'reminders' => RenewalRequest::where('status', 'pending')->count(),
        ]);
    }

    public static function icon(string $name): string
    {
        $p = [
            'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
            'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
            'card' => '<rect x="1" y="4" width="22" height="16" rx="2"/><path d="M1 10h22"/>',
            'book' => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
            'sliders' => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
            'search' => '<circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
            'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        ];
        return '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($p[$name] ?? '').'</svg>';
    }
}
