<?php

namespace App\Support;

use App\Models\{Enquiry, PlanRequest, RenewalRequest};

/**
 * The owner's menu: every page, grouped, always visible in the left sidebar (no dropdowns).
 * Each item is [label, route, [route patterns that count as "on"], icon, optional count badge].
 * Keeping it in one place means the layout never needs to know which page belongs where.
 */
class AdminNav
{
    public static function groups(): array
    {
        $dummy = \Illuminate\Support\Facades\Cache::remember('dummy_total', 30, fn () => Dummy::total());
        $c = self::counts();

        return [
            ['label' => 'Overview', 'items' => [
                ['Today', 'admin.dashboard', ['admin.dashboard'], 'home'],
            ]],
            ['label' => 'People', 'items' => [
                ['Members', 'admin.members.index', ['admin.members.*'], 'users'],
                ['Enquiries', 'admin.enquiries.index', ['admin.enquiries.*'], 'inbox', $c['enquiries']],
                ['Renewals', 'admin.renewals', ['admin.renewals', 'admin.renewals.*'], 'refresh', $c['renewals']],
                ['Announcements', 'admin.announcements.index', ['admin.announcements.*'], 'megaphone'],
            ]],
            ['label' => 'Training', 'items' => [
                ['Workout requests', 'admin.requests.index', ['admin.requests.*'], 'clipboard', $c['requests']],
                ['Meal plans', 'admin.diets.index', ['admin.diets.*'], 'food'],
                ['Exercises', 'admin.exercises.index', ['admin.exercises.*'], 'dumbbell'],
                ['Workout templates', 'admin.templates.index', ['admin.templates.*'], 'layers'],
                ['Equipment', 'admin.equipment.index', ['admin.equipment.*'], 'box', $c['equipment'] ?: null],
                ['Dishes', 'admin.dishes.index', ['admin.dishes.*'], 'food'],
                ['Foods', 'admin.foods.index', ['admin.foods.*'], 'leaf'],
            ]],
            ['label' => 'Money', 'items' => [
                ['Payments', 'admin.payments.index', ['admin.payments.*'], 'card'],
                ['Packages', 'admin.plans.index', ['admin.plans.*'], 'tag'],
                ['Expenses', 'admin.expenses.index', ['admin.expenses.*'], 'receipt'],
                ['Reports', 'admin.finance', ['admin.finance'], 'chart'],
            ]],
            ['label' => 'Gym', 'items' => array_values(array_filter([
                ['Check-in QR', 'admin.checkin.poster', ['admin.checkin.*'], 'qr'],
                ['Gym profile', 'admin.settings', ['admin.settings'], 'sliders'],
                ['My account', 'admin.account', ['admin.account'], 'user'],
                ['Data export', 'admin.export', ['admin.export', 'admin.export.*'], 'download'],
                ['Activity log', 'admin.audit', ['admin.audit'], 'history'],
                $dummy > 0 ? ['Sample data', 'admin.placeholders', ['admin.placeholders', 'admin.placeholders.*'], 'flask', $dummy] : null,
            ]))],
        ];
    }

    /** The label of the page we are on, for the top bar on small screens. */
    public static function currentLabel(array $groups): string
    {
        foreach ($groups as $g) foreach ($g['items'] as $i) if (request()->routeIs(...$i[2])) return $i[0];
        return 'Today';
    }

    public static function counts(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('admin_nav_counts', 10, fn () => [
            'requests' => PlanRequest::where('status', 'pending')->count(),
            'enquiries' => Enquiry::where('status', 'new')->count(),
            'renewals' => RenewalRequest::where('status', 'pending')->count(),
            'equipment' => \App\Models\Equipment::where('out_of_order', true)->count(),
        ]);
    }

    public static function icon(string $name): string
    {
        $p = [
            'home' => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
            'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
            'inbox' => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.5 5h13L22 12v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6z"/>',
            'refresh' => '<path d="M21 12a9 9 0 0 1-15.5 6.2L3 16"/><path d="M3 12A9 9 0 0 1 18.5 5.8L21 8"/><path d="M21 3v5h-5M3 21v-5h5"/>',
            'megaphone' => '<path d="M3 11v2a1 1 0 0 0 1 1h3l6 4V6L7 10H4a1 1 0 0 0-1 1z"/><path d="M16.5 8.5a5 5 0 0 1 0 7"/>',
            'clipboard' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1M9 11h6M9 15h4"/>',
            'food' => '<path d="M4 3v8a3 3 0 0 0 3 3v7M7 3v6M10 3v8a3 3 0 0 1-3 3M18 21V3c-2 1-3 4-3 8h3"/>',
            'dumbbell' => '<path d="M6.5 6.5v11M17.5 6.5v11M3 9v6M21 9v6M6.5 12h11"/>',
            'layers' => '<path d="M12 3l9 5-9 5-9-5z"/><path d="M3 13l9 5 9-5"/>',
            'box' => '<path d="M21 8l-9-5-9 5 9 5z"/><path d="M3 8v8l9 5 9-5V8M12 13v8"/>',
            'leaf' => '<path d="M5 21c0-9 5-15 16-16 0 11-6 16-14 16"/><path d="M5 21c3-6 6-9 11-11"/>',
            'card' => '<rect x="1" y="4" width="22" height="16" rx="2"/><path d="M1 10h22"/>',
            'tag' => '<path d="M20 12l-8 8-9-9V3h8z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
            'receipt' => '<path d="M5 3h14v18l-3-2-2 2-2-2-2 2-2-2-3 2z"/><path d="M9 8h6M9 12h6"/>',
            'chart' => '<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/>',
            'qr' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14v3M14 20h3M20 20h1"/>',
            'sliders' => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
            'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
            'download' => '<path d="M12 3v12M7 10l5 5 5-5"/><path d="M4 21h16"/>',
            'history' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/>',
            'flask' => '<path d="M9 3h6M10 3v6L4.5 19a1.5 1.5 0 0 0 1.3 2h12.4a1.5 1.5 0 0 0 1.3-2L14 9V3"/><path d="M7 15h10"/>',
            'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
            'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
            'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
            'search' => '<circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
            'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
            'check' => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
            'alert' => '<path d="M12 3l10 18H2z"/><path d="M12 10v5M12 18h.01"/>',
            'wrench' => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.5-.5-.5-2.5z"/>',
        ];
        return '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($p[$name] ?? '').'</svg>';
    }
}
