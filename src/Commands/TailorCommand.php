<?php

namespace Onelegstudios\Tailor\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\outro;

class TailorCommand extends Command
{
    public $signature = 'tailor';

    public $description = 'Tailor the livewire starter kit to your needs';

    public function handle(): int
    {
        intro('Welcome to Tailor — let\'s customize your starter kit.');

        $options = multiselect(
            label: 'What would you like to tailor?',
            options: [
                'lucide_icons' => 'Use Lucide icons',
                'move_auth' => 'Move the auth folder',
            ],
            hint: 'Use space to select, enter to confirm.',
        );

        outro('All done! Your starter kit has been tailored.');

        return self::SUCCESS;
    }
}
