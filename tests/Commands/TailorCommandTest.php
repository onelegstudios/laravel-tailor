<?php

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Actions\RemoveTailorPackage;
use Onelegstudios\Tailor\Commands\TailorCommand;
use Onelegstudios\Tailor\Kits\AsIsKit;
use Onelegstudios\Tailor\Tests\Stubs\RecordingFluxIconCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Run the tailor command supplying no answers, and hand back its exit status.
 *
 * A non-interactive run takes every prompt at its default: Prompts falls back to
 * Symfony's question helper while testing, and that helper returns the question's
 * default outright rather than reading any input. That is the only way a default
 * gets exercised here — expectsQuestion() stubs the question out and hands back
 * the answer the test picked, so the default is never consulted and a test built
 * on it keeps passing whatever the default is changed to.
 */
function runTailorTakingEveryDefault(): int
{
    $input = new ArrayInput([]);
    $input->setInteractive(false);

    $command = app(TailorCommand::class);
    $command->setLaravel(app());

    return $command->run($input, new BufferedOutput);
}

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

// expectsQuestion() is backed by ordered() Mockery expectations, so the sequence
// below is what this test asserts: the kit is settled before the tasks are offered.
// What each prompt offers is left to the two tests after it.
it('asks about the UI kit first, then the remaining options', function () {
    $this->artisan('tailor')
        ->expectsQuestion('Which icon set do you want?', 'lucide')
        ->expectsQuestion('What else would you like to tailor?', ['move-auth'])
        ->expectsConfirmation('Tailoring is done — remove the Tailor package now?', 'no')
        ->assertSuccessful();
});

// The two tests below are the only place the offered options are spelled out.
// Everywhere else answers with expectsQuestion(), which asserts the prompt was
// asked without restating what it holds — so registering a kit or a task means
// updating one of these and nothing else.
it('offers every registered UI kit', function () {
    $this->artisan('tailor')
        ->expectsChoice('Which icon set do you want?', 'as-is', [
            'as-is' => 'Flux with mixed icons',
            'hero' => 'Flux with Heroicons only',
            'lucide' => 'Flux with Lucide only',
        ])
        ->expectsQuestion('What else would you like to tailor?', [])
        ->expectsConfirmation('Tailoring is done — remove the Tailor package now?', 'no')
        ->assertSuccessful();
});

it('offers every registered task', function () {
    $this->artisan('tailor', ['--ui-kit' => 'as-is'])
        ->expectsChoice('What else would you like to tailor?', [], [
            'move-auth' => 'Move the auth folder',
            'move-components' => 'Move non-routed pages components',
            'convert-partials' => 'Convert partials into components',
            'group-components' => 'Group components into subfolders',
            'remove-flux-overrides' => 'Remove published Flux overrides',
        ])
        ->expectsConfirmation('Tailoring is done — remove the Tailor package now?', 'no')
        ->assertSuccessful();
});

it('runs the selected move-components task', function () {
    $this->artisan('tailor', ['--ui-kit' => 'as-is'])
        ->expectsQuestion('What else would you like to tailor?', ['move-components'])
        ->expectsConfirmation('Tailoring is done — remove the Tailor package now?', 'no')
        ->assertSuccessful();
});

it('runs the selected tasks in registry order rather than the order they were selected', function () {
    $files = new Filesystem;
    $files->ensureDirectoryExists(resource_path('views/layouts'));
    $files->ensureDirectoryExists(resource_path('views/partials'));
    $files->put(resource_path('views/partials/head.blade.php'), '<title>{{ $title }}</title>');
    $files->put(resource_path('views/layouts/app.blade.php'), "@include('partials.head')");

    // Prompts returns the keys in the order they were toggled, so selecting
    // group-components first is what a user ticking bottom-up produces.
    $this->artisan('tailor', ['--ui-kit' => 'as-is'])
        ->expectsQuestion('What else would you like to tailor?', ['group-components', 'convert-partials'])
        ->expectsConfirmation('Tailoring is done — remove the Tailor package now?', 'no')
        ->assertSuccessful();

    // Grouping only sees head if convert-partials ran first and put it at the root.
    expect(file_exists(resource_path('views/components/layout/head.blade.php')))->toBeTrue()
        ->and(file_get_contents(resource_path('views/layouts/app.blade.php')))
        ->toBe('<x-layout.head :title="$title ?? null" />');
});

it('announces each task as it runs so a slow task does not look like a hang', function () {
    $this->artisan('tailor', ['--ui-kit' => 'as-is'])
        ->expectsQuestion('What else would you like to tailor?', ['move-auth', 'move-components'])
        ->expectsOutputToContain('Move the auth folder...')
        ->expectsOutputToContain('✓ Move the auth folder')
        ->expectsOutputToContain('Move non-routed pages components...')
        ->expectsOutputToContain('✓ Move non-routed pages components')
        ->expectsConfirmation('Tailoring is done — remove the Tailor package now?', 'no')
        ->assertSuccessful();
});

it('defaults the UI kit to leaving the starter kit as-is', function () {
    // Leaves the kit prompt as the only one with a default worth pinning here. The
    // task prompt's fallback offers no "None" while testing, so its default is not
    // a thing this run can take.
    config()->set('tailor.registry.tasks', []);

    $asIs = Mockery::mock(AsIsKit::class)->makePartial();
    $asIs->shouldReceive('apply')->once()->andReturn([]);
    $this->app->instance(AsIsKit::class, $asIs);

    // Taking the kit prompt at its default has to be what runs the as-is kit, so a
    // default of anything else leaves this expectation unmet.
    expect(runTailorTakingEveryDefault())->toBe(Command::SUCCESS);
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
        ->expectsQuestion('Which icon set do you want?', 'lucide')
        ->expectsQuestion('What else would you like to tailor?', [])
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
        ->expectsQuestion('Which icon set do you want?', 'lucide')
        ->expectsQuestion('What else would you like to tailor?', [])
        ->expectsConfirmation('Tailoring is done — remove the Tailor package now?', 'no')
        ->assertSuccessful();

    expect(RecordingFluxIconCommand::$calls)->toBe(2)
        ->and(RecordingFluxIconCommand::$received)->toBe(['pipette', 'loader-circle']);
});

it('uses the --ui-kit option instead of prompting for the UI kit', function () {
    $this->artisan('tailor', ['--ui-kit' => 'hero'])
        ->expectsQuestion('What else would you like to tailor?', [])
        ->expectsConfirmation('Tailoring is done — remove the Tailor package now?', 'no')
        ->assertSuccessful();

    expect(RecordingFluxIconCommand::$calls)->toBe(0);
});

it('removes the package when the user confirms after tailoring', function () {
    $remover = Mockery::mock(RemoveTailorPackage::class);
    $remover->shouldReceive('execute')->once()->andReturnTrue();
    $this->app->instance(RemoveTailorPackage::class, $remover);

    $this->artisan('tailor', ['--ui-kit' => 'hero'])
        ->expectsQuestion('What else would you like to tailor?', [])
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
        ->expectsQuestion('Which icon set do you want?', 'lucide')
        ->expectsQuestion('What else would you like to tailor?', [])
        ->assertFailed();
});

it('skips the UI kit prompt and drops "else" when no kits are configured', function () {
    config()->set('tailor.registry.kits', []);

    $this->artisan('tailor')
        ->expectsQuestion('What would you like to tailor?', [])
        ->expectsConfirmation('Tailoring is done — remove the Tailor package now?', 'no')
        ->assertSuccessful();
});

it('skips the task prompt when no tasks are configured', function () {
    config()->set('tailor.registry.tasks', []);

    $this->artisan('tailor')
        ->expectsQuestion('Which icon set do you want?', 'hero')
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
        ->expectsQuestion('Which icon set do you want?', 'as-is')
        ->expectsQuestion('What else would you like to tailor?', [])
        ->expectsConfirmation('Tailoring is done — remove the Tailor package now?', 'no')
        ->assertSuccessful();

    expect(RecordingFluxIconCommand::$calls)->toBe(0);
});

it('downloads nothing when the Heroicons kit is selected', function () {
    $this->artisan('tailor', ['--ui-kit' => 'hero'])
        ->expectsQuestion('What else would you like to tailor?', [])
        ->expectsConfirmation('Tailoring is done — remove the Tailor package now?', 'no')
        ->assertSuccessful();

    expect(RecordingFluxIconCommand::$calls)->toBe(0);
});
