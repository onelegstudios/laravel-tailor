<?php

namespace Onelegstudios\Tailor\Commands;

use Illuminate\Console\Command;
use Onelegstudios\Tailor\Enums\InstallFeature;

use function Laravel\Prompts\multiselect;

class InstallCommand extends Command
{
    public $signature = 'tailor:install';

    public $description = 'Choose starter kit features to tailor';

    public function handle(): int
    {
        $selectedFeatures = multiselect(
            label: 'Which starter kit features would you like to tailor?',
            options: InstallFeature::options(),
            default: InstallFeature::values(),
        );

        if ($selectedFeatures === []) {
            $this->warn('No features selected.');

            return self::SUCCESS;
        }

        foreach ($selectedFeatures as $selectedFeature) {
            $this->call(InstallFeature::from($selectedFeature)->command());
        }

        $this->info('Tailor install complete.');

        return self::SUCCESS;
    }
}
