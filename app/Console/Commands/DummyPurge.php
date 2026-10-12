<?php

namespace App\Console\Commands;

use App\Support\Dummy;
use Illuminate\Console\Command;

class DummyPurge extends Command
{
    protected $signature = 'dummy:purge {--force : Do not ask for confirmation}';
    protected $description = 'Delete every placeholder (dummy) row. Real data is never touched.';

    public function handle(): int
    {
        $this->warn('This deletes ALL rows marked is_dummy (members, payments, foods, exercises...).');
        $this->warn('Settings stay (edit them in Admin > Settings). Real rows are kept.');
        if (! $this->option('force') && ! $this->confirm('Continue?')) {
            return self::FAILURE;
        }
        foreach (Dummy::purge() as $t => $n) {
            $this->line(str_pad($t, 20).$n.' removed');
        }
        return self::SUCCESS;
    }
}
