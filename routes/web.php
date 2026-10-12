<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Member;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/pricing', [PageController::class, 'pricing'])->name('pricing');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'enquire'])->middleware('throttle:5,1')->name('enquiry.store');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/dashboard', [AuthController::class, 'home'])->name('dashboard');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/password', [AuthController::class, 'showPassword'])->name('password.edit');
    Route::put('/password', [AuthController::class, 'updatePassword'])->name('password.update');
});

/* ------------------------------- OWNER / ADMIN ------------------------------- */
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('members', Admin\MemberController::class);
    Route::post('members/{member}/password', [Admin\MemberController::class, 'resetPassword'])->name('members.password');
    Route::post('members/{member}/freeze', [Admin\MemberController::class, 'toggleFreeze'])->name('members.freeze');
    Route::post('members/{member}/memberships', [Admin\MemberController::class, 'addMembership'])->name('members.memberships.store');

    Route::get('renewals', [Admin\ReportController::class, 'renewals'])->name('renewals');
    Route::post('reminders/sent', [Admin\ReportController::class, 'sent'])->name('reminders.sent');
    Route::post('members/{member}/checkin', [Admin\MemberController::class, 'checkin'])->name('members.checkin');
    Route::post('checkin', [Admin\MemberController::class, 'quickCheckin'])->name('checkin.quick');
    Route::get('checkin-poster', [Admin\CheckinController::class, 'poster'])->name('checkin.poster');
    Route::post('checkin-poster/renew', [Admin\CheckinController::class, 'renew'])->name('checkin.renew');
    Route::resource('enquiries', Admin\EnquiryController::class)->only(['index', 'create', 'store', 'update', 'destroy']);
    Route::post('renewals/{renewalRequest}/done', [Admin\ReportController::class, 'renewalDone'])->name('renewals.done');
    Route::get('quiet', fn () => redirect()->route('admin.members.index', ['filter' => 'quiet']))->name('quiet');

    Route::resource('plans', Admin\PlanController::class)->except('show');

    Route::get('payments/daily', [Admin\PaymentController::class, 'daily'])->name('payments.daily');
    Route::resource('payments', Admin\PaymentController::class)->only(['index', 'store', 'show']);
    Route::resource('expenses', Admin\ExpenseController::class)->except('show');
    Route::get('finance', [Admin\FinanceController::class, 'index'])->name('finance');

    Route::get('requests', [Admin\PlanRequestController::class, 'index'])->name('requests.index');
    Route::get('requests/{planRequest}', [Admin\PlanRequestController::class, 'show'])->name('requests.show');
    Route::post('requests/{planRequest}/draft', [Admin\PlanRequestController::class, 'draft'])->name('requests.draft');
    Route::put('requests/{planRequest}/plan', [Admin\PlanRequestController::class, 'updatePlan'])->name('requests.update');
    Route::post('requests/{planRequest}/approve', [Admin\PlanRequestController::class, 'approve'])->name('requests.approve');
    Route::post('requests/{planRequest}/reject', [Admin\PlanRequestController::class, 'reject'])->name('requests.reject');

    Route::resource('templates', Admin\TemplateController::class)->except('show');
    Route::resource('exercises', Admin\ExerciseController::class)->except('show');
    Route::resource('equipment', Admin\EquipmentController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::post('foods/import', [Admin\FoodController::class, 'import'])->name('foods.import');
    Route::post('foods/feed', [Admin\FoodController::class, 'feed'])->name('foods.feed');
    Route::resource('foods', Admin\FoodController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('diets', Admin\DietController::class)->only(['index', 'show', 'create', 'store', 'destroy']);

    Route::resource('announcements', Admin\AnnouncementController::class)->except('show');
    Route::get('audit', [Admin\SystemController::class, 'audit'])->name('audit');
    Route::get('export', [Admin\SystemController::class, 'exportPage'])->name('export');
    Route::get('export/{type}', [Admin\SystemController::class, 'export'])->name('export.csv');
    Route::get('settings', [Admin\SystemController::class, 'settings'])->name('settings');
    Route::get('account', [Admin\SystemController::class, 'account'])->name('account');
    Route::put('settings', [Admin\SystemController::class, 'saveSettings'])->name('settings.save');
    Route::put('settings/account', [Admin\SystemController::class, 'saveAccount'])->name('settings.account');
    Route::get('placeholders', [Admin\SystemController::class, 'placeholders'])->name('placeholders');
    Route::post('placeholders/purge', [Admin\SystemController::class, 'purge'])->name('placeholders.purge');
});

/* ---------------------------------- MEMBER ---------------------------------- */
Route::middleware(['auth', 'role:member'])->prefix('app')->name('member.')->group(function () {
    Route::get('/', [Member\HomeController::class, 'index'])->name('home');
    Route::get('me', [Member\HomeController::class, 'more'])->name('me');
    Route::get('checkin', [Member\HomeController::class, 'checkin'])->name('checkin');
    Route::get('membership', [Member\HomeController::class, 'membership'])->name('membership');
    Route::post('membership/renew', [Member\HomeController::class, 'renew'])->name('renew');
    Route::get('announcements', [Member\HomeController::class, 'announcements'])->name('announcements');

    Route::get('workout', [Member\WorkoutController::class, 'index'])->name('workout');
    Route::post('workout/request', [Member\WorkoutController::class, 'requestPlan'])->name('workout.request');
    Route::get('workout/day/{day}', [Member\WorkoutController::class, 'day'])->name('workout.day');
    Route::post('workout/log', [Member\WorkoutController::class, 'log'])->name('workout.log');
    Route::post('workout/swap/{item}', [Member\WorkoutController::class, 'swap'])->name('workout.swap');
    Route::get('exercise/{exercise}', [Member\WorkoutController::class, 'exercise'])->name('exercise');

    Route::get('bmi', [Member\ProgressController::class, 'bmi'])->name('bmi');
    Route::post('bmi', [Member\ProgressController::class, 'saveBmi'])->name('bmi.save');
    Route::get('progress', [Member\ProgressController::class, 'index'])->name('progress');
    Route::post('progress/body', [Member\ProgressController::class, 'saveBody'])->name('progress.body');
    Route::post('progress/goal', [Member\ProgressController::class, 'saveGoal'])->name('progress.goal');
    Route::post('progress/goal/{goal}/toggle', [Member\ProgressController::class, 'toggleGoal'])->name('progress.goal.toggle');

    Route::get('diet', [Member\DietController::class, 'index'])->name('diet');
    Route::post('diet', [Member\DietController::class, 'generate'])->name('diet.generate');
    Route::get('diet/shopping', [Member\DietController::class, 'shopping'])->name('diet.shopping');
    Route::post('diet/swap', [Member\DietController::class, 'swap'])->name('diet.swap');
});
