<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\DemoAccountSeeder;
use Illuminate\Console\Command;

final class RefreshDemoAccountCommand extends Command
{
    protected $signature = 'app:refresh-demo-account';

    protected $description = 'Re-seed demo@relaticle.com used by Claude/ChatGPT directory reviewers.';

    public function handle(DemoAccountSeeder $seeder): int
    {
        $this->info('Refreshing demo account...');

        $seeder->setCommand($this);
        $seeder->run();

        $this->info('Demo account ready: '.DemoAccountSeeder::EMAIL);

        return self::SUCCESS;
    }
}
