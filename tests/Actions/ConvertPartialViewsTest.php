<?php

use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Actions\ConvertPartialViews;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/tailor-convert-partials-'.uniqid();
    $this->views = $this->tempDir.'/views';
    $this->tests = $this->tempDir.'/tests';
    mkdir($this->views, 0755, true);
    mkdir($this->tests, 0755, true);
    $this->props = ['head' => ['title']];
    $this->action = new ConvertPartialViews(new Filesystem);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->tempDir);
});

function seedPartial(string $viewsPath, string $relative, string $contents = 'x'): void
{
    $path = $viewsPath.'/partials/'.$relative;
    @mkdir(dirname($path), 0755, true);
    file_put_contents($path, $contents);
}

function seedCaller(string $path, string $relative, string $contents): void
{
    $file = $path.'/'.$relative;
    @mkdir(dirname($file), 0755, true);
    file_put_contents($file, $contents);
}

it('moves a partial into the root of components', function () {
    seedPartial($this->views, 'settings-heading.blade.php', 'heading');

    $failed = $this->action->execute($this->views, $this->tests, $this->props);

    expect($failed)->toBe([])
        ->and(file_exists($this->views.'/partials/settings-heading.blade.php'))->toBeFalse()
        ->and(file_get_contents($this->views.'/components/settings-heading.blade.php'))->toBe('heading');
});

it('rewrites a dataless include to the component tag', function () {
    seedPartial($this->views, 'settings-heading.blade.php');
    seedCaller($this->views, 'pages/settings/profile.blade.php', "<section>\n    @include('partials.settings-heading')\n</section>");

    $this->action->execute($this->views, $this->tests, $this->props);

    expect(file_get_contents($this->views.'/pages/settings/profile.blade.php'))
        ->toBe("<section>\n    <x-settings-heading />\n</section>");
});

it('declares the configured props on the partial and passes them from the caller', function () {
    seedPartial($this->views, 'head.blade.php', '<title>{{ $title }}</title>');
    seedCaller($this->views, 'layouts/app/sidebar.blade.php', "<head>\n        @include('partials.head')\n    </head>");

    $this->action->execute($this->views, $this->tests, $this->props);

    expect(file_get_contents($this->views.'/components/head.blade.php'))
        ->toBe("@props(['title' => null])\n\n<title>{{ \$title }}</title>")
        ->and(file_get_contents($this->views.'/layouts/app/sidebar.blade.php'))
        ->toBe("<head>\n        <x-head :title=\"\$title ?? null\" />\n    </head>");
});

it('converts a partial with no configured props to a tag with no attributes', function () {
    seedPartial($this->views, 'settings-heading.blade.php', 'heading');
    seedCaller($this->views, 'pages/settings/profile.blade.php', "@include('partials.settings-heading')");

    $this->action->execute($this->views, $this->tests, $this->props);

    expect(file_get_contents($this->views.'/components/settings-heading.blade.php'))->toBe('heading')
        ->and(file_get_contents($this->views.'/pages/settings/profile.blade.php'))->toBe('<x-settings-heading />');
});

it('rewrites an include in either quote style', function () {
    seedPartial($this->views, 'settings-heading.blade.php');
    seedCaller($this->views, 'pages/settings/profile.blade.php', '@include("partials.settings-heading")');

    $this->action->execute($this->views, $this->tests, $this->props);

    expect(file_get_contents($this->views.'/pages/settings/profile.blade.php'))->toBe('<x-settings-heading />');
});

it('rewrites every caller of the same partial', function () {
    seedPartial($this->views, 'head.blade.php');
    seedCaller($this->views, 'layouts/app/sidebar.blade.php', "@include('partials.head')");
    seedCaller($this->views, 'layouts/auth/card.blade.php', "@include('partials.head')");

    $this->action->execute($this->views, $this->tests, $this->props);

    expect(file_get_contents($this->views.'/layouts/app/sidebar.blade.php'))->toBe('<x-head :title="$title ?? null" />')
        ->and(file_get_contents($this->views.'/layouts/auth/card.blade.php'))->toBe('<x-head :title="$title ?? null" />');
});

it('rewrites an include in the tests', function () {
    seedPartial($this->views, 'settings-heading.blade.php');
    seedCaller($this->tests, 'Feature/SettingsTest.php', "\$this->blade(\"@include('partials.settings-heading')\");");

    $this->action->execute($this->views, $this->tests, $this->props);

    expect(file_get_contents($this->tests.'/Feature/SettingsTest.php'))
        ->toBe('$this->blade("<x-settings-heading />");');
});

it('rewrites an include of one partial by another', function () {
    seedPartial($this->views, 'head.blade.php', "@include('partials.meta')");
    seedPartial($this->views, 'meta.blade.php', '<meta />');

    $this->action->execute($this->views, $this->tests, $this->props);

    expect(file_get_contents($this->views.'/components/head.blade.php'))
        ->toBe("@props(['title' => null])\n\n<x-meta />");
});

it('preserves the subpath of a nested partial and rewrites it to a dotted tag', function () {
    seedPartial($this->views, 'nested/meta.blade.php', '<meta />');
    seedCaller($this->views, 'layouts/app.blade.php', "@include('partials.nested.meta')");

    $failed = $this->action->execute($this->views, $this->tests, $this->props);

    expect($failed)->toBe([])
        ->and(file_get_contents($this->views.'/components/nested/meta.blade.php'))->toBe('<meta />')
        ->and(file_get_contents($this->views.'/layouts/app.blade.php'))->toBe('<x-nested.meta />');
});

it('tolerates whitespace inside the include parentheses', function () {
    seedPartial($this->views, 'settings-heading.blade.php');
    seedCaller($this->views, 'layouts/app.blade.php', "@include( 'partials.settings-heading' )");

    $this->action->execute($this->views, $this->tests, $this->props);

    expect(file_get_contents($this->views.'/layouts/app.blade.php'))->toBe('<x-settings-heading />');
});

it('leaves an include that passes data alone and reports it', function () {
    seedPartial($this->views, 'head.blade.php', 'head');
    seedCaller($this->views, 'layouts/app.blade.php', "@include('partials.head', ['title' => 'Dashboard'])");

    $failed = $this->action->execute($this->views, $this->tests, $this->props);

    expect($failed)->toBe(['partials.head'])
        ->and(file_get_contents($this->views.'/partials/head.blade.php'))->toBe('head')
        ->and(file_exists($this->views.'/components/head.blade.php'))->toBeFalse()
        ->and(file_get_contents($this->views.'/layouts/app.blade.php'))
        ->toBe("@include('partials.head', ['title' => 'Dashboard'])");
});

it('leaves a partial referenced by a conditional include alone and reports it', function () {
    seedPartial($this->views, 'head.blade.php');
    seedCaller($this->views, 'layouts/app.blade.php', "@includeWhen(\$debug, 'partials.head')");

    $failed = $this->action->execute($this->views, $this->tests, $this->props);

    expect($failed)->toBe(['partials.head'])
        ->and(file_exists($this->views.'/partials/head.blade.php'))->toBeTrue()
        ->and(file_get_contents($this->views.'/layouts/app.blade.php'))->toBe("@includeWhen(\$debug, 'partials.head')");
});

it('leaves a partial resolved through the view helper alone and reports it', function () {
    seedPartial($this->views, 'head.blade.php');
    seedCaller($this->tests, 'Feature/HeadTest.php', "view('partials.head');");

    $failed = $this->action->execute($this->views, $this->tests, $this->props);

    expect($failed)->toBe(['partials.head'])
        ->and(file_exists($this->views.'/partials/head.blade.php'))->toBeTrue()
        ->and(file_get_contents($this->tests.'/Feature/HeadTest.php'))->toBe("view('partials.head');");
});

it('converts the partials it can when a sibling is unconvertible', function () {
    seedPartial($this->views, 'head.blade.php');
    seedPartial($this->views, 'settings-heading.blade.php');
    seedCaller($this->views, 'layouts/app.blade.php', "@include('partials.head', ['title' => 'Dashboard'])\n@include('partials.settings-heading')");

    $failed = $this->action->execute($this->views, $this->tests, $this->props);

    expect($failed)->toBe(['partials.head'])
        ->and(file_exists($this->views.'/partials/head.blade.php'))->toBeTrue()
        ->and(file_exists($this->views.'/components/settings-heading.blade.php'))->toBeTrue()
        ->and(file_get_contents($this->views.'/layouts/app.blade.php'))
        ->toBe("@include('partials.head', ['title' => 'Dashboard'])\n<x-settings-heading />");
});

it('converts a partial nothing references', function () {
    seedPartial($this->views, 'settings-heading.blade.php', 'heading');

    $failed = $this->action->execute($this->views, $this->tests, $this->props);

    expect($failed)->toBe([])
        ->and(file_get_contents($this->views.'/components/settings-heading.blade.php'))->toBe('heading');
});

it('does not match a shorter partial name inside a longer sibling', function () {
    seedPartial($this->views, 'head.blade.php');
    seedPartial($this->views, 'head-scripts.blade.php');
    seedCaller($this->views, 'layouts/app.blade.php', "@include('partials.head-scripts')");

    $failed = $this->action->execute($this->views, $this->tests, $this->props);

    expect($failed)->toBe([])
        ->and(file_get_contents($this->views.'/layouts/app.blade.php'))->toBe('<x-head-scripts />');
});

it('reports a partial whose target already exists rather than clobbering it', function () {
    seedPartial($this->views, 'head.blade.php', 'partial');
    $componentPath = $this->views.'/components/head.blade.php';
    @mkdir(dirname($componentPath), 0755, true);
    file_put_contents($componentPath, 'existing');

    $failed = $this->action->execute($this->views, $this->tests, $this->props);

    expect($failed)->toBe(['partials.head'])
        ->and(file_get_contents($componentPath))->toBe('existing')
        ->and(file_get_contents($this->views.'/partials/head.blade.php'))->toBe('partial');
});

it('removes the partials folder once it is empty', function () {
    seedPartial($this->views, 'nested/meta.blade.php');
    seedPartial($this->views, 'head.blade.php');

    $this->action->execute($this->views, $this->tests, $this->props);

    expect(is_dir($this->views.'/partials'))->toBeFalse();
});

it('keeps the partials folder when it still holds an unconvertible partial', function () {
    seedPartial($this->views, 'head.blade.php');
    seedPartial($this->views, 'settings-heading.blade.php');
    seedCaller($this->views, 'layouts/app.blade.php', "@includeWhen(\$debug, 'partials.head')");

    $this->action->execute($this->views, $this->tests, $this->props);

    expect(is_dir($this->views.'/partials'))->toBeTrue()
        ->and(file_exists($this->views.'/partials/head.blade.php'))->toBeTrue();
});

it('ignores a non-blade file in the partials folder', function () {
    seedPartial($this->views, 'notes.md', 'notes');

    $failed = $this->action->execute($this->views, $this->tests, $this->props);

    expect($failed)->toBe([])
        ->and(file_get_contents($this->views.'/partials/notes.md'))->toBe('notes');
});

it('does nothing when there is no partials folder', function () {
    expect($this->action->execute($this->views, $this->tests, $this->props))->toBe([]);
});

it('handles a missing tests folder', function () {
    (new Filesystem)->deleteDirectory($this->tests);
    seedPartial($this->views, 'settings-heading.blade.php');
    seedCaller($this->views, 'layouts/app.blade.php', "@include('partials.settings-heading')");

    $failed = $this->action->execute($this->views, $this->tests, $this->props);

    expect($failed)->toBe([])
        ->and(file_get_contents($this->views.'/layouts/app.blade.php'))->toBe('<x-settings-heading />');
});

it('is idempotent', function () {
    seedPartial($this->views, 'head.blade.php', '<title>{{ $title }}</title>');
    seedCaller($this->views, 'layouts/app.blade.php', "@include('partials.head')");

    $this->action->execute($this->views, $this->tests, $this->props);
    $failed = $this->action->execute($this->views, $this->tests, $this->props);

    expect($failed)->toBe([])
        ->and(file_get_contents($this->views.'/components/head.blade.php'))
        ->toBe("@props(['title' => null])\n\n<title>{{ \$title }}</title>")
        ->and(file_get_contents($this->views.'/layouts/app.blade.php'))->toBe('<x-head :title="$title ?? null" />');
});
