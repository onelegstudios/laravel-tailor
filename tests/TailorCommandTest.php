<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Tests\Stubs\RecordingFluxIconCommand;

beforeEach(function () {
    RecordingFluxIconCommand::reset();
    // Have the stub write into the same directory the command publishes to, so
    // the download-verification step sees the icons and reports no failures.
    RecordingFluxIconCommand::$targetDir = resource_path('views/flux/icon');
    $this->app->make(Kernel::class)->registerCommand(new RecordingFluxIconCommand);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory(resource_path('views/flux/icon'));
});

it('asks about the UI kit first, then the remaining options', function () {
    $this->artisan('tailor')
        ->expectsChoice('What UI kit do you want to use?', 'lucide', [
            'hero' => 'Flux with Heroicons',
            'lucide' => 'Flux with Lucide Icons',
            'tall-stack' => 'Tall Stack UI',
        ])
        ->expectsChoice('What else would you like to tailor?', ['move_auth'], [
            'move_auth' => 'Move the auth folder',
        ])
        ->assertSuccessful();
});

it('defaults the UI kit to Flux with Heroicons', function () {
    $this->artisan('tailor')
        ->expectsChoice('What UI kit do you want to use?', 'hero', [
            'hero' => 'Flux with Heroicons',
            'lucide' => 'Flux with Lucide Icons',
            'tall-stack' => 'Tall Stack UI',
        ])
        ->expectsChoice('What else would you like to tailor?', [], [
            'move_auth' => 'Move the auth folder',
        ])
        ->assertSuccessful();
});

it('downloads the starter-kit Lucide icons when the Lucide kit is selected', function () {
    config()->set('tailor.icons', [
        'starter-kit' => [
            'heroicons' => ['home' => 'house', 'trash' => 'trash-2'],
            'lucide' => ['layout-grid' => 'layout-dashboard', 'folder-git-2' => 'folder-git-2'],
        ],
        'flux' => ['normal' => [], 'animated' => []],
    ]);

    $this->artisan('tailor')
        ->expectsChoice('What UI kit do you want to use?', 'lucide', [
            'hero' => 'Flux with Heroicons',
            'lucide' => 'Flux with Lucide Icons',
            'tall-stack' => 'Tall Stack UI',
        ])
        ->expectsChoice('What else would you like to tailor?', [], [
            'move_auth' => 'Move the auth folder',
        ])
        ->assertSuccessful();

    expect(RecordingFluxIconCommand::$calls)->toBe(4)
        ->and(RecordingFluxIconCommand::$received)
        ->toBe(['house', 'trash-2', 'layout-dashboard', 'folder-git-2']);
});

it('downloads the Flux internal icons when the Lucide kit is selected', function () {
    config()->set('tailor.icons', [
        'starter-kit' => ['heroicons' => [], 'lucide' => []],
        'flux' => [
            'normal' => ['eye-dropper' => 'pipette'],
            'animated' => ['loading' => 'loader-circle'],
        ],
    ]);

    $this->artisan('tailor')
        ->expectsChoice('What UI kit do you want to use?', 'lucide', [
            'hero' => 'Flux with Heroicons',
            'lucide' => 'Flux with Lucide Icons',
            'tall-stack' => 'Tall Stack UI',
        ])
        ->expectsChoice('What else would you like to tailor?', [], [
            'move_auth' => 'Move the auth folder',
        ])
        ->assertSuccessful();

    expect(RecordingFluxIconCommand::$calls)->toBe(2)
        ->and(RecordingFluxIconCommand::$received)->toBe(['pipette', 'loader-circle']);
});

it('uses the --ui-kit option instead of prompting for the UI kit', function () {
    $this->artisan('tailor', ['--ui-kit' => 'hero'])
        ->expectsChoice('What else would you like to tailor?', [], [
            'move_auth' => 'Move the auth folder',
        ])
        ->assertSuccessful();

    expect(RecordingFluxIconCommand::$calls)->toBe(0);
});

it('fails when given an unknown --ui-kit', function () {
    $this->artisan('tailor', ['--ui-kit' => 'bogus'])
        ->assertFailed();

    expect(RecordingFluxIconCommand::$calls)->toBe(0);
});

it('fails when an icon cannot be downloaded', function () {
    config()->set('tailor.icons', [
        'starter-kit' => [
            'heroicons' => ['home' => 'house', 'trash' => 'trash-2'],
            'lucide' => [],
        ],
        'flux' => ['normal' => [], 'animated' => []],
    ]);

    RecordingFluxIconCommand::$fail = ['trash-2'];

    $this->artisan('tailor')
        ->expectsChoice('What UI kit do you want to use?', 'lucide', [
            'hero' => 'Flux with Heroicons',
            'lucide' => 'Flux with Lucide Icons',
            'tall-stack' => 'Tall Stack UI',
        ])
        ->expectsChoice('What else would you like to tailor?', [], [
            'move_auth' => 'Move the auth folder',
        ])
        ->assertFailed();
});

it('does not touch icons when the Heroicons kit is selected', function () {
    $this->artisan('tailor')
        ->expectsChoice('What UI kit do you want to use?', 'hero', [
            'hero' => 'Flux with Heroicons',
            'lucide' => 'Flux with Lucide Icons',
            'tall-stack' => 'Tall Stack UI',
        ])
        ->expectsChoice('What else would you like to tailor?', [], [
            'move_auth' => 'Move the auth folder',
        ])
        ->assertSuccessful();

    expect(RecordingFluxIconCommand::$calls)->toBe(0);
});
