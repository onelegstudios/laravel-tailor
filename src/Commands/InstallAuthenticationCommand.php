<?php

namespace Onelegstudios\Tailor\Commands;

use Illuminate\Console\Command;

class InstallAuthenticationCommand extends Command
{
    public $signature = 'tailor:install-authentication';

    public $description = 'Run the dummy authentication tailoring steps';

    public function handle(): int
    {
        $this->info('Configured the dummy authentication feature.');

        return self::SUCCESS;
    }
}
