<?php

use Onelegstudios\Tailor\Actions\RemoveIcons;
use Onelegstudios\Tailor\Actions\ReplaceIcons;
use Onelegstudios\Tailor\Kits\HeroKit;

it('is registered as the hero UI kit', function () {
    $kit = app(HeroKit::class);

    expect($kit->key())->toBe('hero')
        ->and($kit->label())->toBe('Flux with Heroicons');
});

it('swaps the configured Lucide icons back to Heroicons and drops their blades', function () {
    config()->set('tailor.settings.kits.hero.icons', [
        'book-open-text' => 'book-open',
        'layout-grid' => 'squares-2x2',
    ]);

    $replaceIcons = Mockery::mock(ReplaceIcons::class);
    $replaceIcons->shouldReceive('execute')
        ->once()
        ->with(resource_path('views'), ['book-open-text' => 'book-open', 'layout-grid' => 'squares-2x2']);
    $this->app->instance(ReplaceIcons::class, $replaceIcons);

    $removeIcons = Mockery::mock(RemoveIcons::class);
    $removeIcons->shouldReceive('execute')
        ->once()
        ->with(resource_path('views/flux/icon'), ['book-open-text', 'layout-grid']);
    $this->app->instance(RemoveIcons::class, $removeIcons);

    expect(app(HeroKit::class)->apply())->toBe([]);
});

it('skips blank and self-mapping entries', function () {
    config()->set('tailor.settings.kits.hero.icons', [
        'book-open-text' => 'book-open',
        'chevrons-up-down' => '',          // no target — leave it in place
        'folder-git-2' => 'folder-git-2',  // maps to itself — nothing to swap
    ]);

    $replaceIcons = Mockery::mock(ReplaceIcons::class);
    $replaceIcons->shouldReceive('execute')
        ->once()
        ->with(resource_path('views'), ['book-open-text' => 'book-open']);
    $this->app->instance(ReplaceIcons::class, $replaceIcons);

    $removeIcons = Mockery::mock(RemoveIcons::class);
    $removeIcons->shouldReceive('execute')
        ->once()
        ->with(resource_path('views/flux/icon'), ['book-open-text']);
    $this->app->instance(RemoveIcons::class, $removeIcons);

    app(HeroKit::class)->apply();
});

it('ignores a malformed config entry instead of erroring', function () {
    config()->set('tailor.settings.kits.hero.icons', [
        'book-open-text' => 'book-open',
        'layout-grid' => ['not-a-string'],
    ]);

    $replaceIcons = Mockery::mock(ReplaceIcons::class);
    $replaceIcons->shouldReceive('execute')
        ->once()
        ->with(resource_path('views'), ['book-open-text' => 'book-open']);
    $this->app->instance(ReplaceIcons::class, $replaceIcons);

    $removeIcons = Mockery::mock(RemoveIcons::class);
    $removeIcons->shouldReceive('execute')->once();
    $this->app->instance(RemoveIcons::class, $removeIcons);

    app(HeroKit::class)->apply();
});

it('does nothing when no Lucide icons are configured', function () {
    config()->set('tailor.settings.kits.hero.icons', []);

    $replaceIcons = Mockery::mock(ReplaceIcons::class);
    $replaceIcons->shouldNotReceive('execute');
    $this->app->instance(ReplaceIcons::class, $replaceIcons);

    $removeIcons = Mockery::mock(RemoveIcons::class);
    $removeIcons->shouldNotReceive('execute');
    $this->app->instance(RemoveIcons::class, $removeIcons);

    expect(app(HeroKit::class)->apply())->toBe([]);
});
