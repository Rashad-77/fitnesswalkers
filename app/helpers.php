<?php

if (! function_exists('rs')) {
    /** Format a rupee amount: Rs. 12,500 (or Rs. 12,500.50 when it has cents). */
    function rs($n, bool $cents = false): string
    {
        $n = (float) $n;
        return 'Rs. '.number_format($n, ($cents || fmod($n, 1) !== 0.0) ? 2 : 0);
    }
}

if (! function_exists('dummy_tag')) {
    function dummy_tag($model): string
    {
        return ($model->is_dummy ?? false) ? '<span class="b dummy" title="Sample data. Edit it to make it real.">sample</span>' : '';
    }
}

if (! function_exists('gym_slots')) {
    /** The photo spots on the public website: file name (without extension) => [what it is, suggested shape]. */
    function gym_slots(): array
    {
        return [
            'hero-1' => ['Home page, big photo', 'portrait, about 900 x 1200'],
            'hero-2' => ['Home page, second photo', 'landscape, about 1000 x 700'],
            'hero-3' => ['Home page, third photo', 'square, about 700 x 700'],
            'floor' => ['The training floor', 'landscape, about 1400 x 900'],
            'free-weights' => ['Free weights', 'landscape, about 1000 x 700'],
            'machines' => ['Strength machines', 'landscape, about 1000 x 700'],
            'cardio' => ['Cardio area', 'landscape, about 1000 x 700'],
            'community' => ['Members training together', 'landscape, about 1000 x 700'],
            'trainer' => ['The owner and head trainer', 'portrait, about 900 x 1000'],
            'program-weight' => ['Weight management', 'landscape, about 900 x 700'],
            'program-muscle' => ['Muscle building', 'landscape, about 900 x 700'],
            'program-fitness' => ['General fitness', 'landscape, about 900 x 700'],
            'visit' => ['The entrance or outside of the gym', 'landscape, about 1200 x 800'],
        ];
    }
}

if (! function_exists('gym_img')) {
    /** Web path of a website photo you added to public/img/gym (jpg, jpeg, png or webp), or null if that spot is still empty. */
    function gym_img(string $slot): ?string
    {
        foreach (['jpg', 'jpeg', 'png', 'webp', 'avif'] as $ext) {
            if (is_file(public_path("img/gym/$slot.$ext"))) return "/img/gym/$slot.$ext?v=".filemtime(public_path("img/gym/$slot.$ext"));
        }
        return null;
    }
}

if (! function_exists('initials')) {
    /** "Kasun Perera" => "KP" */
    function initials(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        return mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1).(count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
    }
}
