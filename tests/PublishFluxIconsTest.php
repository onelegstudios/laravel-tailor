<?php

use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Actions\PublishFluxIcons;
use Onelegstudios\Tailor\Tests\Stubs\RecordingFluxIconCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/tailor-flux-icons-'.uniqid();
    mkdir($this->tempDir, 0755, true);

    RecordingFluxIconCommand::reset();
    RecordingFluxIconCommand::$targetDir = $this->tempDir;
    $this->app->make(Kernel::class)->registerCommand(new RecordingFluxIconCommand);

    $this->action = new PublishFluxIcons(new Filesystem);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->tempDir);
});

it('downloads the deduped flux replacements via flux:icon', function () {
    $this->action->execute(
        $this->tempDir,
        ['eye-dropper' => 'pipette', 'calendar' => 'calendar'],
        ['loading' => 'loader-circle'],
    );

    expect(RecordingFluxIconCommand::$calls)->toBe(3)
        ->and(RecordingFluxIconCommand::$received)->toBe(['pipette', 'calendar', 'loader-circle']);
});

it('warns when a flux icon fails to download', function () {
    RecordingFluxIconCommand::$fail = ['pipette'];

    $buffer = new BufferedOutput;
    $output = new OutputStyle(new ArrayInput([]), $buffer);

    $this->action->execute($this->tempDir, ['eye-dropper' => 'pipette', 'calendar' => 'calendar'], [], $output);

    expect($buffer->fetch())
        ->toContain('failed to download: pipette')
        ->not->toContain('failed to download: calendar');

    // A glyph that never downloaded cannot be aliased to its original name.
    expect(file_exists($this->tempDir.'/eye-dropper.blade.php'))->toBeFalse();
});

it('aliases a downloaded icon under its original Flux name', function () {
    $this->action->execute($this->tempDir, ['eye-dropper' => 'pipette'], []);

    expect(file_exists($this->tempDir.'/pipette.blade.php'))->toBeTrue()
        ->and(file_exists($this->tempDir.'/eye-dropper.blade.php'))->toBeTrue();

    // The alias carries the downloaded Lucide glyph (pipette), not the original.
    expect(file_get_contents($this->tempDir.'/eye-dropper.blade.php'))
        ->toContain('data-icon="pipette"');
});

it('does not create a separate alias when the name is unchanged', function () {
    $this->action->execute($this->tempDir, ['calendar' => 'calendar'], []);

    // Only the downloaded file exists; no needless copy was written.
    expect(file_exists($this->tempDir.'/calendar.blade.php'))->toBeTrue();
    expect((new Filesystem)->files($this->tempDir))->toHaveCount(1);
});

it('adds a Tailwind animation class to animated icon aliases', function () {
    $this->action->execute($this->tempDir, [], ['loading' => 'loader-circle']);

    $alias = file_get_contents($this->tempDir.'/loading.blade.php');
    $source = file_get_contents($this->tempDir.'/loader-circle.blade.php');

    expect($alias)
        ->toContain('data-icon="loader-circle"')
        ->toContain("Flux::classes('shrink-0 animate-spin')");

    // The downloaded static icon stays un-animated.
    expect($source)
        ->toContain("Flux::classes('shrink-0')")
        ->not->toContain('animate-spin');
});

it('does nothing when there are no flux icons to publish', function () {
    $this->action->execute($this->tempDir, [], []);

    expect(RecordingFluxIconCommand::$calls)->toBe(0);
});
