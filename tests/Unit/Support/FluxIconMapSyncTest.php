<?php

use Illuminate\Filesystem\Filesystem;

$GLOBALS['fluxIconMapSyncClass'] ??= require_once dirname(__DIR__, 3).'/bin/sync-flux-icon-map.php';

function fluxIconMapSync(string $method, mixed ...$arguments): mixed
{
    return call_user_func([$GLOBALS['fluxIconMapSyncClass'], $method], ...$arguments);
}

it('extracts static flux icon tags', function (): void {
    $result = fluxIconMapSync('extractIconsFromBlade', <<<'BLADE'
<flux:icon.chevron-right />
<flux:icon.qr-code class="size-4" />
BLADE);

    expect($result['icons'])->toBe([
        'chevron-right',
        'qr-code',
    ])->and($result['warnings'])->toBe([]);
});

it('extracts icons from dynamic flux icon attributes', function (): void {
    $result = fluxIconMapSync('extractIconsFromBlade', <<<'BLADE'
<flux:icon name="users" />
<flux:icon
    icon="chevrons-up-down"
    variant="micro"
/>
<flux:modal name="create-team" />
BLADE);

    expect($result['icons'])->toBe([
        'chevrons-up-down',
        'users',
    ])->and($result['warnings'])->toBe([]);
});

it('extracts icon shorthand attributes from other flux components', function (): void {
    $result = fluxIconMapSync('extractIconsFromBlade', <<<'BLADE'
<flux:button icon="plus" />
<flux:menu.item icon="cog">Settings</flux:menu.item>
<flux:button icon:trailing="chevron-down">More</flux:button>
<flux:profile icon-trailing="chevron-down" />
<flux:button icon:variant="mini" icon:class="size-4" />
BLADE);

    expect($result['icons'])->toBe([
        'chevron-down',
        'cog',
        'plus',
    ])->and($result['warnings'])->toBe([]);
});

it('extracts literal icon values from bound expressions and warns on unresolved ones', function (): void {
    $result = fluxIconMapSync('extractIconsFromBlade', <<<'BLADE'
<flux:button :icon="'plus'" />
<flux:button :icon="$condition ? 'eye' : 'pencil'" />
<flux:icon :name="'users'" />
<flux:button :icon="$iconName" />
BLADE, 'inline.blade.php');

    expect($result['icons'])->toBe([
        'eye',
        'pencil',
        'plus',
        'users',
    ])->and($result['warnings'])->toHaveCount(1);

    expect($result['warnings'][0])
        ->toContain('inline.blade.php')
        ->toContain(':icon')
        ->toContain('<flux:button>');
});

it('ignores flux tags inside Blade and HTML comments', function (): void {
    $result = fluxIconMapSync('extractIconsFromBlade', <<<'BLADE'
<flux:button icon="plus" />

{{--
    <flux:icon name="ghost" />
--}}

<!--
    <flux:button :icon="$iconName" />
-->
BLADE, 'inline.blade.php');

    expect($result['icons'])->toBe([
        'plus',
    ])->and($result['warnings'])->toBe([]);
});

it('fails when an existing config file does not return an array', function (): void {
    $filesystem = new Filesystem;
    $root = sys_get_temp_dir().'/flux-icon-map-'.bin2hex(random_bytes(8));
    $viewsRoot = $root.'/views';
    $configPath = $root.'/config/tailor.php';

    $filesystem->ensureDirectoryExists($viewsRoot);
    $filesystem->ensureDirectoryExists(dirname($configPath));
    $filesystem->put($configPath, "<?php\n\nreturn 'invalid';\n");

    try {
        expect(fn () => fluxIconMapSync('sync', $viewsRoot, $configPath, $filesystem))
            ->toThrow(RuntimeException::class, 'must return an array');
    } finally {
        $filesystem->deleteDirectory($root);
    }
});

it('preserves unrelated tailor config when syncing icons', function (): void {
    $filesystem = new Filesystem;
    $root = sys_get_temp_dir().'/flux-icon-map-'.bin2hex(random_bytes(8));
    $viewsRoot = $root.'/views';
    $configPath = $root.'/config/tailor.php';

    $filesystem->ensureDirectoryExists($viewsRoot);
    $filesystem->ensureDirectoryExists(dirname($configPath));

    try {
        $filesystem->put($viewsRoot.'/icons.blade.php', <<<'BLADE'
<flux:button icon="plus" />
BLADE);

        $filesystem->put($configPath, <<<'PHP'
<?php

return [
    'feature_flag' => true,
];
PHP);

        $summary = fluxIconMapSync('sync', $viewsRoot, $configPath, $filesystem);
        $writtenConfig = require $configPath;

        expect($summary['config'])->toBe([
            'mappings' => [
                'exclamation-triangle' => null,
                'loading' => null,
                'plus' => null,
            ],
            'removed' => [],
        ])->and($writtenConfig)->toBe([
            'feature_flag' => true,
            'icons' => [
                'mappings' => [
                    'exclamation-triangle' => null,
                    'loading' => null,
                    'plus' => null,
                ],
            ],
        ]);
    } finally {
        $filesystem->deleteDirectory($root);
    }
});

it('preserves mappings and tracks new and removed icons across reruns', function (): void {
    $filesystem = new Filesystem;
    $root = sys_get_temp_dir().'/flux-icon-map-'.bin2hex(random_bytes(8));
    $viewsRoot = $root.'/views';
    $configPath = $root.'/config/tailor.php';

    $filesystem->ensureDirectoryExists($viewsRoot);

    try {
        $filesystem->put($viewsRoot.'/icons.blade.php', <<<'BLADE'
<flux:button icon="plus" />
<flux:icon.chevron-down />
BLADE);

        $firstRun = fluxIconMapSync('sync', $viewsRoot, $configPath, $filesystem);

        expect($firstRun['detected'])->toBe([
            'chevron-down',
            'exclamation-triangle',
            'loading',
            'plus',
        ]);

        $filesystem->put($configPath, <<<'PHP'
<?php

return [
    'mappings' => [
        'chevron-down' => 'chevron-right',
        'plus' => 'circle-plus',
    ],
    'new' => [
        'chevron-down',
        'plus',
    ],
    'removed' => [],
];
PHP);

        $filesystem->put($viewsRoot.'/icons.blade.php', <<<'BLADE'
<flux:button icon="plus" />
<flux:button icon="bars-2" />
BLADE);

        $secondRun = fluxIconMapSync('sync', $viewsRoot, $configPath, $filesystem);
        $writtenConfig = require $configPath;
        $writtenContents = $filesystem->get($configPath);

        expect($writtenContents)->toContain("'bars-2' => null")
            ->not->toContain("'new' =>")
            ->not->toContain('NULL')
            ->and($secondRun['new'])->toBe([
                'bars-2',
                'exclamation-triangle',
                'loading',
            ])->and($writtenConfig)->toBe([
                'icons' => [
                    'mappings' => [
                        'bars-2' => null,
                        'chevron-down' => 'chevron-right',
                        'exclamation-triangle' => null,
                        'loading' => null,
                        'plus' => 'circle-plus',
                    ],
                    'removed' => [
                        'chevron-down' => 'chevron-right',
                    ],
                ],
            ]);
    } finally {
        $filesystem->deleteDirectory($root);
    }
});

it('always includes flux internal icons in mappings', function (): void {
    $filesystem = new Filesystem;
    $root = sys_get_temp_dir().'/flux-icon-map-'.bin2hex(random_bytes(8));
    $viewsRoot = $root.'/views';
    $configPath = $root.'/config/tailor.php';

    $filesystem->ensureDirectoryExists($viewsRoot);

    try {
        $filesystem->put($viewsRoot.'/icons.blade.php', <<<'BLADE'
<flux:button icon="plus" />
BLADE);

        $summary = fluxIconMapSync('sync', $viewsRoot, $configPath, $filesystem);
        $writtenConfig = require $configPath;
        $writtenContents = $filesystem->get($configPath);

        expect($summary['detected'])->toBe([
            'exclamation-triangle',
            'loading',
            'plus',
        ])->and($summary['new'])->toBe([
            'exclamation-triangle',
            'loading',
            'plus',
        ])->and($summary['config'])->toBe([
            'mappings' => [
                'exclamation-triangle' => null,
                'loading' => null,
                'plus' => null,
            ],
            'removed' => [],
        ])->and($writtenConfig)->toBe([
            'icons' => [
                'mappings' => [
                    'exclamation-triangle' => null,
                    'loading' => null,
                    'plus' => null,
                ],
            ],
        ])->and($writtenContents)->not->toContain("'removed' =>");
    } finally {
        $filesystem->deleteDirectory($root);
    }
});

it('reads wrapped icon config files', function (): void {
    $filesystem = new Filesystem;
    $root = sys_get_temp_dir().'/flux-icon-map-'.bin2hex(random_bytes(8));
    $viewsRoot = $root.'/views';
    $configPath = $root.'/config/tailor.php';

    $filesystem->ensureDirectoryExists($viewsRoot);
    $filesystem->ensureDirectoryExists(dirname($configPath));

    try {
        $filesystem->put($viewsRoot.'/icons.blade.php', <<<'BLADE'
<flux:button icon="plus" />
BLADE);

        $filesystem->put($configPath, <<<'PHP'
<?php

return [
    'icons' => [
        'mappings' => [
            'plus' => 'circle-plus',
        ],
        'new' => [
            'plus',
        ],
        'removed' => [],
    ],
];
PHP);

        $summary = fluxIconMapSync('sync', $viewsRoot, $configPath, $filesystem);

        expect($summary['config'])->toBe([
            'mappings' => [
                'exclamation-triangle' => null,
                'loading' => null,
                'plus' => 'circle-plus',
            ],
            'removed' => [],
        ])->and($summary['new'])->toBe([
            'exclamation-triangle',
            'loading',
        ]);
    } finally {
        $filesystem->deleteDirectory($root);
    }
});
