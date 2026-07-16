<?php

use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Actions\MoveComponentViews;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/tailor-move-components-'.uniqid();
    $this->views = $this->tempDir.'/views';
    $this->routes = $this->tempDir.'/routes';
    $this->tests = $this->tempDir.'/tests';
    mkdir($this->views, 0755, true);
    mkdir($this->routes, 0755, true);
    mkdir($this->tests, 0755, true);
    $this->action = new MoveComponentViews(new Filesystem);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->tempDir);
});

function seedPage(string $viewsPath, string $relative, string $contents = 'x'): void
{
    $path = $viewsPath.'/pages/'.$relative;
    @mkdir(dirname($path), 0755, true);
    file_put_contents($path, $contents);
}

function seedRoute(string $routesPath, string $name, string $contents): void
{
    file_put_contents($routesPath.'/'.$name, $contents);
}

it('moves a non-routed page component into components, preserving the marker and subpath', function () {
    seedPage($this->views, 'settings/⚡delete-user-modal.blade.php');

    $failed = $this->action->execute($this->views, $this->routes, $this->tests);

    expect($failed)->toBe([])
        ->and(file_exists($this->views.'/pages/settings/⚡delete-user-modal.blade.php'))->toBeFalse()
        ->and(file_exists($this->views.'/components/settings/⚡delete-user-modal.blade.php'))->toBeTrue();
});

it('moves a nested component and prunes the now-empty directory', function () {
    seedPage($this->views, 'settings/two-factor/⚡recovery-codes.blade.php');

    $this->action->execute($this->views, $this->routes, $this->tests);

    expect(file_exists($this->views.'/components/settings/two-factor/⚡recovery-codes.blade.php'))->toBeTrue()
        ->and(is_dir($this->views.'/pages/settings/two-factor'))->toBeFalse();
});

it('moves an anonymous blade component that has no marker', function () {
    seedPage($this->views, 'settings/layout.blade.php');

    $this->action->execute($this->views, $this->routes, $this->tests);

    expect(file_exists($this->views.'/pages/settings/layout.blade.php'))->toBeFalse()
        ->and(file_exists($this->views.'/components/settings/layout.blade.php'))->toBeTrue();
});

it('leaves the excluded auth folder in place even though it is not routed', function () {
    seedPage($this->views, 'auth/login.blade.php');

    $this->action->execute($this->views, $this->routes, $this->tests);

    expect(file_exists($this->views.'/pages/auth/login.blade.php'))->toBeTrue()
        ->and(file_exists($this->views.'/components/auth/login.blade.php'))->toBeFalse();
});

it('leaves a directly routed component in place', function () {
    seedPage($this->views, 'settings/⚡profile.blade.php');
    seedRoute($this->routes, 'settings.php', "<?php\nRoute::livewire('settings/profile', 'pages::settings.profile');");

    $this->action->execute($this->views, $this->routes, $this->tests);

    expect(file_exists($this->views.'/pages/settings/⚡profile.blade.php'))->toBeTrue()
        ->and(file_exists($this->views.'/components/settings/⚡profile.blade.php'))->toBeFalse();
});

it('rewrites every reference form for moved components without touching kept ones', function () {
    seedPage($this->views, 'settings/⚡profile.blade.php', <<<'BLADE'
    <x-pages::settings.layout>
        <livewire:pages::settings.delete-user-form />
    </x-pages::settings.layout>
    BLADE);
    seedPage($this->views, 'settings/⚡delete-user-form.blade.php');
    seedPage($this->views, 'settings/layout.blade.php');
    seedPage($this->views, 'auth/login.blade.php', "view('pages::auth.login')");
    seedRoute($this->routes, 'settings.php', "<?php\nRoute::livewire('settings/profile', 'pages::settings.profile');");
    file_put_contents($this->tests.'/ProfileTest.php', "<?php\nLivewire::test('pages::settings.delete-user-form');");

    $this->action->execute($this->views, $this->routes, $this->tests);

    $profile = file_get_contents($this->views.'/pages/settings/⚡profile.blade.php');
    $test = file_get_contents($this->tests.'/ProfileTest.php');
    $auth = file_get_contents($this->views.'/pages/auth/login.blade.php');

    expect($profile)
        ->toContain('<x-settings.layout>')
        ->toContain('</x-settings.layout>')
        ->toContain('<livewire:settings.delete-user-form />')
        ->not->toContain('pages::')
        ->and($test)->toContain("Livewire::test('settings.delete-user-form')")
        ->and($auth)->toContain("view('pages::auth.login')");
});

it('does not clobber an existing target and reports it as failed', function () {
    seedPage($this->views, 'settings/layout.blade.php', 'new');
    $existing = $this->views.'/components/settings/layout.blade.php';
    @mkdir(dirname($existing), 0755, true);
    file_put_contents($existing, 'keep');

    $failed = $this->action->execute($this->views, $this->routes, $this->tests);

    expect($failed)->toBe(['pages::settings.layout'])
        ->and(file_get_contents($existing))->toBe('keep')
        ->and(file_exists($this->views.'/pages/settings/layout.blade.php'))->toBeTrue();
});

it('is idempotent across repeated runs', function () {
    seedPage($this->views, 'settings/⚡delete-user-modal.blade.php');

    $this->action->execute($this->views, $this->routes, $this->tests);
    $failed = $this->action->execute($this->views, $this->routes, $this->tests);

    expect($failed)->toBe([])
        ->and(file_exists($this->views.'/components/settings/⚡delete-user-modal.blade.php'))->toBeTrue();
});

it('does nothing when there is no pages folder', function () {
    $failed = $this->action->execute($this->views, $this->routes, $this->tests);

    expect($failed)->toBe([])
        ->and(is_dir($this->views.'/components'))->toBeFalse();
});
