<?php

namespace Onelegstudios\Tailor\Commands;

use Illuminate\Console\Command;

class UseLucideIconsCommand extends Command
{
    public $signature = 'tailor:use-lucide-icons';

    public $description = 'Configure Lucide icons';

    public function handle(): int
    {
        $this->info('Configured Lucide icons.');

        return self::SUCCESS;
    }
}
