<?php

namespace Database\Seeders;

use App\Models\{Announcement, Attendance, BodyLog, Enquiry, DietPlan, Equipment, Exercise, Expense, Food, Goal, Member, Membership, Payment, Plan, PlanRequest, RenewalRequest, Setting, User, WorkoutLog, WorkoutPlan, WorkoutPlanItem, WorkoutTemplate, WorkoutTemplateItem};
use App\Services\Bmi;
use App\Services\DietGenerator;
use Illuminate\Database\Seeder;

/**
 * EVERYTHING created here is a placeholder and carries is_dummy = true.
 * See DUMMY_DATA.md. Run `php artisan dummy:list` to see what's left and
 * `php artisan dummy:purge` to remove it all once real data is in.
 */
class DummySeeder extends Seeder
{
    private array $d = ['is_dummy' => true];

    public function run(): void
    {
        $this->settings();
        $this->users();
        $this->training();
        $this->foods();
        $this->members();
        $this->extras();
    }

    private function settings(): void
    {
        // gym_name is real (given by the owner); the rest are placeholders.
        Setting::put('gym_name', 'FitnessWalkers', false);
        Setting::put('owner_name', 'Gym Owner', true);
        Setting::put('gym_phone', '077 000 0000', true);
        Setting::put('gym_address', '123 Sample Road, Your Town, Sri Lanka', true);
        Setting::put('gym_hours', 'Mon-Sat 5:30 AM - 9:00 PM, Sun 6:00 AM - 12:00 PM', true);
        Setting::put('gym_email', 'hello@fitnesswalkers.example', true);
        Setting::put('tagline', 'Train with purpose. Walk in as you are, walk out stronger.', true);
        Setting::put('about_text', "FitnessWalkers is a friendly local gym where every member is trained personally by the owner. We keep the gym small on purpose, so you get real attention, a plan that fits you, and someone who notices when you improve.\n\nWhether you are starting for the first time, getting back after a break, or chasing a goal, we help you build a routine you can keep.", true);
        Setting::put('bank_details', 'Sample Bank, Acc 0000000000, Branch Town', true);
        Setting::put('receipt_footer', 'Thank you for training with FitnessWalkers. Fees are not refundable.', true);
    }

    private function users(): void
    {
        User::create(['name' => 'Gym Owner', 'email' => 'owner@fitnesswalkers.test', 'phone' => '0770000000', 'role' => 'admin', 'password' => 'password'] + $this->d);
    }

    private function training(): void
    {
        $eq = [];
        foreach ([
            ['Leg Press Machine', 'machine'], ['Chest Press Machine', 'machine'], ['Lat Pulldown Machine', 'machine'],
            ['Seated Cable Row', 'machine'], ['Leg Extension Machine', 'machine'], ['Leg Curl Machine', 'machine', true],
            ['Shoulder Press Machine', 'machine'], ['Pec Deck', 'machine'], ['Barbell and Squat Rack', 'free weights'],
            ['Flat Bench', 'free weights'], ['Dumbbells', 'free weights'], ['Pull-up Bar', 'bodyweight'],
            ['Treadmill', 'cardio'], ['Stationary Bike', 'cardio'],
        ] as $e) {
            $eq[$e[0]] = Equipment::create(['name' => $e[0], 'category' => $e[1], 'out_of_order' => $e[2] ?? false] + $this->d);
        }

        $ex = [
            // name, muscle, equipment, steps, mistakes
            ['Leg Press', 'Legs', 'Leg Press Machine', "Sit back with feet shoulder-width on the platform.\nRelease the safety handles.\nLower until knees reach about 90 degrees.\nPress through the heels without locking the knees.", 'Locking knees at the top; letting the lower back lift off the seat.'],
            ['Machine Chest Press', 'Chest', 'Chest Press Machine', "Adjust the seat so the handles are at mid-chest.\nPush forward until arms are nearly straight.\nReturn slowly.", 'Shrugging shoulders; flaring elbows too high.'],
            ['Lat Pulldown', 'Back', 'Lat Pulldown Machine', "Grip the bar slightly wider than shoulders.\nLean back a little and pull to the upper chest.\nControl the bar back up.", 'Pulling behind the neck; swinging the body.'],
            ['Seated Cable Row', 'Back', 'Seated Cable Row', "Sit tall with a slight knee bend.\nPull the handle to your stomach, squeezing the shoulder blades.\nReturn slowly.", 'Rounding the back; using momentum.'],
            ['Leg Extension', 'Legs', 'Leg Extension Machine', "Set the pad on the lower shin.\nExtend the knees until legs are straight.\nLower under control.", 'Using too much weight and jerking.'],
            ['Leg Curl', 'Legs', 'Leg Curl Machine', "Lie face down, pad above the heels.\nCurl the heels toward your glutes.\nLower slowly.", 'Lifting the hips off the pad.'],
            ['Machine Shoulder Press', 'Shoulders', 'Shoulder Press Machine', "Adjust the seat so the handles are at shoulder height.\nPress up until arms are almost straight.\nLower slowly.", 'Arching the lower back.'],
            ['Pec Deck Fly', 'Chest', 'Pec Deck', "Sit with back flat and elbows at shoulder height.\nBring the arms together in front of you.\nOpen slowly.", 'Going too heavy and rounding shoulders forward.'],
            ['Barbell Back Squat', 'Legs', 'Barbell and Squat Rack', "Bar on the upper back, feet shoulder-width.\nSit down and back until thighs are parallel.\nStand by driving through the whole foot.", 'Knees caving in; heels lifting; rounded back.'],
            ['Barbell Bench Press', 'Chest', 'Flat Bench', "Lie with eyes under the bar, feet flat.\nLower the bar to mid-chest.\nPress up in a straight line. Use a spotter for heavy sets.", 'Bouncing the bar; lifting the hips.'],
            ['Dumbbell Shoulder Press', 'Shoulders', 'Dumbbells', "Hold dumbbells at shoulder height.\nPress overhead until arms are straight.\nLower slowly.", 'Over-arching the back.'],
            ['Dumbbell Bicep Curl', 'Arms', 'Dumbbells', "Stand tall, elbows by your sides.\nCurl the weights to shoulder height.\nLower slowly.", 'Swinging the torso.'],
            ['Dumbbell Row', 'Back', 'Dumbbells', "One hand and knee on the bench.\nPull the dumbbell to your hip.\nLower with control.", 'Twisting the torso; shrugging.'],
            ['Dumbbell Lunge', 'Legs', 'Dumbbells', "Hold dumbbells at your sides.\nStep forward and lower the back knee toward the floor.\nPush back to standing.", 'Front knee collapsing inward; short steps.'],
            ['Dumbbell Triceps Extension', 'Arms', 'Dumbbells', "Hold one dumbbell overhead with both hands.\nBend elbows to lower it behind the head.\nExtend back up.", 'Flaring the elbows wide.'],
            ['Pull-up', 'Back', 'Pull-up Bar', "Hang with hands just wider than shoulders.\nPull until the chin is over the bar.\nLower fully.", 'Kipping; half reps.'],
            ['Treadmill Brisk Walk', 'Cardio', 'Treadmill', "Set a speed where you can still talk.\nKeep posture tall and arms swinging.\n10-30 minutes.", 'Holding the handrails the whole time.'],
            ['Stationary Bike', 'Cardio', 'Stationary Bike', "Set the seat at hip height.\nPedal steadily at moderate resistance.\n10-30 minutes.", 'Seat too low; bouncing in the saddle.'],
            ['Push-up', 'Chest', null, "Hands under shoulders, body in a straight line.\nLower the chest toward the floor.\nPush back up. Knees down is fine to start.", 'Sagging hips; flaring elbows.'],
            ['Bodyweight Squat', 'Legs', null, "Feet shoulder-width.\nSit back and down, chest up.\nStand by pushing through the feet.", 'Knees caving in; heels lifting.'],
            ['Plank', 'Core', null, "Forearms on the floor, body in a straight line.\nHold, breathing normally.", 'Sagging hips or lifting them too high.'],
            ['Glute Bridge', 'Legs', null, "Lie on your back, knees bent.\nLift the hips until shoulders, hips, knees are in line.\nLower slowly.", 'Arching the lower back.'],
            ['Crunch', 'Core', null, "Lie back, knees bent, hands by your head.\nCurl the shoulders up a few inches.\nLower slowly.", 'Pulling on the neck.'],
        ];
        $exm = [];
        foreach ($ex as $e) {
            $exm[$e[0]] = Exercise::create([
                'name' => $e[0], 'muscle_group' => $e[1], 'equipment_id' => $e[2] ? $eq[$e[2]]->id : null,
                'steps' => $e[3], 'mistakes' => $e[4], 'clip_url' => null,
            ] + $this->d);
        }

        $tpl = [
            ['Beginner Full Body (3 days)', 'general', 'beginner', 3, [
                1 => [['Leg Press', 3, '12'], ['Machine Chest Press', 3, '12'], ['Lat Pulldown', 3, '12'], ['Plank', 3, '30 sec']],
                2 => [['Bodyweight Squat', 3, '15'], ['Machine Shoulder Press', 3, '12'], ['Seated Cable Row', 3, '12'], ['Crunch', 3, '15']],
                3 => [['Leg Press', 3, '12'], ['Push-up', 3, '10'], ['Dumbbell Row', 3, '12'], ['Glute Bridge', 3, '15']],
            ]],
            ['Fat Loss Circuit (4 days)', 'fat_loss', 'beginner', 4, [
                1 => [['Treadmill Brisk Walk', 1, '20 min'], ['Bodyweight Squat', 3, '15'], ['Push-up', 3, '10'], ['Plank', 3, '30 sec']],
                2 => [['Stationary Bike', 1, '20 min'], ['Dumbbell Lunge', 3, '10'], ['Seated Cable Row', 3, '12'], ['Crunch', 3, '15']],
                3 => [['Treadmill Brisk Walk', 1, '25 min'], ['Leg Press', 3, '15'], ['Machine Chest Press', 3, '12'], ['Glute Bridge', 3, '15']],
                4 => [['Stationary Bike', 1, '25 min'], ['Dumbbell Shoulder Press', 3, '12'], ['Lat Pulldown', 3, '12'], ['Plank', 3, '40 sec']],
            ]],
            ['Muscle Gain Upper/Lower (4 days)', 'muscle_gain', 'intermediate', 4, [
                1 => [['Barbell Bench Press', 4, '8'], ['Lat Pulldown', 4, '10'], ['Dumbbell Shoulder Press', 3, '10'], ['Dumbbell Bicep Curl', 3, '12']],
                2 => [['Barbell Back Squat', 4, '8'], ['Leg Extension', 3, '12'], ['Glute Bridge', 3, '12'], ['Plank', 3, '45 sec']],
                3 => [['Machine Chest Press', 4, '10'], ['Seated Cable Row', 4, '10'], ['Pec Deck Fly', 3, '12'], ['Dumbbell Triceps Extension', 3, '12']],
                4 => [['Leg Press', 4, '10'], ['Dumbbell Lunge', 3, '10'], ['Pull-up', 3, '6'], ['Crunch', 3, '15']],
            ]],
        ];
        foreach ($tpl as [$name, $goal, $level, $days, $plan]) {
            $t = WorkoutTemplate::create(['name' => $name, 'goal' => $goal, 'level' => $level, 'days_per_week' => $days] + $this->d);
            foreach ($plan as $dayNo => $items) {
                foreach ($items as [$en, $sets, $reps]) {
                    WorkoutTemplateItem::create(['workout_template_id' => $t->id, 'day_no' => $dayNo, 'exercise_id' => $exm[$en]->id, 'sets' => $sets, 'reps' => $reps]);
                }
            }
        }
    }

    private function foods(): void
    {
        // name, category, diet_type, allergens, kcal, protein, carbs, fat, Rs/kg (all prices are PLACEHOLDER estimates)
        $rows = [
            ['White Rice, cooked', 'grain', 'vegan', '', 130, 2.7, 28, 0.3, 120],
            ['Red Rice, cooked', 'grain', 'vegan', '', 123, 2.7, 26, 1.0, 140],
            ['Roti (wheat)', 'grain', 'vegan', 'gluten', 297, 9, 50, 7, 420],
            ['Bread, white', 'grain', 'vegan', 'gluten', 265, 9, 49, 3.2, 380],
            ['Oats, dry', 'grain', 'vegan', 'gluten', 389, 17, 66, 7, 1300],
            ['String Hoppers (idiyappam)', 'grain', 'vegan', '', 140, 3, 30, 0.5, 350],
            ['Sweet Potato, boiled', 'grain', 'vegan', '', 90, 2, 21, 0.1, 260],
            ['Chicken Breast, cooked', 'protein', 'non_veg', '', 165, 31, 0, 3.6, 1600],
            ['Egg, boiled', 'protein', 'vegetarian', 'egg', 143, 12.6, 0.7, 9.5, 1000],
            ['Fish (Balaya), cooked', 'protein', 'non_veg', 'fish', 130, 23, 0, 4, 1200],
            ['Dried Sprats (Haalmassa)', 'protein', 'non_veg', 'fish', 290, 52, 0, 8, 1800],
            ['Dhal, cooked', 'legume', 'vegan', '', 116, 9, 20, 0.4, 180],
            ['Chickpeas, cooked', 'legume', 'vegan', '', 164, 8.9, 27, 2.6, 480],
            ['Green Gram (Mung), cooked', 'legume', 'vegan', '', 105, 7, 19, 0.4, 400],
            ['Cowpea, cooked', 'legume', 'vegan', '', 116, 7.7, 21, 0.5, 380],
            ['Soya Meat, dry', 'legume', 'vegan', 'soy', 345, 52, 33, 0.5, 1000],
            ['Tofu', 'legume', 'vegan', 'soy', 76, 8, 1.9, 4.8, 1300],
            ['Milk, fresh', 'dairy', 'vegetarian', 'dairy', 61, 3.2, 4.8, 3.3, 300],
            ['Yoghurt, plain', 'dairy', 'vegetarian', 'dairy', 61, 3.5, 4.7, 3.3, 500],
            ['Mixed Vegetables, cooked', 'veg', 'vegan', '', 40, 2, 7, 0.5, 260],
            ['Gotukola / Leafy Greens', 'veg', 'vegan', '', 25, 2.5, 3, 0.5, 220],
            ['Pumpkin', 'veg', 'vegan', '', 26, 1, 6.5, 0.1, 160],
            ['Brinjal', 'veg', 'vegan', '', 35, 1, 8.7, 0.2, 300],
            ['Green Beans', 'veg', 'vegan', '', 31, 1.8, 7, 0.2, 380],
            ['Carrot', 'veg', 'vegan', '', 41, 0.9, 9.6, 0.2, 320],
            ['Cabbage', 'veg', 'vegan', '', 25, 1.3, 6, 0.1, 220],
            ['Banana', 'fruit', 'vegan', '', 89, 1.1, 23, 0.3, 260],
            ['Papaya', 'fruit', 'vegan', '', 43, 0.5, 11, 0.3, 220],
            ['Pineapple', 'fruit', 'vegan', '', 50, 0.5, 13, 0.1, 320],
            ['Watermelon', 'fruit', 'vegan', '', 30, 0.6, 8, 0.2, 140],
            ['Apple', 'fruit', 'vegan', '', 52, 0.3, 14, 0.2, 750],
            ['Orange', 'fruit', 'vegan', '', 47, 0.9, 12, 0.1, 650],
            ['Coconut Oil', 'fat', 'vegan', '', 884, 0, 0, 100, 950],
            ['Grated Coconut', 'fat', 'vegan', '', 354, 3.3, 15, 33, 320],
            ['Peanuts', 'fat', 'vegan', 'nuts', 567, 26, 16, 49, 750],
        ];
        $tags = function (array $r): string {
            $n = $r[0]; $c = $r[1];
            $t = [];
            if (preg_match('/Roti|Bread|Oats|String Hoppers|Egg|Milk|Yoghurt|Dhal|Banana|Papaya|Pineapple|Watermelon|Apple|Orange|Grated Coconut|Coconut Oil/', $n)) $t[] = 'breakfast';
            if (! in_array($n, ['Oats, dry', 'Milk, fresh', 'Yoghurt, plain', 'Banana', 'Papaya', 'Pineapple', 'Watermelon', 'Apple', 'Orange', 'Roti (wheat)x'])) $t[] = 'main';
            if (preg_match('/Banana|Papaya|Pineapple|Watermelon|Apple|Orange|Milk|Yoghurt|Egg|Peanut|Chickpea|Green Gram|Sweet Potato/', $n)) $t[] = 'snack';
            if ($c === 'fruit') $t = array_diff($t, ['main']);
            if ($c === 'fat') $t = ['breakfast', 'main'];
            if ($n === 'Peanuts') $t = ['main', 'snack'];
            return implode(',', array_unique($t ?: ['main']));
        };
        foreach ($rows as $r) {
            Food::create(['meal_tags' => $tags($r)] + [
                'name' => $r[0], 'category' => $r[1], 'diet_type' => $r[2], 'allergens' => $r[3] ?: null,
                'kcal_100g' => $r[4], 'protein_100g' => $r[5], 'carbs_100g' => $r[6], 'fat_100g' => $r[7],
                'price_per_kg' => $r[8], 'price_updated_on' => now()->subDays(20)->toDateString(),
            ] + $this->d);
        }
    }

    private function members(): void
    {
        $plans = [
            Plan::create(['name' => 'Monthly', 'duration_days' => 30, 'price' => 5000] + $this->d),
            Plan::create(['name' => 'Quarterly', 'duration_days' => 90, 'price' => 13500] + $this->d),
            Plan::create(['name' => 'Annual', 'duration_days' => 365, 'price' => 48000] + $this->d),
        ];
        // name, sex, dob, height, plan idx, days since latest start (negative = starts later), pay fraction, frozen
        $people = [
            ['Kasun Perera', 'male', '1996-03-12', 175, 0, 10, 1.0, false],
            ['Nimali Fernando', 'female', '1994-07-21', 162, 1, 40, 1.0, false],
            ['Dilshan Silva', 'male', '2001-01-30', 180, 0, 27, 1.0, false],
            ['Tharushi Jayasinghe', 'female', '1999-11-05', 158, 0, 25, 0.5, false],
            ['Ruwan Wickramasinghe', 'male', '1988-09-18', 170, 2, 120, 1.0, false],
            ['Sanduni Rathnayake', 'female', '1997-05-02', 165, 1, 85, 1.0, false],
            ['Chamara Bandara', 'male', '1992-12-09', 172, 0, 45, 1.0, false],
            ['Ishara Gunawardena', 'female', '2000-04-14', 160, 0, 8, 0.6, false],
            ['Pradeep Kumara', 'male', '1985-08-25', 168, 1, 100, 1.0, false],
            ['Hasini De Silva', 'female', '1998-02-27', 163, 0, 15, 1.0, true],
            ['Mahesh Gamage', 'male', '1993-06-06', 177, 2, 200, 1.0, false],
            ['Lakmal Senanayake', 'male', '1990-10-10', 174, 0, 60, 1.0, false],
        ];
        $methods = ['cash', 'cash', 'bank', 'card'];
        foreach ($people as $i => [$name, $sex, $dob, $h, $pi, $ago, $frac, $frozen]) {
            $slug = strtolower(explode(' ', $name)[0]);
            $u = User::create(['name' => $name, 'email' => "$slug@example.test", 'phone' => '07700000'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT), 'role' => 'member', 'password' => 'password'] + $this->d);
            $m = Member::create(['user_id' => $u->id, 'member_no' => 'FW-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT), 'dob' => $dob, 'sex' => $sex, 'height_cm' => $h,
                'address' => 'Sample address', 'emergency_contact' => 'Sample Contact 0770000099', 'joined_on' => now()->subDays($ago + 90), 'frozen' => $frozen] + $this->d);
            $plan = $plans[$pi];
            // earlier paid membership + current one
            $start = now()->subDays($ago)->startOfDay();
            $prev = Membership::create(['member_id' => $m->id, 'plan_id' => $plans[0]->id, 'starts_on' => $start->copy()->subDays(30), 'ends_on' => $start->copy()->subDay(), 'price' => 5000] + $this->d);
            Payment::create(['receipt_no' => Payment::nextReceiptNo(), 'member_id' => $m->id, 'membership_id' => $prev->id, 'amount' => 5000, 'method' => $methods[$i % 4], 'paid_on' => $start->copy()->subDays(30)] + $this->d);
            $cur = Membership::create(['member_id' => $m->id, 'plan_id' => $plan->id, 'starts_on' => $start, 'ends_on' => $start->copy()->addDays($plan->duration_days), 'price' => $plan->price] + $this->d);
            Payment::create(['receipt_no' => Payment::nextReceiptNo(), 'member_id' => $m->id, 'membership_id' => $cur->id, 'amount' => round($plan->price * $frac), 'method' => $methods[($i + 1) % 4], 'paid_on' => $start] + $this->d);
        }
        // Spread extra past payments over 6 months for the finance charts
        $m1 = Member::first();
        for ($k = 2; $k <= 6; $k++) {
            foreach ([1500, 5000, 5000, 13500] as $j => $amt) {
                Payment::create(['receipt_no' => Payment::nextReceiptNo(), 'member_id' => Member::find(($j + $k) % 12 + 1)->id, 'amount' => $amt, 'method' => $methods[$j], 'paid_on' => now()->subMonths($k)->subDays($j * 3), 'note' => 'Sample history'] + $this->d);
            }
        }
        // a few payments inside the current month so the dashboard shows live-looking numbers
        foreach ([[0, 5000, 'cash'], [1, 13500, 'bank'], [2, 5000, 'card'], [3, 2500, 'cash']] as $j => [$daysAgo, $amt, $mt]) {
            $when = now()->subDays(min($daysAgo, max(0, now()->day - 1)));
            Payment::create(['receipt_no' => Payment::nextReceiptNo(), 'member_id' => Member::find($j + 6)->id, 'amount' => $amt, 'method' => $mt, 'paid_on' => $when, 'note' => 'Sample payment'] + $this->d);
        }
        foreach ([['Rent', 60000], ['Electricity', 18000], ['Salaries', 25000], ['Equipment maintenance', 6000], ['Marketing', 4000]] as [$cat, $amt]) {
            for ($k = 0; $k <= 5; $k++) {
                Expense::create(['category' => $cat, 'amount' => $amt + ($k * 250), 'spent_on' => now()->subMonths($k)->startOfMonth()->addDays(4), 'description' => 'Sample expense'] + $this->d);
            }
        }
    }

    private function extras(): void
    {
        Announcement::create(['title' => 'Welcome to the new FitnessWalkers app', 'body' => 'Sample announcement. Track your workouts, request a plan and see your progress here.', 'published_at' => now()->subDays(2)] + $this->d);
        Announcement::create(['title' => 'Holiday hours', 'body' => 'Sample: the gym closes at 2 PM on public holidays.', 'published_at' => now()->subDays(8)] + $this->d);

        $members = Member::with('user')->get();
        $exercises = Exercise::all()->keyBy('name');

        // Member 1: approved plan + logs + body history
        $m = $members[0];
        $tpl = WorkoutTemplate::with('items')->first();
        $plan = WorkoutPlan::create(['member_id' => $m->id, 'name' => $tpl->name, 'status' => 'approved', 'approved_at' => now()->subDays(20)] + $this->d);
        foreach ($tpl->items as $it) {
            WorkoutPlanItem::create(['workout_plan_id' => $plan->id, 'day_no' => $it->day_no, 'exercise_id' => $it->exercise_id, 'sets' => $it->sets, 'reps' => $it->reps]);
        }
        foreach ([18, 14, 11, 7, 4, 1] as $n => $daysAgo) {
            foreach (['Leg Press', 'Machine Chest Press', 'Lat Pulldown'] as $en) {
                for ($s = 1; $s <= 3; $s++) {
                    WorkoutLog::create(['member_id' => $m->id, 'exercise_id' => $exercises[$en]->id, 'logged_on' => now()->subDays($daysAgo), 'set_no' => $s, 'reps' => 12 - ($s - 1), 'weight_kg' => 40 + $n * 2.5] + $this->d);
                }
            }
        }
        foreach ([0, 2] as $mi) {
            foreach ([40, 30, 20, 10, 0] as $n => $daysAgo) {
                $w = 78 - $n * 0.8;
                BodyLog::create(['member_id' => $members[$mi]->id, 'logged_on' => now()->subDays($daysAgo), 'weight_kg' => $w, 'height_cm' => $members[$mi]->height_cm,
                    'bmi' => Bmi::calc($w, (float) $members[$mi]->height_cm), 'waist_cm' => 88 - $n * 0.5] + $this->d);
            }
        }
        Goal::create(['member_id' => $m->id, 'title' => 'Reach 74 kg', 'target_value' => 74, 'unit' => 'kg', 'target_date' => now()->addDays(60)] + $this->d);

        // Member 2: pending plan request. Member 3: app diet plan.
        PlanRequest::create(['member_id' => $members[1]->id, 'goal' => 'fat_loss', 'level' => 'beginner', 'days_per_week' => 4, 'notes' => 'Sample request'] + $this->d);
        $in = ['goal' => 'lose', 'age' => 24, 'sex' => 'male', 'height' => 180, 'weight' => 82, 'activity' => 'moderate', 'diet_type' => 'non_veg', 'allergies' => [], 'meals' => 4, 'budget' => 1200];
        $gen = DietGenerator::generate($in);
        DietPlan::create(['member_id' => $members[2]->id, 'source' => 'app', 'title' => 'App-generated plan', 'inputs' => $in, 'plan' => $gen] + $this->d);
        DietPlan::create(['member_id' => $members[4]->id, 'source' => 'owner', 'title' => 'Owner plan (sample)', 'owner_text' => "Breakfast: 3 eggs + 2 slices bread\nLunch: rice, dhal, vegetables, fish\nDinner: roti, chicken, salad\nSample text written by the owner."] + $this->d);
        RenewalRequest::create(['member_id' => $members[3]->id, 'plan_id' => Plan::first()->id] + $this->d);

        $this->visitsAndEnquiries($members);
    }

    /** Visits (some members recent, some quiet), health forms, and a few enquiries. */
    private function visitsAndEnquiries($members): void
    {
        // member index => days ago they visited. Index 2 (Dilshan) and 11 (Lakmal) are left out so they show as "not training lately".
        $visits = [0 => [0, 1, 3, 5, 8], 1 => [0, 2, 4, 6], 4 => [1, 3, 7], 5 => [2, 5], 6 => [20], 7 => [1, 4], 8 => [3, 9, 12], 10 => [25]];
        foreach ($visits as $idx => $days) {
            foreach ($days as $dAgo) {
                Attendance::create(['member_id' => $members[$idx]->id, 'visited_on' => today()->subDays($dAgo), 'source' => $dAgo % 2 ? 'owner' : 'member'] + $this->d);
            }
        }
        $questions = array_keys(config('fitness.health_questions'));
        foreach ($members as $i => $m) {
            $answers = array_fill_keys($questions, false);
            if ($i === 8) $answers['bp'] = true;       // one member with a flag so the warning shows
            if ($i === 6) $answers['joints'] = true;
            $m->update([
                'health_answers' => $answers, 'health_flag' => in_array(true, $answers, true),
                'health_notes' => $i === 8 ? 'Sample: takes blood pressure tablets. Check with doctor before heavy lifting.' : null,
                'waiver_accepted_at' => $i === 11 ? null : $m->joined_on,   // one member has no signed terms
            ]);
        }
        Enquiry::create(['name' => 'Amaya Wijesinghe', 'phone' => '0770000101', 'interest' => 'Weight loss', 'message' => 'Sample: do you have evening classes?', 'source' => 'website', 'status' => 'new', 'created_at' => now()->subHours(3)] + $this->d);
        Enquiry::create(['name' => 'Nuwan Herath', 'phone' => '0770000102', 'interest' => 'Building muscle', 'source' => 'walk-in', 'status' => 'new', 'created_at' => now()->subDay()] + $this->d);
        Enquiry::create(['name' => 'Piumi Karunaratne', 'phone' => '0770000103', 'email' => 'piumi@example.test', 'interest' => 'General fitness', 'message' => 'Sample: price for 3 months?', 'source' => 'website', 'status' => 'contacted', 'notes' => 'Sent prices on WhatsApp', 'created_at' => now()->subDays(3)] + $this->d);
        Enquiry::create(['name' => 'Janaka Peiris', 'phone' => '0770000104', 'source' => 'phone', 'status' => 'lost', 'notes' => 'Sample: too far from home', 'created_at' => now()->subDays(12)] + $this->d);
    }
}
