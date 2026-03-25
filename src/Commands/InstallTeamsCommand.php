<?php

namespace Onelegstudios\Tailor\Commands;

use Illuminate\Console\Command;

class InstallTeamsCommand extends Command
{
    public $signature = 'tailor:install-teams';

    public $description = 'Run the dummy team tailoring steps';

    public function handle(): int
    {
        $this->info('Configured the dummy teams feature.');

        return self::SUCCESS;
    }
}
