<?php

use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Actions\PublishFluxIcons;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/tailor-flux-icons-'.uniqid();
    mkdir($this->tempDir, 0755, true);

    $this->action = new PublishFluxIcons(new Filesystem);

    // Simulate flux:icon having already downloaded a glyph into the icon dir, so
    // the aliasing step has a source blade to copy from.
    $this->download = function (string $icon) {
        file_put_contents(
            $this->tempDir.'/'.$icon.'.blade.php',
            <<<BLADE
                @php
                    \$classes = Flux::classes('shrink-0')->add('size-6');
                @endphp
                <svg {{ \$attributes->class(\$classes) }} data-icon="{$icon}"></svg>
                BLADE,
        );
    };
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->tempDir);
});

it('lists the flux replacement names to fold into the download', function () {
    expect($this->action->replacements(
        ['eye-dropper' => 'pipette', 'calendar' => 'calendar'],
        ['loading' => 'loader-circle'],
    ))->toBe(['pipette', 'calendar', 'loader-circle']);
});

it('returns no replacements when there are no flux icons', function () {
    expect($this->action->replacements([], []))->toBe([]);
});

it('renames a downloaded icon to its original Flux name', function () {
    ($this->download)('pipette');

    $this->action->applyAliases($this->tempDir, ['eye-dropper' => 'pipette'], []);

    expect(file_exists($this->tempDir.'/eye-dropper.blade.php'))->toBeTrue();

    // The alias carries the downloaded Lucide glyph (pipette), not the original.
    expect(file_get_contents($this->tempDir.'/eye-dropper.blade.php'))
        ->toContain('data-icon="pipette"');

    // Flux only references the original name, so the Lucide-named source is gone.
    expect(file_exists($this->tempDir.'/pipette.blade.php'))->toBeFalse();
});

it('keeps the Lucide-named source when it must be preserved', function () {
    ($this->download)('chevrons-up-down');

    // A starter-kit icon references chevrons-up-down by that name, so it stays.
    $this->action->applyAliases(
        $this->tempDir,
        ['chevron-up-down' => 'chevrons-up-down'],
        [],
        ['chevrons-up-down'],
    );

    expect(file_exists($this->tempDir.'/chevron-up-down.blade.php'))->toBeTrue();
    expect(file_exists($this->tempDir.'/chevrons-up-down.blade.php'))->toBeTrue();
});

it('does not alias an icon that never downloaded', function () {
    // No source blade on disk — mirrors a failed download.
    $this->action->applyAliases($this->tempDir, ['eye-dropper' => 'pipette'], []);

    expect(file_exists($this->tempDir.'/eye-dropper.blade.php'))->toBeFalse();
});

it('does not create a separate alias when the name is unchanged', function () {
    ($this->download)('calendar');

    $this->action->applyAliases($this->tempDir, ['calendar' => 'calendar'], []);

    // Only the downloaded file exists; no needless copy was written.
    expect((new Filesystem)->files($this->tempDir))->toHaveCount(1);
});

it('adds a Tailwind animation class to animated icon aliases', function () {
    ($this->download)('loader-circle');

    $this->action->applyAliases($this->tempDir, [], ['loading' => 'loader-circle']);

    $alias = file_get_contents($this->tempDir.'/loading.blade.php');

    expect($alias)
        ->toContain('data-icon="loader-circle"')
        ->toContain("Flux::classes('shrink-0 animate-spin')");

    // The animated alias replaces the source rather than duplicating it.
    expect(file_exists($this->tempDir.'/loader-circle.blade.php'))->toBeFalse();
});

it('does nothing when there are no flux icons to alias', function () {
    $this->action->applyAliases($this->tempDir, [], []);

    expect((new Filesystem)->files($this->tempDir))->toHaveCount(0);
});
