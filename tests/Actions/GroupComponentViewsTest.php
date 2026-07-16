<?php

use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Actions\GroupComponentViews;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/tailor-group-components-'.uniqid();
    $this->views = $this->tempDir.'/views';
    $this->tests = $this->tempDir.'/tests';
    mkdir($this->views, 0755, true);
    mkdir($this->tests, 0755, true);
    $this->groups = [
        'branding' => ['app-logo', 'app-logo-icon'],
        'auth' => ['auth-header', 'auth-session-status'],
    ];
    $this->action = new GroupComponentViews(new Filesystem);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->tempDir);
});

function seedComponent(string $viewsPath, string $relative, string $contents = 'x'): void
{
    $path = $viewsPath.'/components/'.$relative;
    @mkdir(dirname($path), 0755, true);
    file_put_contents($path, $contents);
}

function seedView(string $viewsPath, string $relative, string $contents): void
{
    $path = $viewsPath.'/'.$relative;
    @mkdir(dirname($path), 0755, true);
    file_put_contents($path, $contents);
}

it('moves a root component into its configured folder', function () {
    seedComponent($this->views, 'app-logo.blade.php');

    $ungrouped = $this->action->execute($this->views, $this->tests, $this->groups);

    expect($ungrouped)->toBe([])
        ->and(file_exists($this->views.'/components/app-logo.blade.php'))->toBeFalse()
        ->and(file_exists($this->views.'/components/branding/app-logo.blade.php'))->toBeTrue();
});

it('leaves a component that is already in a subfolder alone', function () {
    seedComponent($this->views, 'settings/layout.blade.php');

    $ungrouped = $this->action->execute($this->views, $this->tests, $this->groups);

    expect($ungrouped)->toBe([])
        ->and(file_exists($this->views.'/components/settings/layout.blade.php'))->toBeTrue();
});

it('leaves an ungrouped root component in place and reports it', function () {
    seedComponent($this->views, 'team-switcher.blade.php');

    $ungrouped = $this->action->execute($this->views, $this->tests, $this->groups);

    expect($ungrouped)->toBe(['team-switcher'])
        ->and(file_exists($this->views.'/components/team-switcher.blade.php'))->toBeTrue();
});

it('reports a component whose target already exists rather than clobbering it', function () {
    seedComponent($this->views, 'app-logo.blade.php', 'root');
    seedComponent($this->views, 'branding/app-logo.blade.php', 'existing');

    $ungrouped = $this->action->execute($this->views, $this->tests, $this->groups);

    expect($ungrouped)->toBe(['app-logo'])
        ->and(file_get_contents($this->views.'/components/branding/app-logo.blade.php'))->toBe('existing')
        ->and(file_get_contents($this->views.'/components/app-logo.blade.php'))->toBe('root');
});

it('rewrites the tag of a moved component in the views', function () {
    seedComponent($this->views, 'app-logo.blade.php');
    seedView($this->views, 'layouts/app/header.blade.php', '<x-app-logo href="/" wire:navigate />');

    $this->action->execute($this->views, $this->tests, $this->groups);

    expect(file_get_contents($this->views.'/layouts/app/header.blade.php'))
        ->toBe('<x-branding.app-logo href="/" wire:navigate />');
});

it('rewrites an opening and closing tag pair', function () {
    seedComponent($this->views, 'auth-header.blade.php');
    seedView($this->views, 'pages/auth/login.blade.php', '<x-auth-header>Log in</x-auth-header>');

    $this->action->execute($this->views, $this->tests, $this->groups);

    expect(file_get_contents($this->views.'/pages/auth/login.blade.php'))
        ->toBe('<x-auth.auth-header>Log in</x-auth.auth-header>');
});

it('does not rewrite a shorter name inside a longer sibling tag', function () {
    seedComponent($this->views, 'app-logo.blade.php');
    seedComponent($this->views, 'app-logo-icon.blade.php');
    seedView($this->views, 'layouts/auth/simple.blade.php', '<x-app-logo-icon class="size-9" />');

    $this->action->execute($this->views, $this->tests, $this->groups);

    expect(file_get_contents($this->views.'/layouts/auth/simple.blade.php'))
        ->toBe('<x-branding.app-logo-icon class="size-9" />');
});

it('leaves an alpine x- attribute that shares a component name untouched', function () {
    seedComponent($this->views, 'app-logo.blade.php');
    seedView($this->views, 'layouts/app/header.blade.php', '<div x-app-logo="true"></div>');

    $this->action->execute($this->views, $this->tests, $this->groups);

    expect(file_get_contents($this->views.'/layouts/app/header.blade.php'))
        ->toBe('<div x-app-logo="true"></div>');
});

it('rewrites references in the tests', function () {
    seedComponent($this->views, 'auth-header.blade.php');
    file_put_contents($this->tests.'/AuthHeaderTest.php', "<?php\n\$html = '<x-auth-header />';");

    $this->action->execute($this->views, $this->tests, $this->groups);

    expect(file_get_contents($this->tests.'/AuthHeaderTest.php'))
        ->toBe("<?php\n\$html = '<x-auth.auth-header />';");
});

it('does not rewrite the reference of a component it could not group', function () {
    seedComponent($this->views, 'team-switcher.blade.php');
    seedView($this->views, 'layouts/app/sidebar.blade.php', '<x-team-switcher />');

    $this->action->execute($this->views, $this->tests, $this->groups);

    expect(file_get_contents($this->views.'/layouts/app/sidebar.blade.php'))
        ->toBe('<x-team-switcher />');
});

it('is safe to run twice', function () {
    seedComponent($this->views, 'app-logo.blade.php');
    seedView($this->views, 'layouts/app/header.blade.php', '<x-app-logo />');

    $this->action->execute($this->views, $this->tests, $this->groups);
    $ungrouped = $this->action->execute($this->views, $this->tests, $this->groups);

    expect($ungrouped)->toBe([])
        ->and(file_exists($this->views.'/components/branding/app-logo.blade.php'))->toBeTrue()
        ->and(file_get_contents($this->views.'/layouts/app/header.blade.php'))->toBe('<x-branding.app-logo />');
});

it('does nothing when there is no components folder', function () {
    expect($this->action->execute($this->views, $this->tests, $this->groups))->toBe([]);
});

it('ignores a non-blade file at the root of components', function () {
    seedComponent($this->views, 'README.md');

    expect($this->action->execute($this->views, $this->tests, $this->groups))->toBe([]);
});
