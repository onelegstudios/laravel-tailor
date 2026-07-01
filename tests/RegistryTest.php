<?php

use Onelegstudios\Tailor\Kits\LucideKit;
use Onelegstudios\Tailor\Kits\UiKit;
use Onelegstudios\Tailor\Registry;

beforeEach(function () {
    $this->registry = app(Registry::class);
});

it('resolves the package class when the app has no override', function () {
    $resolved = $this->registry->resolve([LucideKit::class], 'App\\Tailor\\Kits', UiKit::class);

    expect($resolved)->toHaveKey('lucide')
        ->and($resolved['lucide'])->toBeInstanceOf(LucideKit::class);
});

it('prefers an app override with the same short name', function () {
    $namespace = 'Onelegstudios\\Tailor\\Tests\\Fixtures\\Overrides\\Kits';

    $resolved = $this->registry->resolve([LucideKit::class], $namespace, UiKit::class);

    expect($resolved['lucide'])
        ->toBeInstanceOf($namespace.'\\LucideKit')
        ->not->toBeInstanceOf(LucideKit::class);
});

it('ignores an app class that does not implement the contract', function () {
    $namespace = 'Onelegstudios\\Tailor\\Tests\\Fixtures\\Invalid\\Kits';

    $resolved = $this->registry->resolve([LucideKit::class], $namespace, UiKit::class);

    expect($resolved['lucide'])->toBeInstanceOf(LucideKit::class);
});

it('ignores a config entry that exists in neither the app nor the package', function () {
    $resolved = $this->registry->resolve(
        ['Onelegstudios\\Tailor\\Kits\\GhostKit'],
        'App\\Tailor\\Kits',
        UiKit::class,
    );

    expect($resolved)->toBe([]);
});

it('drops missing entries while keeping the ones that resolve', function () {
    $resolved = $this->registry->resolve(
        [LucideKit::class, 'Onelegstudios\\Tailor\\Kits\\GhostKit'],
        'App\\Tailor\\Kits',
        UiKit::class,
    );

    expect(array_keys($resolved))->toBe(['lucide']);
});
