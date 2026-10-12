<?php

namespace Tests\Unit;

use App\Services\DishPlanner;
use PHPUnit\Framework\TestCase;

/** The day-fit rules are plain arithmetic, so they are checked without the database. */
class DishFitTest extends TestCase
{
    private array $target = ['kcal' => 2000, 'protein' => 120, 'carbs' => 250, 'fat' => 65];

    public function test_exact_match_is_ok(): void
    {
        $this->assertSame('ok', DishPlanner::fit($this->target, $this->target)['all']);
    }

    public function test_each_nutrient_reports_high_or_low(): void
    {
        $fit = DishPlanner::fit(['kcal' => 2600, 'protein' => 70, 'carbs' => 250, 'fat' => 65], $this->target);
        $this->assertSame('high', $fit['kcal']['state']);
        $this->assertSame('low', $fit['protein']['state']);
        $this->assertSame('ok', $fit['carbs']['state']);
        $this->assertSame('off', $fit['all']);
    }

    public function test_error_is_zero_on_target_and_grows_with_distance(): void
    {
        $near = DishPlanner::error(['kcal' => 2050, 'protein' => 118, 'carbs' => 250, 'fat' => 65], $this->target);
        $far = DishPlanner::error(['kcal' => 2800, 'protein' => 60, 'carbs' => 250, 'fat' => 65], $this->target);
        $this->assertSame(0.0, (float) DishPlanner::error($this->target, $this->target));
        $this->assertLessThan($far, $near);
    }
}
