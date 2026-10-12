<?php

namespace App\Console\Commands;

use App\Support\Dummy;
use Illuminate\Console\Command;

class DummyList extends Command
{
    protected $signature = 'dummy:list';
    protected $description = 'Show how many placeholder (dummy) rows remain in each table';

    public function handle(): int
    {
        $rows = [];
        foreach (Dummy::counts() as $t => $n) {
            $rows[] = [$t, $n];
        }
        $this->table(['Table', 'Dummy rows'], $rows);
        $this->info('Total dummy rows: '.Dummy::total());
        return self::SUCCESS;
    }
}
