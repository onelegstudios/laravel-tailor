<?php

namespace Onelegstudios\Tailor\Commands;

use Illuminate\Console\Command;

class InstallBillingCommand extends Command
{
    public $signature = 'tailor:install-billing';

    public $description = 'Run the dummy billing tailoring steps';

    public function handle(): int
    {
        $this->info('Configured the dummy billing feature.');

        return self::SUCCESS;
    }
}
