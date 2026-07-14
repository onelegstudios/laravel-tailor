<?php

use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Actions\MoveAuthViews;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/tailor-move-auth-'.uniqid();
    $this->views = $this->tempDir.'/views';
    $this->provider = $this->tempDir.'/FortifyServiceProvider.php';
    mkdir($this->views, 0755, true);
    $this->action = new MoveAuthViews(new Filesystem);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->tempDir);
});

/**
 * @param  array<int, string>  $names
 */
function seedAuthFolder(string $viewsPath, string $namespaceFolder, array $names): void
{
    $dir = $viewsPath.'/'.$namespaceFolder.'/auth';
    mkdir($dir, 0755, true);

    foreach ($names as $name) {
        file_put_contents($dir.'/'.$name.'.blade.php', 'x');
    }
}

function seedProvider(string $path, string $prefix): void
{
    file_put_contents($path, <<<PHP
    <?php

    Fortify::loginView(fn () => view('{$prefix}login'));
    Fortify::registerView(fn () => view('{$prefix}register'));
    PHP);
}

it('moves the pages auth folder and repoints the provider', function () {
    seedAuthFolder($this->views, 'pages', ['login', 'register']);
    seedProvider($this->provider, 'pages::auth.');

    $moved = $this->action->execute($this->views, $this->provider);

    expect($moved)->toBeTrue()
        ->and(is_dir($this->views.'/pages/auth'))->toBeFalse()
        ->and(file_exists($this->views.'/auth/login.blade.php'))->toBeTrue()
        ->and(file_exists($this->views.'/auth/register.blade.php'))->toBeTrue()
        ->and(file_get_contents($this->provider))
        ->toContain("view('auth.login')")
        ->not->toContain('pages::auth.');
});

it('moves the livewire auth folder and repoints the provider', function () {
    seedAuthFolder($this->views, 'livewire', ['login', 'register']);
    seedProvider($this->provider, 'livewire.auth.');

    $moved = $this->action->execute($this->views, $this->provider);

    expect($moved)->toBeTrue()
        ->and(is_dir($this->views.'/livewire/auth'))->toBeFalse()
        ->and(file_exists($this->views.'/auth/login.blade.php'))->toBeTrue()
        ->and(file_get_contents($this->provider))
        ->toContain("view('auth.login')")
        ->not->toContain('livewire.auth.');
});

it('does nothing when no auth folder exists', function () {
    seedProvider($this->provider, 'pages::auth.');

    $moved = $this->action->execute($this->views, $this->provider);

    expect($moved)->toBeFalse()
        ->and(is_dir($this->views.'/auth'))->toBeFalse()
        ->and(file_get_contents($this->provider))->toContain('pages::auth.');
});

it('does not clobber an existing auth target', function () {
    seedAuthFolder($this->views, 'pages', ['login']);
    mkdir($this->views.'/auth', 0755, true);
    file_put_contents($this->views.'/auth/existing.blade.php', 'keep');
    seedProvider($this->provider, 'pages::auth.');

    $moved = $this->action->execute($this->views, $this->provider);

    expect($moved)->toBeFalse()
        ->and(file_exists($this->views.'/auth/existing.blade.php'))->toBeTrue()
        ->and(is_dir($this->views.'/pages/auth'))->toBeTrue()
        ->and(file_get_contents($this->provider))->toContain('pages::auth.');
});

it('still moves the folder when the provider file is missing', function () {
    seedAuthFolder($this->views, 'pages', ['login']);

    $moved = $this->action->execute($this->views, $this->tempDir.'/missing.php');

    expect($moved)->toBeTrue()
        ->and(file_exists($this->views.'/auth/login.blade.php'))->toBeTrue();
});
