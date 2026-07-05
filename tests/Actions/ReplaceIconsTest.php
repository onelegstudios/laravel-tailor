<?php

use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Actions\ReplaceIcons;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/tailor-icons-'.uniqid();
    mkdir($this->tempDir, 0755, true);
    $this->action = new ReplaceIcons(new Filesystem);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->tempDir);
});

it('replaces icon attribute values with their lucide equivalents', function () {
    file_put_contents($this->tempDir.'/sidebar.blade.php', <<<'BLADE'
        <flux:sidebar.item icon="home" wire:navigate>
            Dashboard
        </flux:sidebar.item>
        <flux:sidebar.toggle icon="bars-2" />
        BLADE);

    $this->action->execute($this->tempDir, ['home' => 'house', 'bars-2' => 'menu']);

    $result = file_get_contents($this->tempDir.'/sidebar.blade.php');
    expect($result)
        ->toContain('icon="house"')
        ->toContain('icon="menu"')
        ->not->toContain('icon="home"')
        ->not->toContain('icon="bars-2"');
});

it('replaces icon:trailing and icon-trailing attributes', function () {
    file_put_contents($this->tempDir.'/nav.blade.php', <<<'BLADE'
        <flux:profile icon:trailing="chevron-down" />
        <flux:profile icon-trailing="chevron-down" />
        <flux:button icon:leading="plus" />
        BLADE);

    $this->action->execute($this->tempDir, ['chevron-down' => 'chevron-down-lucide', 'plus' => 'plus-lucide']);

    $result = file_get_contents($this->tempDir.'/nav.blade.php');
    expect($result)
        ->toContain('icon:trailing="chevron-down-lucide"')
        ->toContain('icon-trailing="chevron-down-lucide"')
        ->toContain('icon:leading="plus-lucide"')
        ->not->toContain('icon:trailing="chevron-down"')
        ->not->toContain('icon-trailing="chevron-down"');
});

it('replaces flux:icon. component syntax', function () {
    file_put_contents($this->tempDir.'/page.blade.php', <<<'BLADE'
        <flux:icon.home class="size-5" />
        <flux:icon.lock-closed variant="outline"/>
        BLADE);

    $this->action->execute($this->tempDir, ['home' => 'house', 'lock-closed' => 'lock']);

    $result = file_get_contents($this->tempDir.'/page.blade.php');
    expect($result)
        ->toContain('<flux:icon.house ')
        ->toContain('<flux:icon.lock ')
        ->not->toContain('<flux:icon.home')
        ->not->toContain('<flux:icon.lock-closed');
});

it('does not replace icon:variant attributes', function () {
    file_put_contents($this->tempDir.'/button.blade.php', <<<'BLADE'
        <flux:button icon="eye" icon:variant="outline" />
        BLADE);

    $this->action->execute($this->tempDir, ['eye' => 'eye-lucide']);

    $result = file_get_contents($this->tempDir.'/button.blade.php');
    expect($result)
        ->toContain('icon="eye-lucide"')
        ->toContain('icon:variant="outline"');
});

it('skips icons that map to an empty string', function () {
    file_put_contents($this->tempDir.'/page.blade.php', <<<'BLADE'
        <flux:button icon="trash" />
        BLADE);

    $this->action->execute($this->tempDir, ['trash' => '']);

    $result = file_get_contents($this->tempDir.'/page.blade.php');
    expect($result)->toContain('icon="trash"');
});

it('skips icons that map to themselves', function () {
    $original = '<flux:button icon="chevron-down" />';
    file_put_contents($this->tempDir.'/page.blade.php', $original);

    $this->action->execute($this->tempDir, ['chevron-down' => 'chevron-down']);

    expect(file_get_contents($this->tempDir.'/page.blade.php'))->toBe($original);
});

it('does not touch non-blade files', function () {
    file_put_contents($this->tempDir.'/app.js', 'icon="home"');

    $this->action->execute($this->tempDir, ['home' => 'house']);

    expect(file_get_contents($this->tempDir.'/app.js'))->toBe('icon="home"');
});

it('recurses into subdirectories', function () {
    $sub = $this->tempDir.'/layouts';
    mkdir($sub);
    file_put_contents($sub.'/app.blade.php', '<flux:button icon="home" />');

    $this->action->execute($this->tempDir, ['home' => 'house']);

    expect(file_get_contents($sub.'/app.blade.php'))->toContain('icon="house"');
});
