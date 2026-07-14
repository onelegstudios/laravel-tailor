<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Actions\RemoveTailorPackage;
use Onelegstudios\Tailor\Tests\Stubs\RecordingFluxIconCommand;

beforeEach(function () {
    // Isolate resource_path() from other parallel workers before capturing it.
    $this->appBase = $this->isolateApplicationPaths();

    RecordingFluxIconCommand::reset();
    // Have the stub write into the same directory the command publishes to, so
    // the download-verification step sees the icons and reports no failures.
    RecordingFluxIconCommand::$targetDir = resource_path('views/flux/icon');
    $this->app->make(Kernel::class)->registerCommand(new RecordingFluxIconCommand);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->appBase);
});

it('asks about the UI kit first, then the remaining options', function () {
    $this->artisan('tailor')
        ->expectsChoice('What UI kit do you want to use?', 'lucide', [
            'as-is' => 'Leave the starter kit as-is',
            'hero' => 'Flux with Heroicons',
            'lucide' => 'Flux with Lucide Icons',
        ])
        ->expectsChoice('What else would you like to tailor?', ['move-auth'], [
            'move-auth' => 'Move the auth folder',
        ])
        ->expectsConfirmation('Tailoring is done — remove the Tailor package now?', 'no')
        ->assertSuccessful();
});

it('defaults the UI kit to leaving the starter kit as-is', function () {
    $this->artisan('tailor')
        ->expectsChoice('What UI kit do you want to use?', 'as-is', [
            'as-is' => 'Leave the starter kit as-is',
            'hero' => 'Flux with Heroicons',
            'lucide' => 'Flux with Lucide Icons',
        ])
        ->expectsChoice('What else would you like to tailor?', [], [
            'move-auth' => 'Move the auth folder',
        ])
        ->expectsConfirmation('Tailoring is done — remove the Tailor package now?', 'no')
        ->assertSuccessful();
});

it('downloads the starter-kit Lucide icons when the Lucide kit is selected', function () {
    config()->set('tailor.settings.kits.lucide.icons', [
        'starter-kit' => [
            'heroicons' => ['home' => 'house', 'trash' => 'trash-2'],
            'lucide' => ['layout-grid' => 'layout-dashboard', 'folder-git-2' => 'folder-git-2'],
        ],
        'flux' => ['normal' => [], 'animated' => []],
    ]);

    $this->artisan('tailor')
        ->expectsChoice('What UI kit do you want to use?', 'lucide', [
            'as-is' => 'Leave the starter kit as-is',
            'hero' => 'Flux with Heroicons',
            'lucide' => 'Flux with Lucide Icons',
        ])
        ->expectsChoice('What else would you like to tailor?', [], [
            'move-auth' => 'Move the auth folder',
        ])
        ->expectsConfirmation('Tailoring is done — remove the Tailor package now?', 'no')
        ->assertSuccessful();

    expect(RecordingFluxIconCommand::$calls)->toBe(4)
        ->and(RecordingFluxIconCommand::$received)
        ->toBe(['house', 'trash-2', 'layout-dashboard', 'folder-git-2']);
});

it('downloads the Flux internal icons when the Lucide kit is selected', function () {
    config()->set('tailor.settings.kits.lucide.icons', [
        'starter-kit' => ['heroicons' => [], 'lucide' => []],
        'flux' => [
            'normal' => ['eye-dropper' => 'pipette'],
            'animated' => ['loading' => 'loader-circle'],
        ],
    ]);

    $this->artisan('tailor')
        ->expectsChoice('What UI kit do you want to use?', 'lucide', [
            'as-is' => 'Leave the starter kit as-is',
            'hero' => 'Flux with Heroicons',
            'lucide' => 'Flux with Lucide Icons',
        ])
        ->expectsChoice('What else would you like to tailor?', [], [
            'move-auth' => 'Move the auth folder',
        ])
        ->expectsConfirmation('Tailoring is done — remove the Tailor package now?', 'no')
        ->assertSuccessful();

    expect(RecordingFluxIconCommand::$calls)->toBe(2)
        ->and(RecordingFluxIconCommand::$received)->toBe(['pipette', 'loader-circle']);
});

it('uses the --ui-kit option instead of prompting for the UI kit', function () {
    $this->artisan('tailor', ['--ui-kit' => 'hero'])
        ->expectsChoice('What else would you like to tailor?', [], [
            'move-auth' => 'Move the auth folder',
        ])
        ->expectsConfirmation('Tailoring is done — remove the Tailor package now?', 'no')
        ->assertSuccessful();

    expect(RecordingFluxIconCommand::$calls)->toBe(0);
});

it('removes the package when the user confirms after tailoring', function () {
    $remover = Mockery::mock(RemoveTailorPackage::class);
    $remover->shouldReceive('execute')->once()->andReturnTrue();
    $this->app->instance(RemoveTailorPackage::class, $remover);

    $this->artisan('tailor', ['--ui-kit' => 'hero'])
        ->expectsChoice('What else would you like to tailor?', [], [
            'move-auth' => 'Move the auth folder',
        ])
        ->expectsConfirmation('Tailoring is done — remove the Tailor package now?', 'yes')
        ->assertSuccessful();
});

it('fails when given an unknown --ui-kit', function () {
    $this->artisan('tailor', ['--ui-kit' => 'bogus'])
        ->assertFailed();

    expect(RecordingFluxIconCommand::$calls)->toBe(0);
});

it('fails when an icon cannot be downloaded', function () {
    config()->set('tailor.settings.kits.lucide.icons', [
        'starter-kit' => [
            'heroicons' => ['home' => 'house', 'trash' => 'trash-2'],
            'lucide' => [],
        ],
        'flux' => ['normal' => [], 'animated' => []],
    ]);

    RecordingFluxIconCommand::$fail = ['trash-2'];

    $this->artisan('tailor')
        ->expectsChoice('What UI kit do you want to use?', 'lucide', [
            'as-is' => 'Leave the starter kit as-is',
            'hero' => 'Flux with Heroicons',
            'lucide' => 'Flux with Lucide Icons',
        ])
        ->expectsChoice('What else would you like to tailor?', [], [
            'move-auth' => 'Move the auth folder',
        ])
        ->assertFailed();
});

it('skips the UI kit prompt and drops "else" when no kits are configured', function () {
    config()->set('tailor.registry.kits', []);

    $this->artisan('tailor')
        ->expectsChoice('What would you like to tailor?', [], [
            'move-auth' => 'Move the auth folder',
        ])
        ->expectsConfirmation('Tailoring is done — remove the Tailor package now?', 'no')
        ->assertSuccessful();
});

it('skips the task prompt when no tasks are configured', function () {
    config()->set('tailor.registry.tasks', []);

    $this->artisan('tailor')
        ->expectsChoice('What UI kit do you want to use?', 'hero', [
            'as-is' => 'Leave the starter kit as-is',
            'hero' => 'Flux with Heroicons',
            'lucide' => 'Flux with Lucide Icons',
        ])
        ->expectsConfirmation('Tailoring is done — remove the Tailor package now?', 'no')
        ->assertSuccessful();
});

it('warns and does nothing when neither kits nor tasks are configured', function () {
    config()->set('tailor.registry.kits', []);
    config()->set('tailor.registry.tasks', []);

    $this->artisan('tailor')
        ->expectsOutputToContain('nothing to tailor')
        ->assertSuccessful();
});

it('downloads nothing when leaving the starter kit as-is', function () {
    $this->artisan('tailor')
        ->expectsChoice('What UI kit do you want to use?', 'as-is', [
            'as-is' => 'Leave the starter kit as-is',
            'hero' => 'Flux with Heroicons',
            'lucide' => 'Flux with Lucide Icons',
        ])
        ->expectsChoice('What else would you like to tailor?', [], [
            'move-auth' => 'Move the auth folder',
        ])
        ->expectsConfirmation('Tailoring is done — remove the Tailor package now?', 'no')
        ->assertSuccessful();

    expect(RecordingFluxIconCommand::$calls)->toBe(0);
});

it('downloads nothing when the Heroicons kit is selected', function () {
    $this->artisan('tailor', ['--ui-kit' => 'hero'])
        ->expectsChoice('What else would you like to tailor?', [], [
            'move-auth' => 'Move the auth folder',
        ])
        ->expectsConfirmation('Tailoring is done — remove the Tailor package now?', 'no')
        ->assertSuccessful();

    expect(RecordingFluxIconCommand::$calls)->toBe(0);
});
