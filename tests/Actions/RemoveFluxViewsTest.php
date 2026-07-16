<?php

use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Actions\RemoveFluxViews;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/tailor-remove-flux-views-'.uniqid();
    mkdir($this->tempDir.'/navlist', 0755, true);
    $this->action = new RemoveFluxViews(new Filesystem);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->tempDir);
});

it('deletes the named view blades and reports them', function () {
    file_put_contents($this->tempDir.'/navlist/group.blade.php', 'x');
    file_put_contents($this->tempDir.'/navlist/item.blade.php', 'x');

    $removed = $this->action->execute($this->tempDir, ['navlist/group', 'navlist/item']);

    expect($removed)->toBe(['navlist/group', 'navlist/item'])
        ->and(file_exists($this->tempDir.'/navlist/group.blade.php'))->toBeFalse()
        ->and(file_exists($this->tempDir.'/navlist/item.blade.php'))->toBeFalse();
});

it('leaves unnamed view blades in place', function () {
    file_put_contents($this->tempDir.'/navlist/group.blade.php', 'x');
    file_put_contents($this->tempDir.'/navlist/item.blade.php', 'x');

    $this->action->execute($this->tempDir, ['navlist/group']);

    expect(file_exists($this->tempDir.'/navlist/item.blade.php'))->toBeTrue();
});

it('removes the folder the last view leaves empty', function () {
    file_put_contents($this->tempDir.'/navlist/group.blade.php', 'x');

    $this->action->execute($this->tempDir, ['navlist/group']);

    expect(is_dir($this->tempDir.'/navlist'))->toBeFalse()
        ->and(is_dir($this->tempDir))->toBeTrue();
});

it('keeps a folder that still holds another view', function () {
    file_put_contents($this->tempDir.'/navlist/group.blade.php', 'x');
    file_put_contents($this->tempDir.'/navlist/item.blade.php', 'x');

    $this->action->execute($this->tempDir, ['navlist/group']);

    expect(is_dir($this->tempDir.'/navlist'))->toBeTrue();
});

it('leaves the icons published alongside the removed view alone', function () {
    mkdir($this->tempDir.'/icon', 0755, true);
    file_put_contents($this->tempDir.'/icon/layout-grid.blade.php', 'x');
    file_put_contents($this->tempDir.'/navlist/group.blade.php', 'x');

    $this->action->execute($this->tempDir, ['navlist/group']);

    expect(file_exists($this->tempDir.'/icon/layout-grid.blade.php'))->toBeTrue();
});

it('ignores a view that is not on disk', function () {
    $removed = $this->action->execute($this->tempDir, ['navlist/group']);

    expect($removed)->toBe([])
        ->and(is_dir($this->tempDir.'/navlist'))->toBeTrue();
});

it('does nothing when the directory does not exist', function () {
    $missing = $this->tempDir.'/missing';

    expect($this->action->execute($missing, ['navlist/group']))->toBe([])
        ->and(is_dir($missing))->toBeFalse();
});
