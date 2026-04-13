<?php

use Illuminate\Filesystem\Filesystem;
use Mockery\Expectation;
use Mockery\MockInterface;
use Onelegstudios\Tailor\Support\UseLucideIcons;
use PHPUnit\Framework\Assert;

use function Pest\Laravel\artisan;
use function Pest\Laravel\mock;

it('delegates to the lucide icon action and adds the package tailwind source directive', function (): void {
    $filesystem = new Filesystem;
    $appCssPath = resource_path('css/app.css');
    $originalContents = $filesystem->exists($appCssPath)
        ? $filesystem->get($appCssPath)
        : null;

    try {
        $filesystem->ensureDirectoryExists(dirname($appCssPath));
        $filesystem->replace($appCssPath, tailwindAppCssStub());

        mockUseLucideIconsAction([
            'filesUpdated' => [resource_path('views/example.blade.php')],
            'iconsPublished' => ['loader-circle'],
            'warnings' => ['resources/views/example.blade.php: skipped unresolved :icon on <flux:button>'],
        ]);

        artisan('tailor:use-lucide-icons')
            ->expectsOutputToContain('Published 1 Lucide icons and updated 1 view files.')
            ->expectsOutputToContain('Icons: loader-circle')
            ->expectsOutputToContain('Left 1 unresolved icon expressions unchanged.')
            ->expectsOutputToContain('resources/views/example.blade.php: skipped unresolved :icon on <flux:button>')
            ->expectsOutputToContain('Configured Lucide icons.')
            ->assertSuccessful();

        $contents = $filesystem->get($appCssPath);

        Assert::assertStringContainsString(tailorPackageTailwindSourceDirective(), $contents);
        Assert::assertSame(1, substr_count($contents, tailorPackageTailwindSourceDirective()));
    } finally {
        if ($originalContents === null) {
            $filesystem->delete($appCssPath);
        } else {
            $filesystem->replace($appCssPath, $originalContents);
        }
    }
});

it('does not duplicate the package tailwind source directive', function (): void {
    $filesystem = new Filesystem;
    $appCssPath = resource_path('css/app.css');
    $originalContents = $filesystem->exists($appCssPath)
        ? $filesystem->get($appCssPath)
        : null;

    try {
        $filesystem->ensureDirectoryExists(dirname($appCssPath));
        $filesystem->replace(
            $appCssPath,
            tailwindAppCssStub().tailorPackageTailwindSourceDirective().PHP_EOL,
        );

        mockUseLucideIconsAction([
            'filesUpdated' => [],
            'iconsPublished' => [],
            'warnings' => [],
        ]);

        artisan('tailor:use-lucide-icons')
            ->expectsOutputToContain('Published 0 Lucide icons and updated 0 view files.')
            ->expectsOutputToContain('Configured Lucide icons.')
            ->assertSuccessful();

        Assert::assertSame(1, substr_count(
            $filesystem->get($appCssPath),
            tailorPackageTailwindSourceDirective(),
        ));
    } finally {
        if ($originalContents === null) {
            $filesystem->delete($appCssPath);
        } else {
            $filesystem->replace($appCssPath, $originalContents);
        }
    }
});

function mockUseLucideIconsAction(array $summary): void
{
    mock(UseLucideIcons::class, function (MockInterface $mock) use ($summary): void {
        /** @var Expectation $handleExpectation */
        $handleExpectation = $mock->shouldReceive('handle');

        $handleExpectation
            ->once()
            ->withArgs(function (string $viewsRoot, string $iconRoot, array $mappings, callable $publisher): bool {
                expect($viewsRoot)->toBe(resource_path('views'))
                    ->and($iconRoot)->toBe(resource_path('views/flux/icon'))
                    ->and($mappings)->toBe(config('tailor.icons.mappings'));

                return true;
            })
            ->andReturn($summary);
    });
}

function tailorPackageTailwindSourceDirective(): string
{
    return "@source '../../vendor/onelegstudios/laravel-tailor/resources/views/**/*.blade.php';";
}

function tailwindAppCssStub(): string
{
    return <<<'CSS'
@import 'tailwindcss';
@import '../../vendor/livewire/flux/dist/flux.css';

@source '../views';
@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../vendor/livewire/flux-pro/stubs/**/*.blade.php';
@source '../../vendor/livewire/flux/stubs/**/*.blade.php';

CSS;
}
