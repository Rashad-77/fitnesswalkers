<?php

namespace App\Console\Commands;

use App\Services\{AutoReminders, WhatsApp};
use Illuminate\Console\Command;

class SendReminders extends Command
{
    protected $signature = 'reminders:send {--force : Run now, whatever the hour} {--list : Only list what is due, send nothing}';
    protected $description = 'Send the automatic WhatsApp reminders that are due today (see Settings > Automatic sending).';

    public function handle(): int
    {
        if (WhatsApp::mode() === 'off') { $this->line('Automatic sending is switched off.'); return self::SUCCESS; }

        if ($this->option('list')) {
            $plan = AutoReminders::plan();
            foreach ($plan['send'] as $r) $this->line('would send  '.$r['key'].'  '.$r['member']->user->name);
            foreach ($plan['waiting'] as $r) $this->line('holding     '.$r['key'].'  '.$r['member']->user->name.'  ('.$r['why'].')');
            $this->line($plan['no_consent'].' due message(s) skipped because the member has not agreed to WhatsApp.');
            return self::SUCCESS;
        }
        if (! $this->option('force') && now()->hour !== WhatsApp::hour()) return self::SUCCESS;   // the scheduler calls this every hour

        $r = AutoReminders::run();
        $this->info(sprintf('%s mode: %d sent, %d failed, %d waiting.', $r['mode'], $r['sent'], $r['failed'], $r['waiting']));
        return self::SUCCESS;
    }
}
