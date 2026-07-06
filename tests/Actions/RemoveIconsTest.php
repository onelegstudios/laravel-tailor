<?php

use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Actions\RemoveIcons;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/tailor-remove-icons-'.uniqid();
    mkdir($this->tempDir, 0755, true);
    $this->action = new RemoveIcons(new Filesystem);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->tempDir);
});

it('deletes the named icon blades', function () {
    file_put_contents($this->tempDir.'/book-open-text.blade.php', 'x');
    file_put_contents($this->tempDir.'/layout-grid.blade.php', 'x');

    $this->action->execute($this->tempDir, ['book-open-text', 'layout-grid']);

    expect(file_exists($this->tempDir.'/book-open-text.blade.php'))->toBeFalse()
        ->and(file_exists($this->tempDir.'/layout-grid.blade.php'))->toBeFalse();
});

it('leaves unnamed icon blades in place', function () {
    file_put_contents($this->tempDir.'/book-open-text.blade.php', 'x');
    file_put_contents($this->tempDir.'/house.blade.php', 'x');

    $this->action->execute($this->tempDir, ['book-open-text']);

    expect(file_exists($this->tempDir.'/house.blade.php'))->toBeTrue();
});

it('ignores an icon that is not on disk', function () {
    file_put_contents($this->tempDir.'/house.blade.php', 'x');

    $this->action->execute($this->tempDir, ['not-here']);

    expect(file_exists($this->tempDir.'/house.blade.php'))->toBeTrue();
});

it('does nothing when the directory does not exist', function () {
    $missing = $this->tempDir.'/missing';

    $this->action->execute($missing, ['book-open-text']);

    expect(is_dir($missing))->toBeFalse();
});
