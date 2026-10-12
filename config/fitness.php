<?php

return [
    // JSON feed of {"name": "...", "price_per_kg": 123} used by `php artisan foods:update-prices`.
    'price_feed_url' => env('PRICE_FEED_URL'),
    'currency' => 'Rs.',
    // A member with no workout logged for this many days appears in the "quiet members" list.
    'quiet_days' => 14,
    // Asked when someone joins. Answering yes to any flags the member so the owner checks with them first.
    'health_questions' => [
        'heart' => 'Has a doctor ever said you have a heart condition?',
        'chest' => 'Do you feel pain in your chest when you exercise?',
        'dizzy' => 'Do you often feel dizzy or faint?',
        'joints' => 'Do you have a bone, back or joint problem that exercise could make worse?',
        'bp' => 'Are you taking medicine for blood pressure or a heart condition?',
        'pregnant' => 'Are you pregnant or have you given birth in the last 6 months?',
        'other' => 'Is there any other reason you should not exercise?',
    ],
];
