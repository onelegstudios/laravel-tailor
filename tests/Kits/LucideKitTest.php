<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Kits\LucideKit;
use Onelegstudios\Tailor\Tests\Stubs\RecordingFluxIconCommand;

beforeEach(function () {
    RecordingFluxIconCommand::reset();
    // Have the stub write into the directory the kit publishes to, so the
    // download-verification step sees the icons and reports no failures.
    RecordingFluxIconCommand::$targetDir = resource_path('views/flux/icon');
    $this->app->make(Kernel::class)->registerCommand(new RecordingFluxIconCommand);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory(resource_path('views/flux/icon'));
});

it('is registered as the lucide UI kit', function () {
    $kit = app(LucideKit::class);

    expect($kit->key())->toBe('lucide')
        ->and($kit->label())->toBe('Flux with Lucide Icons');
});

it('downloads the configured starter-kit and Flux icons in one pass', function () {
    config()->set('tailor.icons', [
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
    config()->set('tailor.icons', [
        'starter-kit' => ['heroicons' => ['home' => 'house'], 'lucide' => []],
        'flux' => ['normal' => [], 'animated' => []],
    ]);

    RecordingFluxIconCommand::$fail = ['house'];

    $failed = app(LucideKit::class)->apply();

    expect($failed)->toBe(['house']);
});
