<?php

namespace Onelegstudios\Tailor\Commands;

use Illuminate\Console\Command;
use Onelegstudios\Tailor\Support\UseLucideIcons;
use Throwable;

class UseLucideIconsCommand extends Command
{
    public $signature = 'tailor:use-lucide-icons';

    public $description = 'Publish mapped Lucide icons and rewrite supported Flux icon usages.';

    public function handle(UseLucideIcons $useLucideIcons): int
    {
        try {
            $summary = $useLucideIcons->handle(
                resource_path('views'),
                resource_path('views/flux/icon'),
                config('tailor.icons.mappings', config('tailor.mappings', [])),
                fn (array $icons): int => $icons === []
                    ? self::SUCCESS
                    : $this->call('flux:icon', ['icons' => $icons]),
            );
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Published %d Lucide icons and updated %d view files.',
            count($summary['iconsPublished']),
            count($summary['filesUpdated']),
        ));

        if ($summary['iconsPublished'] !== []) {
            $this->line('Icons: '.implode(', ', $summary['iconsPublished']));
        }

        if ($summary['warnings'] !== []) {
            $this->warn(sprintf('Left %d unresolved icon expressions unchanged.', count($summary['warnings'])));

            foreach ($summary['warnings'] as $warning) {
                $this->line('- '.$warning);
            }
        }

        $this->info('Configured Lucide icons.');

        return self::SUCCESS;
    }
}
