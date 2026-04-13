<?php

use Mockery\Expectation;
use Mockery\MockInterface;
use Onelegstudios\Tailor\Support\UseLucideIcons;

use function Pest\Laravel\artisan;
use function Pest\Laravel\mock;

it('delegates to the lucide icon action', function (): void {
    mock(UseLucideIcons::class, function (MockInterface $mock): void {
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
            ->andReturn([
                'filesUpdated' => [resource_path('views/example.blade.php')],
                'iconsPublished' => ['loader-circle'],
                'warnings' => ['resources/views/example.blade.php: skipped unresolved :icon on <flux:button>'],
            ]);
    });

    artisan('tailor:use-lucide-icons')
        ->expectsOutputToContain('Published 1 Lucide icons and updated 1 view files.')
        ->expectsOutputToContain('Icons: loader-circle')
        ->expectsOutputToContain('Left 1 unresolved icon expressions unchanged.')
        ->expectsOutputToContain('resources/views/example.blade.php: skipped unresolved :icon on <flux:button>')
        ->expectsOutputToContain('Configured Lucide icons.')
        ->assertSuccessful();
});
