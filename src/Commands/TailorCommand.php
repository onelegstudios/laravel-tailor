<?php

namespace Onelegstudios\Tailor\Commands;

use Illuminate\Console\Command;

class TailorCommand extends Command
{
    public $signature = 'laravel-tailor';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
