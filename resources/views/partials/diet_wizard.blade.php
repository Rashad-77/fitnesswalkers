{{-- The meal-plan survey: one question to a screen. The system asks, the system builds the plan around the member's workout plan.
     $action, $previewUrl, $form (array|null), $defaults, $allergens, $achievements, $causes, $usuals, $workout --}}
@php
  $f = fn ($k, $d = null) => old($k, $form[$k] ?? $d);
  $fl = fn ($k) => (array) old($k, $form[$k] ?? []);
  $usualSel = fn ($slot) => array_map('intval', (array) old('usual_'.$slot, $form['usuals'][$slot] ?? []));
  $achieveDesc = ['lose' => 'Lose body fat while keeping muscle', 'tone' => 'Slowly lose fat and build a firmer look', 'muscle' => 'Gain muscle slowly while you train', 'gain' => 'Gain healthy weight', 'maintain' => 'Eat what your body burns'];
  $bodies = ['lean' => ['Lean', 'Slim build, little body fat'], 'average' => ['Average', 'Neither slim nor heavy'], 'soft' => ['Soft', 'Some extra body fat'], 'heavy' => ['Heavy', 'A lot of extra weight']];
  $bodyGoals = ['lean' => ['Lean and slim', 'Light and slim'], 'toned' => ['Toned', 'Firm, with visible shape'], 'athletic' => ['Athletic', 'Strong and fit'], 'muscular' => ['Big and muscular', 'Maximum muscle'], 'healthy' => ['A healthy weight', 'Feel good and healthy']];
  $metab = ['slow' => ['Slow', 'I gain weight easily and lose it slowly'], 'average' => ['Average', 'My weight changes at a normal pace'], 'fast' => ['Fast', 'I stay slim even when I eat a lot']];
  $shape = ['never' => 'I have never felt in great shape', 'recent' => 'In the last year', 'few_years' => '2 to 5 years ago', 'long_ago' => 'More than 5 years ago', 'teen' => 'When I was a teenager'];
  $jobs = [
    'sitting' => ['Mostly sitting', 'Desk work, studying or driving.'],
    'standing' => ['Often on my feet', 'Teaching, a shop, nursing or housework.'],
    'heavy' => ['Physical work', 'Farming, construction or carrying loads.'],
  ];
  $patterns = [
    'everything' => ['I eat everything', 'Fish, chicken, meat, eggs and milk'],
    'semi' => ['Mostly plants', 'Mainly vegetarian, with a little fish, egg or meat'],
    'ovolacto' => ['Vegetarian: eggs and milk', 'No fish, chicken or meat'],
    'lacto' => ['Vegetarian: milk, no eggs', 'No fish, chicken, meat or eggs'],
    'ovo' => ['Vegetarian: eggs, no milk', 'No fish, chicken, meat or milk'],
    'vegan' => ['Vegan', 'Plant foods only. No eggs, milk, fish or meat.'],
  ];
  $allergyLabels = ['egg' => 'Egg', 'dairy' => 'Milk and curd', 'fish' => 'Fish', 'shellfish' => 'Prawns, crab, cuttlefish', 'nuts' => 'Peanuts and tree nuts', 'gluten' => 'Wheat (gluten)', 'soy' => 'Soya', 'sesame' => 'Sesame'];
  $mealOpts = [3 => ['3 meals', 'Breakfast, lunch, dinner'], 4 => ['4 meals', 'Breakfast, lunch, evening snack, dinner'], 5 => ['5 meals', 'Breakfast, mid-morning snack, lunch, evening snack, dinner']];
  $health = [
    'pregnant' => 'Are you pregnant or breastfeeding?',
    'medical' => 'Has a doctor told you that you have a condition that affects what you should eat? For example diabetes, high blood pressure, or heart, kidney or liver disease. Or do you have an injury that stops you training?',
    'eating_disorder' => 'Have you ever had an eating disorder, such as anorexia, bulimia or binge eating?',
  ];
@endphp
<form method="post" action="{{ $action }}" class="wiz" id="wiz" data-preview="{{ $previewUrl }}" novalidate>@csrf
  <div class="wprog" aria-hidden="true"><i id="wbar"></i></div>
  <p class="wcount" id="wcount" aria-live="polite"></p>

  <section class="wstep" data-step="you"><div class="wh"><div><h2>About you</h2><p>We use these to work out the energy your body burns at rest.</p></div></div>
    <div class="f"><label>Gender</label><div class="seg"><label><input type="radio" name="sex" value="male" @checked($f('sex', $defaults['sex'] ?? 'male') === 'male')><span>Male</span></label><label><input type="radio" name="sex" value="female" @checked($f('sex', $defaults['sex'] ?? 'male') === 'female')><span>Female</span></label></div></div>
    <div class="f"><label for="w-age">Age, in years</label><input id="w-age" type="number" name="age" value="{{ $f('age', $defaults['age'] ?? '') }}" required min="10" max="90" inputmode="numeric"><p class="small muted">Plans are only made for people aged 18 or over.</p></div>
    <div class="grid g2">
      <div class="f"><label for="w-h">Height, in cm</label><input id="w-h" type="number" step="0.1" name="height" value="{{ $f('height', $defaults['height'] ?? '') }}" required min="100" max="250" inputmode="decimal" placeholder="e.g. 170"></div>
      <div class="f"><label for="w-w">Weight today, in kg</label><input id="w-w" type="number" step="0.1" name="weight" value="{{ $f('weight', $defaults['weight'] ?? '') }}" required min="25" max="300" inputmode="decimal" placeholder="e.g. 70"></div>
    </div>
  </section>

  <section class="wstep" data-step="goal"><div class="wh"><div><h2>What do you want to achieve?</h2><p>This sets how many calories you eat compared with what you burn.</p></div></div>
    <div class="opts" role="radiogroup" aria-label="Wanted achievement">
      @foreach ($achievements as $k => $t)
        <label class="opt"><input type="radio" name="achievement" value="{{ $k }}" @checked($f('achievement', 'maintain') === $k)><span><b>{{ $t }}</b><small>{{ $achieveDesc[$k] ?? '' }}</small></span></label>
      @endforeach
    </div>
    <div id="pacebox" class="mt"><label>How fast do you want to <span id="pacegoal">lose weight</span>?</label>
      <div class="opts" role="radiogroup" aria-label="Pace">
        <label class="opt"><input type="radio" name="pace" value="gentle" @checked($f('pace', 'gentle') === 'gentle')><span><b>Gentle</b><small id="pace-g"></small></span></label>
        <label class="opt"><input type="radio" name="pace" value="steady" @checked($f('pace') === 'steady')><span><b>Steady</b><small id="pace-s"></small></span></label>
      </div>
      <p class="small muted" style="margin:.5rem 0 0">These are typical results, not promises. We never cut your calories by more than a quarter of what you burn.</p>
    </div>
    <div class="f mt"><label for="w-tw">Target weight, in kg <span class="muted">(optional)</span></label><input id="w-tw" type="number" step="0.1" name="target_weight" value="{{ $f('target_weight') }}" min="25" max="300" inputmode="decimal" placeholder="e.g. 65"></div>
  </section>

  <section class="wstep" data-step="body"><div class="wh"><div><h2>Your body</h2><p>Pick the one that is closest, for where you are now and where you want to be.</p></div></div>
    <label>Your body now</label>
    <div class="opts opts2" role="radiogroup" aria-label="Current body">
      @foreach ($bodies as $k => [$t, $d])<label class="opt"><input type="radio" name="current_body" value="{{ $k }}" @checked($f('current_body', 'average') === $k)><span><b>{{ $t }}</b><small>{{ $d }}</small></span></label>@endforeach
    </div>
    <label class="mt">Your body goal</label>
    <div class="opts opts2" role="radiogroup" aria-label="Body goal">
      @foreach ($bodyGoals as $k => [$t, $d])<label class="opt"><input type="radio" name="body_goal" value="{{ $k }}" @checked($f('body_goal', 'healthy') === $k)><span><b>{{ $t }}</b><small>{{ $d }}</small></span></label>@endforeach
    </div>
  </section>

  <section class="wstep" data-step="metabolism"><div class="wh"><div><h2>How does your body respond to food?</h2><p>This is your metabolism. Go with how it has felt for you over the years.</p></div></div>
    <div class="opts" role="radiogroup" aria-label="Metabolism">
      @foreach ($metab as $k => [$t, $d])<label class="opt"><input type="radio" name="metabolism" value="{{ $k }}" @checked($f('metabolism', 'average') === $k)><span><b>{{ $t }}</b><small>{{ $d }}</small></span></label>@endforeach
    </div>
    <label class="mt">When were you in your best shape?</label>
    <div class="opts" role="radiogroup" aria-label="Best shape">
      @foreach ($shape as $k => $t)<label class="opt"><input type="radio" name="best_shape" value="{{ $k }}" @checked($f('best_shape', 'few_years') === $k)><span><b>{{ $t }}</b></span></label>@endforeach
    </div>
  </section>

  <section class="wstep" data-step="causes"><div class="wh"><div><h2>What affects your weight?</h2><p>Tap anything that plays a part. Tap none if nothing does.</p></div></div>
    <div class="tgs">
      @foreach ($causes as $k => $t)<label class="tg"><input type="checkbox" name="causes[]" value="{{ $k }}" @checked(in_array($k, $fl('causes')))><span>{{ $t }}</span></label>@endforeach
    </div>
    <p class="small muted mt" id="causes-note" hidden>A medical condition or medication means your diet should be set with a doctor or dietitian, so we will not build a plan.</p>
    <label class="mt">What is your normal day like, not counting workouts?</label>
    <div class="opts" role="radiogroup" aria-label="Daily activity">
      @foreach ($jobs as $k => [$t, $d])<label class="opt"><input type="radio" name="job" value="{{ $k }}" @checked($f('job', 'sitting') === $k)><span><b>{{ $t }}</b><small>{{ $d }}</small></span></label>@endforeach
    </div>
    <div class="wnote mt"><b>Your workout plan</b><span>{{ $workout['name'] }}: {{ $workout['days'] }} workout {{ $workout['days'] === 1 ? 'day' : 'days' }} a week, about {{ $workout['minutes'] }} minutes each. Your meals are built around it.</span></div>
  </section>

  <section class="wstep" data-step="meals"><div class="wh"><div><h2>How many times a day do you eat?</h2><p>Your calories are spread over these meals.</p></div></div>
    <div class="opts" role="radiogroup" aria-label="Meals a day">
      @foreach ($mealOpts as $n => [$t, $d])<label class="opt"><input type="radio" name="meals" value="{{ $n }}" @checked((int) $f('meals', 4) === $n)><span><b>{{ $t }}</b><small>{{ $d }}</small></span></label>@endforeach
    </div>
  </section>

  <section class="wstep" data-step="limits"><div class="wh"><div><h2>Any dietary limits or allergies?</h2><p>The plan will only use foods that fit. Pick the closest one.</p></div></div>
    <div class="opts" role="radiogroup" aria-label="Eating pattern">
      @foreach ($patterns as $k => [$t, $d])<label class="opt"><input type="radio" name="pattern" value="{{ $k }}" @checked($f('pattern', 'everything') === $k)><span><b>{{ $t }}</b><small>{{ $d }}</small></span></label>@endforeach
    </div>
    <div id="meatbox" class="mt"><label>Do you avoid either of these?</label>
      <div class="tgs"><label class="tg"><input type="checkbox" name="no_beef" value="1" @checked($f('no_beef'))><span>I do not eat beef</span></label><label class="tg"><input type="checkbox" name="no_pork" value="1" @checked($f('no_pork'))><span>I do not eat pork</span></label></div></div>
    <label class="mt">Allergies <span class="muted">(tap any, foods with these never appear)</span></label>
    <div class="tgs">
      @foreach ($allergens as $a)<label class="tg"><input type="checkbox" name="allergies[]" value="{{ $a }}" @checked(in_array($a, $fl('allergies')))><span>{{ $allergyLabels[$a] ?? $a }}</span></label>@endforeach
    </div>
  </section>

  @foreach (['breakfast' => ['What do you usually have for breakfast?', 'Tap the foods you normally eat. We will keep them in your plan.'], 'lunch' => ['What do you usually have for lunch?', 'Tap what you normally eat at lunch.'], 'dinner' => ['What do you usually have for dinner?', 'Tap what you normally eat at dinner.']] as $slot => [$q, $help])
  <section class="wstep" data-step="usual-{{ $slot }}"><div class="wh"><div><h2>{{ $q }}</h2><p>{{ $help }} Skip it if nothing fits.</p></div></div>
    <div class="tgs">
      @foreach ($usuals[$slot] as $food)<label class="tg"><input type="checkbox" name="usual_{{ $slot }}[]" value="{{ $food->id }}" @checked(in_array($food->id, $usualSel($slot)))><span>{{ $food->name }}</span></label>@endforeach
    </div>
  </section>
  @endforeach

  <section class="wstep" data-step="health"><div class="wh"><div><h2>Three health questions</h2><p>Please answer truthfully. If any answer is Yes, we will not build a plan, because you need advice from a doctor or dietitian.</p></div></div>
    @foreach ($health as $k => $q)
      <div class="yn"><p>{{ $q }}</p>
        <div class="seg"><label><input type="radio" name="{{ $k }}" value="0" @checked(! $f($k))><span>No</span></label><label><input type="radio" name="{{ $k }}" value="1" @checked($f($k))><span>Yes</span></label></div></div>
    @endforeach
  </section>

  <section class="wstep" data-step="review"><div class="wh"><div><h2>Check and make your plan</h2><p>These are your numbers from what you told us.</p></div></div>
    <div class="wsc" id="rv-nums">
      <div class="k">Your daily target</div>
      <div class="kc"><b id="s-kcal">-</b><span>calories</span></div>
      <div class="macros"><div><b id="s-p">-</b><span>protein</span></div><div><b id="s-tdee">-</b><span>you burn</span></div><div><b id="s-water">-</b><span>water</span></div></div>
      <div class="k" style="margin-top:.9rem">Servings a day</div>
      <ul class="svlist" id="s-sv"></ul>
      <ul class="wwarn" id="s-warn"></ul>
    </div>
    <dl class="rv" id="rv-list"></dl>
    <label class="agree"><input type="checkbox" name="understand" value="1" @checked(old('understand'))> I understand this is a general guide made by a program, not medical advice.</label>
    @error('understand')<p class="err">{{ $message }}</p>@enderror
  </section>

  <div class="wnav">
    <button type="button" class="btn ghost" id="wback">Back</button>
    <button type="button" class="btn lime" id="wnext">Next</button>
    <button class="btn lime" id="wsubmit" hidden>Make my plan</button>
  </div>
  <p class="disc" id="wdisc" hidden>Plans follow the Food Based Dietary Guidelines for Sri Lanka (Ministry of Health), adjusted for your body, goal and workouts. Calories and protein are estimates.</p>
</form>
@pushOnce('scripts', 'planner-js')<script src="/js/planner.js"></script>@endPushOnce
