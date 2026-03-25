<?php

namespace Onelegstudios\Tailor\Commands;

use Illuminate\Console\Command;

class InstallApiCommand extends Command
{
    public $signature = 'tailor:install-api';

    public $description = 'Run the dummy API tailoring steps';

    public function handle(): int
    {
        $this->info('Configured the dummy API feature.');

        return self::SUCCESS;
    }
}
