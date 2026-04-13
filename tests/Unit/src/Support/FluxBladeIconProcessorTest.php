<?php

use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Support\FluxBladeIconProcessor;

it('extracts static flux icon tags', function (): void {
    $result = (new FluxBladeIconProcessor(new Filesystem))->extractIconsFromBlade(<<<'BLADE'
<flux:icon.chevron-right />
<flux:icon.loading/>
<flux:icon.qr-code class="size-4" />
BLADE);

    expect($result['icons'])->toBe([
        'chevron-right',
        'loading',
        'qr-code',
    ])->and($result['warnings'])->toBe([]);
});

it('extracts icons from dynamic flux icon attributes', function (): void {
    $result = (new FluxBladeIconProcessor(new Filesystem))->extractIconsFromBlade(<<<'BLADE'
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
    $result = (new FluxBladeIconProcessor(new Filesystem))->extractIconsFromBlade(<<<'BLADE'
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
    $result = (new FluxBladeIconProcessor(new Filesystem))->extractIconsFromBlade(<<<'BLADE'
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
    $result = (new FluxBladeIconProcessor(new Filesystem))->extractIconsFromBlade(<<<'BLADE'
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
