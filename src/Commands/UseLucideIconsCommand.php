<?php

namespace Onelegstudios\Tailor\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Support\UseLucideIcons;
use Throwable;

class UseLucideIconsCommand extends Command
{
    private const FLUX_TAILWIND_SOURCE_DIRECTIVE = "@source '../../vendor/livewire/flux/stubs/**/*.blade.php';";

    private const PACKAGE_TAILWIND_SOURCE_DIRECTIVE = "@source '../../vendor/onelegstudios/laravel-tailor/resources/views/**/*.blade.php';";

    public $signature = 'tailor:use-lucide-icons';

    public $description = 'Publish mapped Lucide icons and rewrite supported Flux icon usages.';

    public function handle(UseLucideIcons $useLucideIcons, Filesystem $filesystem): int
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

            $this->ensureTailwindSourceDirective($filesystem);
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

    private function ensureTailwindSourceDirective(Filesystem $filesystem): void
    {
        $appCssPath = resource_path('css/app.css');

        if (! $filesystem->exists($appCssPath)) {
            return;
        }

        $contents = $filesystem->get($appCssPath);

        if (str_contains($contents, 'vendor/onelegstudios/laravel-tailor/resources/views/**/*.blade.php')) {
            return;
        }

        $updatedContents = str_contains($contents, self::FLUX_TAILWIND_SOURCE_DIRECTIVE)
            ? str_replace(
                self::FLUX_TAILWIND_SOURCE_DIRECTIVE,
                self::FLUX_TAILWIND_SOURCE_DIRECTIVE.PHP_EOL.self::PACKAGE_TAILWIND_SOURCE_DIRECTIVE,
                $contents,
            )
            : rtrim($contents).PHP_EOL.PHP_EOL.self::PACKAGE_TAILWIND_SOURCE_DIRECTIVE.PHP_EOL;

        $filesystem->replace($appCssPath, $updatedContents);
    }
}
