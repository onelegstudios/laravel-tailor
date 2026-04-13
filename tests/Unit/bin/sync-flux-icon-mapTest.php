<?php

use Illuminate\Filesystem\Filesystem;

$GLOBALS['fluxIconMapSyncClass'] ??= require_once dirname(__DIR__, 3).'/bin/sync-flux-icon-map.php';

function fluxIconMapSync(string $method, mixed ...$arguments): mixed
{
    return call_user_func([$GLOBALS['fluxIconMapSyncClass'], $method], ...$arguments);
}

it('defaults to syncing the package workbench views into the package tailor config', function (): void {
    $projectRoot = dirname(__DIR__, 3);
    $defaultViewsRoot = (new ReflectionMethod($GLOBALS['fluxIconMapSyncClass'], 'defaultViewsRoot'))->invoke(null);
    $defaultConfigPath = (new ReflectionMethod($GLOBALS['fluxIconMapSyncClass'], 'defaultConfigPath'))->invoke(null);

    expect($defaultViewsRoot)->toBe($projectRoot.DIRECTORY_SEPARATOR.'workbench'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views')
        ->and($defaultConfigPath)->toBe($projectRoot.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'tailor.php');
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
            'feature_flag' => true,
            'icons' => [
                'mappings' => [
                    'exclamation-triangle' => null,
                    'loading' => null,
                    'plus' => null,
                ],
            ],
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

        expect($writtenContents)->toContain("'bars-2' => null");
        expect($writtenContents)->not->toContain("'new' =>");
        expect($writtenContents)->not->toContain('NULL');

        expect($secondRun['new'])->toBe([
            'bars-2',
            'exclamation-triangle',
            'loading',
        ])->and($secondRun['config'])->toBe([
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
            'icons' => [
                'mappings' => [
                    'exclamation-triangle' => null,
                    'loading' => null,
                    'plus' => null,
                ],
            ],
        ])->and($writtenConfig)->toBe([
            'icons' => [
                'mappings' => [
                    'exclamation-triangle' => null,
                    'loading' => null,
                    'plus' => null,
                ],
            ],
        ]);

        expect($writtenContents)->not->toContain("'removed' =>");
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
            'icons' => [
                'mappings' => [
                    'exclamation-triangle' => null,
                    'loading' => null,
                    'plus' => 'circle-plus',
                ],
            ],
        ])->and($summary['new'])->toBe([
            'exclamation-triangle',
            'loading',
        ]);
    } finally {
        $filesystem->deleteDirectory($root);
    }
});

it('sanitizes slash-suffixed icon mappings from existing config', function (): void {
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
            'loading/' => 'loader-circle',
            'plus/' => 'circle-plus',
        ],
    ],
];
PHP);

        $summary = fluxIconMapSync('sync', $viewsRoot, $configPath, $filesystem);
        $writtenConfig = require $configPath;

        expect($summary['config'])->toBe([
            'icons' => [
                'mappings' => [
                    'exclamation-triangle' => null,
                    'loading' => 'loader-circle',
                    'plus' => 'circle-plus',
                ],
            ],
        ])->and($writtenConfig)->toBe([
            'icons' => [
                'mappings' => [
                    'exclamation-triangle' => null,
                    'loading' => 'loader-circle',
                    'plus' => 'circle-plus',
                ],
            ],
        ]);
    } finally {
        $filesystem->deleteDirectory($root);
    }
});
