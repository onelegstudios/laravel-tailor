<?php

use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Support\FluxBladeIconProcessor;
use Onelegstudios\Tailor\Support\UseLucideIcons;

it('publishes mapped lucide icons and rewrites supported flux usages', function (): void {
    $filesystem = new Filesystem;
    $action = new UseLucideIcons($filesystem, new FluxBladeIconProcessor($filesystem));
    $root = sys_get_temp_dir().'/use-lucide-icons-'.bin2hex(random_bytes(8));
    $viewsRoot = $root.'/views';
    $iconRoot = $viewsRoot.'/flux/icon';
    $viewPath = $viewsRoot.'/dashboard.blade.php';
    $ignoredIconPath = $iconRoot.'/ignored.blade.php';
    $notesPath = $viewsRoot.'/notes.txt';

    $filesystem->ensureDirectoryExists($iconRoot);

    try {
        $filesystem->put($viewPath, <<<'BLADE'
<flux:icon.plus class="size-4" />
<flux:icon name="users" />
<flux:icon icon="chevrons-up-down" />
<flux:button icon="plus" />
<flux:button icon:leading="chevron-down">Menu</flux:button>
<flux:button icon:trailing="chevron-down">More</flux:button>
<flux:profile icon-leading="plus" icon-trailing="chevron-down" />
<flux:button :icon="'plus'" />
<flux:button :icon="$canView ? 'eye' : 'pencil'" />
<flux:icon :name="'users'" />
<flux:icon.loading />
<flux:icon.exclamation-triangle />
<flux:button :icon="$iconName" />
BLADE);

        $filesystem->put($ignoredIconPath, '<flux:button icon="plus" />');
        $filesystem->put($notesPath, '<flux:button icon="plus" />');

        $publishedBatches = [];

        $summary = $action->handle(
            $viewsRoot,
            $iconRoot,
            [
                'icons' => [
                    'mappings' => [
                        'plus' => 'circle-plus',
                        'users' => 'users-round',
                        'chevrons-up-down' => 'arrow-up-down',
                        'chevron-down' => 'chevrons-down-up',
                        'eye' => 'scan-eye',
                        'pencil' => 'square-pen',
                        'loading' => 'loader-circle',
                        'exclamation-triangle' => 'triangle-alert',
                        'bars-2' => 'menu',
                    ],
                ],
            ],
            function (array $icons) use ($filesystem, $iconRoot, &$publishedBatches): int {
                $publishedBatches[] = $icons;

                foreach ($icons as $icon) {
                    $filesystem->put($iconRoot.'/'.$icon.'.blade.php', lucideIconBladeStub($icon));
                }

                return 0;
            },
        );

        expect($publishedBatches)->toHaveCount(1)
            ->and($publishedBatches[0])->toBe([
                'arrow-up-down',
                'chevrons-down-up',
                'circle-plus',
                'loader-circle',
                'scan-eye',
                'square-pen',
                'triangle-alert',
                'users-round',
            ])
            ->and($summary['iconsPublished'])->toBe($publishedBatches[0])
            ->and($summary['filesUpdated'])->toBe([$viewPath])
            ->and($summary['warnings'])->toHaveCount(1);

        expect($summary['warnings'][0])
            ->toContain($viewPath)
            ->toContain(':icon')
            ->toContain('<flux:button>');

        $rewrittenBlade = $filesystem->get($viewPath);

        expect($rewrittenBlade)
            ->toContain('<flux:icon.circle-plus class="size-4" />')
            ->toContain('<flux:icon name="users-round" />')
            ->toContain('<flux:icon icon="arrow-up-down" />')
            ->toContain('<flux:button icon="circle-plus" />')
            ->toContain('icon:leading="chevrons-down-up"')
            ->toContain('icon:trailing="chevrons-down-up"')
            ->toContain('icon-leading="circle-plus"')
            ->toContain('icon-trailing="chevrons-down-up"')
            ->toContain(':icon="\'circle-plus\'"')
            ->toContain(':icon="$canView ? \'scan-eye\' : \'square-pen\'"')
            ->toContain(':name="\'users-round\'"')
            ->toContain('<flux:icon.loader-circle />')
            ->toContain('<flux:icon.triangle-alert />')
            ->toContain(':icon="$iconName"');

        expect($filesystem->get($ignoredIconPath))->toBe('<flux:button icon="plus" />')
            ->and($filesystem->get($notesPath))->toBe('<flux:button icon="plus" />')
            ->and($filesystem->get($iconRoot.'/loader-circle.blade.php'))->toContain('animate-spin')
            ->and($filesystem->get($iconRoot.'/loading.blade.php'))->toContain('animate-spin')
            ->and($filesystem->get($iconRoot.'/exclamation-triangle.blade.php'))
            ->toBe($filesystem->get($iconRoot.'/triangle-alert.blade.php'));
    } finally {
        $filesystem->deleteDirectory($root);
    }
});

it('aborts before rewriting views when a published icon file is missing', function (): void {
    $filesystem = new Filesystem;
    $action = new UseLucideIcons($filesystem, new FluxBladeIconProcessor($filesystem));
    $root = sys_get_temp_dir().'/use-lucide-icons-'.bin2hex(random_bytes(8));
    $viewsRoot = $root.'/views';
    $iconRoot = $viewsRoot.'/flux/icon';
    $viewPath = $viewsRoot.'/dashboard.blade.php';

    $filesystem->ensureDirectoryExists($iconRoot);

    try {
        $filesystem->put($viewPath, '<flux:button icon="plus" />');

        expect(fn () => $action->handle(
            $viewsRoot,
            $iconRoot,
            ['plus' => 'circle-plus'],
            fn (array $icons): int => 0,
        ))->toThrow(RuntimeException::class, 'Published Lucide icon files are missing: circle-plus');

        expect($filesystem->get($viewPath))->toBe('<flux:button icon="plus" />');
    } finally {
        $filesystem->deleteDirectory($root);
    }
});

it('publishes detected icons with same-name mappings', function (): void {
    $filesystem = new Filesystem;
    $action = new UseLucideIcons($filesystem, new FluxBladeIconProcessor($filesystem));
    $root = sys_get_temp_dir().'/use-lucide-icons-'.bin2hex(random_bytes(8));
    $viewsRoot = $root.'/views';
    $iconRoot = $viewsRoot.'/flux/icon';
    $viewPath = $viewsRoot.'/dashboard.blade.php';

    $filesystem->ensureDirectoryExists($iconRoot);

    try {
        $filesystem->put($viewPath, <<<'BLADE'
<flux:icon.layout-grid />
<flux:button :icon="'layout-grid'" />
BLADE);

        $publishedBatches = [];

        $summary = $action->handle(
            $viewsRoot,
            $iconRoot,
            [
                'icons' => [
                    'mappings' => [
                        'layout-grid' => 'layout-grid',
                    ],
                ],
            ],
            function (array $icons) use ($filesystem, $iconRoot, &$publishedBatches): int {
                $publishedBatches[] = $icons;

                foreach ($icons as $icon) {
                    $filesystem->put($iconRoot.'/'.$icon.'.blade.php', lucideIconBladeStub($icon));
                }

                return 0;
            },
        );

        expect($publishedBatches)->toBe([['layout-grid']])
            ->and($summary['iconsPublished'])->toBe(['layout-grid'])
            ->and($summary['filesUpdated'])->toBe([])
            ->and($summary['warnings'])->toBe([])
            ->and($filesystem->exists($iconRoot.'/layout-grid.blade.php'))->toBeTrue()
            ->and($filesystem->get($viewPath))->toBe(<<<'BLADE'
<flux:icon.layout-grid />
<flux:button :icon="'layout-grid'" />
BLADE);
    } finally {
        $filesystem->deleteDirectory($root);
    }
});

it('leaves partially mapped bound icon ternaries unchanged and warns', function (): void {
    $filesystem = new Filesystem;
    $action = new UseLucideIcons($filesystem, new FluxBladeIconProcessor($filesystem));
    $root = sys_get_temp_dir().'/use-lucide-icons-'.bin2hex(random_bytes(8));
    $viewsRoot = $root.'/views';
    $iconRoot = $viewsRoot.'/flux/icon';
    $viewPath = $viewsRoot.'/dashboard.blade.php';
    $blade = <<<'BLADE'
<flux:button :icon="$ok ? 'plus' : 'missing'" />
BLADE;

    $filesystem->ensureDirectoryExists($iconRoot);

    try {
        $filesystem->put($viewPath, $blade);

        $publishedBatches = [];

        $summary = $action->handle(
            $viewsRoot,
            $iconRoot,
            [
                'icons' => [
                    'mappings' => [
                        'plus' => 'circle-plus',
                    ],
                ],
            ],
            function (array $icons) use ($filesystem, $iconRoot, &$publishedBatches): int {
                $publishedBatches[] = $icons;

                foreach ($icons as $icon) {
                    $filesystem->put($iconRoot.'/'.$icon.'.blade.php', lucideIconBladeStub($icon));
                }

                return 0;
            },
        );

        expect($publishedBatches)->toBe([['circle-plus']])
            ->and($summary['iconsPublished'])->toBe(['circle-plus'])
            ->and($summary['filesUpdated'])->toBe([])
            ->and($summary['warnings'])->toHaveCount(1)
            ->and($summary['warnings'][0])->toContain($viewPath)
            ->and($summary['warnings'][0])->toContain(':icon')
            ->and($summary['warnings'][0])->toContain('<flux:button>')
            ->and($filesystem->get($viewPath))->toBe($blade)
            ->and($filesystem->exists($iconRoot.'/circle-plus.blade.php'))->toBeTrue()
            ->and($filesystem->exists($iconRoot.'/missing.blade.php'))->toBeFalse();
    } finally {
        $filesystem->deleteDirectory($root);
    }
});

it('aborts before writing legacy aliases when loading icons cannot be patched', function (): void {
    $filesystem = new Filesystem;
    $action = new UseLucideIcons($filesystem, new FluxBladeIconProcessor($filesystem));
    $root = sys_get_temp_dir().'/use-lucide-icons-'.bin2hex(random_bytes(8));
    $viewsRoot = $root.'/views';
    $iconRoot = $viewsRoot.'/flux/icon';
    $viewPath = $viewsRoot.'/dashboard.blade.php';
    $invalidLoadingStub = <<<'BLADE'
@php
$classes = Flux::classes('text-sm');
@endphp

<svg data-icon="loader-circle"></svg>
BLADE;

    $filesystem->ensureDirectoryExists($iconRoot);

    try {
        $filesystem->put($viewPath, '<flux:button icon="plus" />');

        expect(fn () => $action->handle(
            $viewsRoot,
            $iconRoot,
            [
                'icons' => [
                    'mappings' => [
                        'loading' => 'loader-circle',
                        'exclamation-triangle' => 'triangle-alert',
                    ],
                ],
            ],
            function (array $icons) use ($filesystem, $iconRoot, $invalidLoadingStub): int {
                foreach ($icons as $icon) {
                    $contents = $icon === 'loader-circle'
                        ? $invalidLoadingStub
                        : lucideIconBladeStub($icon);

                    $filesystem->put($iconRoot.'/'.$icon.'.blade.php', $contents);
                }

                return 0;
            },
        ))->toThrow(RuntimeException::class, 'Unable to add animate-spin');

        expect($filesystem->get($viewPath))->toBe('<flux:button icon="plus" />')
            ->and($filesystem->get($iconRoot.'/loader-circle.blade.php'))->toBe($invalidLoadingStub)
            ->and($filesystem->exists($iconRoot.'/loading.blade.php'))->toBeFalse()
            ->and($filesystem->exists($iconRoot.'/exclamation-triangle.blade.php'))->toBeFalse();
    } finally {
        $filesystem->deleteDirectory($root);
    }
});

function lucideIconBladeStub(string $icon): string
{
    return str_replace('[[ICON]]', $icon, <<<'BLADE'
@php
$classes = Flux::classes('shrink-0');
@endphp

<svg data-icon="[[ICON]]"></svg>
BLADE);
}
