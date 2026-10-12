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
