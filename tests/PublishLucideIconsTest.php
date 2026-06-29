<?php

use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Actions\PublishLucideIcons;
use Onelegstudios\Tailor\Tests\Stubs\RecordingFluxIconCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function () {
    RecordingFluxIconCommand::reset();
    $this->app->make(Kernel::class)->registerCommand(new RecordingFluxIconCommand);

    $this->tempDir = sys_get_temp_dir().'/tailor-flux-'.uniqid();
    mkdir($this->tempDir, 0755, true);

    $this->action = new PublishLucideIcons(new Filesystem);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->tempDir);
});

it('removes stale icon blades but leaves the new set and other files alone', function () {
    RecordingFluxIconCommand::$targetDir = $this->tempDir;

    file_put_contents($this->tempDir.'/book-open-text.blade.php', 'old');
    file_put_contents($this->tempDir.'/layout-grid.blade.php', 'old');
    file_put_contents($this->tempDir.'/keep.txt', 'keep');

    $this->action->execute($this->tempDir, ['house']);

    expect(file_exists($this->tempDir.'/book-open-text.blade.php'))->toBeFalse()
        ->and(file_exists($this->tempDir.'/layout-grid.blade.php'))->toBeFalse()
        ->and(file_exists($this->tempDir.'/keep.txt'))->toBeTrue()
        ->and(file_exists($this->tempDir.'/house.blade.php'))->toBeTrue();
});

it('leaves the existing icons in place when a download fails', function () {
    RecordingFluxIconCommand::$targetDir = $this->tempDir;
    RecordingFluxIconCommand::$fail = ['house'];

    file_put_contents($this->tempDir.'/book-open-text.blade.php', 'old');
    file_put_contents($this->tempDir.'/layout-grid.blade.php', 'old');

    $this->action->execute($this->tempDir, ['house']);

    expect(file_exists($this->tempDir.'/book-open-text.blade.php'))->toBeTrue()
        ->and(file_exists($this->tempDir.'/layout-grid.blade.php'))->toBeTrue();
});

it('downloads the deduped, non-empty icon set via flux:icon', function () {
    $this->action->execute($this->tempDir, ['house', 'search', 'house', '', 'file-text']);

    expect(RecordingFluxIconCommand::$calls)->toBe(3)
        ->and(RecordingFluxIconCommand::$received)->toBe(['house', 'search', 'file-text']);
});

it('does not call flux:icon when there are no icons to download', function () {
    $this->action->execute($this->tempDir, ['', '']);

    expect(RecordingFluxIconCommand::$calls)->toBe(0);
});

it('still downloads when the icon directory does not yet exist', function () {
    $this->action->execute($this->tempDir.'/missing', ['house']);

    expect(RecordingFluxIconCommand::$calls)->toBe(1)
        ->and(RecordingFluxIconCommand::$received)->toBe(['house']);
});

it('reports progress and warns about icons that fail to download', function () {
    RecordingFluxIconCommand::$targetDir = $this->tempDir;
    RecordingFluxIconCommand::$fail = ['search'];

    $buffer = new BufferedOutput;
    $output = new OutputStyle(new ArrayInput([]), $buffer);

    $this->action->execute($this->tempDir, ['house', 'search'], $output);

    expect($buffer->fetch())
        ->toContain('Downloading 2 icon(s) from Lucide')
        ->toContain('failed to download: search')
        ->not->toContain('failed to download: house');
});
