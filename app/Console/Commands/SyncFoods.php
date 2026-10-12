<?php

namespace App\Console\Commands;

use App\Support\FoodCatalogue;
use Illuminate\Console\Command;

class SyncFoods extends Command
{
    protected $signature = 'foods:sync';
    protected $description = 'Add any food from the built-in Sri Lankan catalogue that is missing. Foods you edited are never changed.';

    public function handle(): int
    {
        $n = FoodCatalogue::sync();
        $this->info($n ? "Added $n food(s) to the catalogue." : 'The catalogue is already complete.');
        return self::SUCCESS;
    }
}
