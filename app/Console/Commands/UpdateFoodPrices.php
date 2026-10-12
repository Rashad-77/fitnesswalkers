<?php

namespace App\Console\Commands;

use App\Services\PriceUpdater;
use Illuminate\Console\Command;

class UpdateFoodPrices extends Command
{
    protected $signature = 'foods:update-prices';
    protected $description = 'Update food prices from the configured price feed';

    public function handle(): int
    {
        $r = PriceUpdater::fromFeed();
        if ($r['error']) { $this->warn($r['error']); return self::SUCCESS; }
        $this->info("Updated {$r['updated']} foods; {$r['skipped_locked']} locked by owner.");
        foreach ($r['rejected'] as $line) $this->warn('Rejected: '.$line);
        foreach ($r['unmatched'] as $name) $this->line('No matching food: '.$name);
        return self::SUCCESS;
    }
}
