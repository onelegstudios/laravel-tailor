<?php

use Composer\InstalledVersions;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Actions\ReplaceIcons;
use Onelegstudios\Tailor\Kits\LucideKit;
use Onelegstudios\Tailor\Services\PublishFluxIcons;
use Onelegstudios\Tailor\Tests\Stubs\FluxProLucideKit;
use Onelegstudios\Tailor\Tests\Stubs\FreeOnlyLucideKit;
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

it('detects Flux Pro from the installed composer packages', function () {
    // The stubs above pin detection to cover both branches; this exercises the
    // real rule, which is what decides the Pro group in a user's app.
    $detect = (fn (): bool => $this->fluxProIsInstalled())
        ->call(app(LucideKit::class));

    expect($detect)->toBe(InstalledVersions::isInstalled('livewire/flux-pro'));
});

it('downloads the configured starter-kit and Flux icons in one pass', function () {
    config()->set('tailor.settings.kits.lucide.icons', [
        'starter-kit' => [
            'heroicons' => ['home' => 'house', 'trash' => 'trash-2'],
            'lucide' => ['layout-grid' => 'layout-dashboard'],
        ],
        'flux' => [
            'free' => ['exclamation-triangle' => 'triangle-alert'],
            'pro' => ['eye-dropper' => 'pipette'],
            'animated' => [],
        ],
    ]);

    $failed = app(FluxProLucideKit::class)->apply();

    expect($failed)->toBe([])
        ->and(RecordingFluxIconCommand::$received)
        ->toBe(['house', 'trash-2', 'layout-dashboard', 'triangle-alert', 'pipette']);
});

it('skips the Flux Pro icons when Flux Pro is not installed', function () {
    config()->set('tailor.settings.kits.lucide.icons', [
        'starter-kit' => ['heroicons' => ['home' => 'house'], 'lucide' => []],
        'flux' => [
            'free' => ['exclamation-triangle' => 'triangle-alert'],
            'pro' => ['eye-dropper' => 'pipette'],
            'animated' => ['loading' => 'loader-circle'],
        ],
    ]);

    $failed = app(FreeOnlyLucideKit::class)->apply();

    // pipette is left out: only a Pro component renders it. The animated spinner
    // is referenced by both packages, so it survives.
    expect($failed)->toBe([])
        ->and(RecordingFluxIconCommand::$received)
        ->toBe(['house', 'triangle-alert', 'loader-circle'])
        ->not->toContain('pipette');
});

it('does not alias a Flux Pro icon when Flux Pro is not installed', function () {
    config()->set('tailor.settings.kits.lucide.icons', [
        'starter-kit' => ['heroicons' => [], 'lucide' => []],
        'flux' => [
            'free' => ['exclamation-triangle' => 'triangle-alert'],
            'pro' => ['eye-dropper' => 'pipette'],
            'animated' => [],
        ],
    ]);

    app(FreeOnlyLucideKit::class)->apply();

    $iconPath = resource_path('views/flux/icon');

    // The free icon is aliased to the name Flux references internally; the Pro
    // one was never downloaded, so no blade exists under either name.
    expect(file_exists("{$iconPath}/exclamation-triangle.blade.php"))->toBeTrue()
        ->and(file_exists("{$iconPath}/eye-dropper.blade.php"))->toBeFalse()
        ->and(file_exists("{$iconPath}/pipette.blade.php"))->toBeFalse();
});

it('returns the icons that failed to download', function () {
    config()->set('tailor.settings.kits.lucide.icons', [
        'starter-kit' => ['heroicons' => ['home' => 'house'], 'lucide' => []],
        'flux' => ['free' => [], 'pro' => [], 'animated' => []],
    ]);

    RecordingFluxIconCommand::$fail = ['house'];

    $failed = app(LucideKit::class)->apply();

    expect($failed)->toBe(['house']);
});

it('does not rewrite the views when an icon fails to download', function () {
    config()->set('tailor.settings.kits.lucide.icons', [
        'starter-kit' => ['heroicons' => ['home' => 'house'], 'lucide' => []],
        'flux' => ['free' => [], 'pro' => [], 'animated' => []],
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
        'flux' => ['free' => [], 'pro' => [], 'animated' => []],
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
        'flux' => ['free' => [], 'pro' => [], 'animated' => []],
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
        'flux' => ['free' => [], 'pro' => [], 'animated' => []],
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
        'flux' => ['free' => [], 'pro' => [], 'animated' => []],
    ]);

    RecordingFluxIconCommand::$fail = ['house'];

    $buffer = new BufferedOutput;

    app(LucideKit::class)->apply(new OutputStyle(new ArrayInput([]), $buffer));

    expect($buffer->fetch())->not->toContain('Updating icon references in your views...');
});

it('rewrites the views once every icon has downloaded', function () {
    config()->set('tailor.settings.kits.lucide.icons', [
        'starter-kit' => ['heroicons' => ['home' => 'house'], 'lucide' => []],
        'flux' => ['free' => [], 'pro' => [], 'animated' => []],
    ]);

    $replaceIcons = Mockery::mock(ReplaceIcons::class);
    $replaceIcons->shouldReceive('execute')
        ->once()
        ->with(resource_path('views'), ['home' => 'house']);
    $this->app->instance(ReplaceIcons::class, $replaceIcons);

    app(LucideKit::class)->apply();
});
