<?php

use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Actions\ReplaceIcons;
use Onelegstudios\Tailor\Kits\LucideKit;
use Onelegstudios\Tailor\Services\PublishFluxIcons;
use Onelegstudios\Tailor\Tests\Stubs\RecordingFluxIconCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function () {
    // Isolate resource_path() from other parallel workers before capturing it.
    $this->appBase = $this->isolateApplicationPaths();

    RecordingFluxIconCommand::reset();
    // Have the stub write into the directory the kit publishes to, so the
    // download-verification step sees the icons and reports no failures.
    RecordingFluxIconCommand::$targetDir = resource_path('views/flux/icon');
    $this->app->make(Kernel::class)->registerCommand(new RecordingFluxIconCommand);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->appBase);
});

it('is registered as the lucide UI kit', function () {
    $kit = app(LucideKit::class);

    expect($kit->key())->toBe('lucide')
        ->and($kit->label())->toBe('Flux with Lucide only');
});

it('downloads the configured starter-kit and Flux icons in one pass', function () {
    config()->set('tailor.settings.kits.lucide.icons', [
        'starter-kit' => [
            'heroicons' => ['home' => 'house', 'trash' => 'trash-2'],
            'lucide' => ['layout-grid' => 'layout-dashboard'],
        ],
        'flux' => [
            'normal' => ['eye-dropper' => 'pipette'],
            'animated' => [],
        ],
    ]);

    $failed = app(LucideKit::class)->apply();

    expect($failed)->toBe([])
        ->and(RecordingFluxIconCommand::$received)
        ->toBe(['house', 'trash-2', 'layout-dashboard', 'pipette']);
});

it('returns the icons that failed to download', function () {
    config()->set('tailor.settings.kits.lucide.icons', [
        'starter-kit' => ['heroicons' => ['home' => 'house'], 'lucide' => []],
        'flux' => ['normal' => [], 'animated' => []],
    ]);

    RecordingFluxIconCommand::$fail = ['house'];

    $failed = app(LucideKit::class)->apply();

    expect($failed)->toBe(['house']);
});

it('does not rewrite the views when an icon fails to download', function () {
    config()->set('tailor.settings.kits.lucide.icons', [
        'starter-kit' => ['heroicons' => ['home' => 'house'], 'lucide' => []],
        'flux' => ['normal' => [], 'animated' => []],
    ]);

    RecordingFluxIconCommand::$fail = ['house'];

    $replaceIcons = Mockery::mock(ReplaceIcons::class);
    $replaceIcons->shouldNotReceive('execute');
    $this->app->instance(ReplaceIcons::class, $replaceIcons);

    app(LucideKit::class)->apply();
});

it('ignores a malformed starter-kit config entry instead of erroring', function () {
    config()->set('tailor.settings.kits.lucide.icons', [
        'starter-kit' => [
            'heroicons' => ['home' => 'house'],
            'lucide' => ['layout-grid' => 'layout-dashboard'],
            'note' => 'not-an-array',
        ],
        'flux' => ['normal' => [], 'animated' => []],
    ]);

    $replaceIcons = Mockery::mock(ReplaceIcons::class);
    $replaceIcons->shouldReceive('execute')
        ->once()
        ->with(resource_path('views'), ['home' => 'house', 'layout-grid' => 'layout-dashboard']);
    $this->app->instance(ReplaceIcons::class, $replaceIcons);

    app(LucideKit::class)->apply();
});

it('does not alias flux icons when an icon fails to download', function () {
    config()->set('tailor.settings.kits.lucide.icons', [
        'starter-kit' => ['heroicons' => ['home' => 'house'], 'lucide' => []],
        'flux' => ['normal' => [], 'animated' => []],
    ]);

    RecordingFluxIconCommand::$fail = ['house'];

    $publishFluxIcons = Mockery::mock(PublishFluxIcons::class);
    $publishFluxIcons->shouldReceive('replacements')->andReturn([]);
    $publishFluxIcons->shouldNotReceive('applyAliases');
    $this->app->instance(PublishFluxIcons::class, $publishFluxIcons);

    app(LucideKit::class)->apply();
});

it('reports progress while rewriting the views after the download', function () {
    config()->set('tailor.settings.kits.lucide.icons', [
        'starter-kit' => ['heroicons' => ['home' => 'house'], 'lucide' => []],
        'flux' => ['normal' => [], 'animated' => []],
    ]);

    $buffer = new BufferedOutput;

    app(LucideKit::class)->apply(new OutputStyle(new ArrayInput([]), $buffer));

    expect($buffer->fetch())
        ->toContain('Updating icon references in your views...')
        ->toContain('Aliasing the icons Flux references internally...')
        ->toContain('Your starter kit now uses Lucide icons.');
});

it('stays silent about the rewrite when an icon fails to download', function () {
    config()->set('tailor.settings.kits.lucide.icons', [
        'starter-kit' => ['heroicons' => ['home' => 'house'], 'lucide' => []],
        'flux' => ['normal' => [], 'animated' => []],
    ]);

    RecordingFluxIconCommand::$fail = ['house'];

    $buffer = new BufferedOutput;

    app(LucideKit::class)->apply(new OutputStyle(new ArrayInput([]), $buffer));

    expect($buffer->fetch())->not->toContain('Updating icon references in your views...');
});

it('rewrites the views once every icon has downloaded', function () {
    config()->set('tailor.settings.kits.lucide.icons', [
        'starter-kit' => ['heroicons' => ['home' => 'house'], 'lucide' => []],
        'flux' => ['normal' => [], 'animated' => []],
    ]);

    $replaceIcons = Mockery::mock(ReplaceIcons::class);
    $replaceIcons->shouldReceive('execute')
        ->once()
        ->with(resource_path('views'), ['home' => 'house']);
    $this->app->instance(ReplaceIcons::class, $replaceIcons);

    app(LucideKit::class)->apply();
});
