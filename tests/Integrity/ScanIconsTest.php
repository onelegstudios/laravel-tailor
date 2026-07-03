<?php

$root = dirname(__DIR__, 2);

it('groups icons into starter-kit and flux in config/tailor.php', function () use ($root) {
    $icons = (require $root.'/config/tailor.php')['icons'] ?? [];

    $starterKit = $icons['starter-kit'];
    $flux = $icons['flux'];

    // Every set carries a non-empty Lucide replacement. Lucide entries default
    // to self-mapping when first scanned, but may be customized to a different
    // Lucide icon, so they are not required to equal the original name.
    expect($starterKit['lucide'])->each(fn ($replacement) => $replacement->toBeString()->not->toBe(''));
    expect($starterKit['heroicons'])->each(fn ($replacement) => $replacement->toBeString()->not->toBe(''));
    expect($flux['normal'])->each(fn ($replacement) => $replacement->toBeString()->not->toBe(''));
    expect($flux['animated'])->each(fn ($replacement) => $replacement->toBeString()->not->toBe(''));

    $heroicons = array_keys($starterKit['heroicons']);
    $lucide = array_keys($starterKit['lucide']);
    $normal = array_keys($flux['normal']);
    $animated = array_keys($flux['animated']);

    // starter-kit.heroicons: the app's own Heroicons (from the fixtures).
    expect($heroicons)
        ->not->toContain('folder-git-2')   // a Lucide override
        ->not->toContain('loading')        // an animated icon
        ->not->toContain('outline')        // an icon:variant value, not an icon
        ->not->toContain('calendar')       // used only inside a Flux component
        ->toContain('chevron-down')
        ->toContain('magnifying-glass')
        ->toContain('x-mark')
        ->toContain('envelope')            // via <flux:icon name="..."> attr syntax
        ->toContain('information-circle')
        ->toContain('users');

    // starter-kit.lucide: the locally overridden Lucide icons.
    expect($lucide)
        ->toContain('folder-git-2')
        ->toContain('book-open-text')
        ->toContain('layout-grid')
        ->toContain('chevrons-up-down');

    // flux.normal: icons only Flux's own components use (free + Pro), deduped
    // against starter-kit.heroicons.
    expect($normal)
        ->toContain('calendar')            // date-picker
        ->toContain('clock')               // time-picker
        ->toContain('eye-dropper')         // color-picker
        ->toContain('document')            // file-item, via @props default icon
        ->toContain('cloud-arrow-up')      // file-upload dropzone @props default
        ->toContain('exclamation-triangle')// error, via @props default icon
        ->not->toContain('chevron-down')   // already an app heroicon
        ->not->toContain('loading');

    // flux.animated: Flux's built-in animated pseudo-icons.
    expect($animated)
        ->toContain('loading')
        ->not->toContain('chevron-down');

    // No icon is duplicated between the starter-kit and flux groups.
    $starterKitNames = array_merge($heroicons, $lucide);
    expect(array_intersect($starterKitNames, array_merge($normal, $animated)))->toBe([]);
});

it('keeps config/tailor.php in sync with the fixtures', function () use ($root) {
    exec('php '.escapeshellarg($root.'/bin/scan-icons').' --check 2>&1', $output, $status);

    expect($status)->toBe(0, implode("\n", $output));
});

it('shows help for -h and --help without writing', function (string $flag) use ($root) {
    exec('php '.escapeshellarg($root.'/bin/scan-icons').' '.$flag.' 2>&1', $output, $status);

    $text = implode("\n", $output);
    expect($status)->toBe(0)
        ->and($text)->toContain('Usage:')
        ->and($text)->toContain('-c, --check')
        ->and($text)->toContain('-p, --prune')
        ->and($text)->toContain('-h, --help');
})->with(['-h', '--help']);

it('warns about stale icons and removes them with --prune', function () use ($root) {
    $script = escapeshellarg($root.'/bin/scan-icons');
    $configFile = $root.'/config/tailor.php';
    $original = file_get_contents($configFile);

    try {
        // Inject a stale entry no scan will ever rediscover.
        file_put_contents($configFile, preg_replace(
            "/('heroicons' => \[\n)/",
            "$1                'zzz-ghost' => '',\n",
            $original,
            1
        ));

        // Append-only run: warns, exits 0, and keeps the stale entry.
        exec("php {$script} 2>&1", $warnOut, $warnStatus);
        expect($warnStatus)->toBe(0)
            ->and(implode("\n", $warnOut))->toContain('Warning')->toContain('zzz-ghost');
        expect(file_get_contents($configFile))->toContain('zzz-ghost');

        // Prune run: removes the stale entry.
        exec("php {$script} --prune 2>&1", $pruneOut, $pruneStatus);
        expect($pruneStatus)->toBe(0)
            ->and(implode("\n", $pruneOut))->toContain('zzz-ghost');
        expect(file_get_contents($configFile))->not->toContain('zzz-ghost');
    } finally {
        file_put_contents($configFile, $original);
    }
});

it('reports newly found icons', function () use ($root) {
    $script = escapeshellarg($root.'/bin/scan-icons');
    $configFile = $root.'/config/tailor.php';
    $original = file_get_contents($configFile);

    try {
        file_put_contents($configFile, "<?php\n\nreturn [\n\n];\n");

        exec("php {$script} 2>&1", $output, $status);
        $text = implode("\n", $output);

        expect($status)->toBe(0)
            ->and($text)->toContain('new icon(s):')
            ->and($text)->toContain('chevron-down');
    } finally {
        file_put_contents($configFile, $original);
    }
});
