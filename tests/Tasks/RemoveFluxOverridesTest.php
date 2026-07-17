<?php

use Illuminate\Console\OutputStyle;
use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Actions\RemoveFluxViews;
use Onelegstudios\Tailor\Tasks\RemoveFluxOverrides;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Bind a RemoveFluxViews reporting $removed as the views it deleted.
 *
 * @param  array<int, string>  $removed
 */
function fakeFluxRemoval(array $removed): void
{
    $removeFluxViews = Mockery::mock(RemoveFluxViews::class);
    $removeFluxViews->shouldReceive('execute')->once()->andReturn($removed);
    app()->instance(RemoveFluxViews::class, $removeFluxViews);
}

/**
 * Drop a file into the compiled view path, standing in for a view compiled while
 * the override was still on disk. Returns its path.
 */
function staleCompiledView(): string
{
    /** @var string $compiled */
    $compiled = config('view.compiled');

    (new Filesystem)->ensureDirectoryExists($compiled);

    $path = $compiled.'/tailor-stale-'.uniqid().'.php';
    file_put_contents($path, '<?php /* compiled while the override still existed */');

    return $path;
}

it('is registered as the remove-flux-overrides task', function () {
    $task = app(RemoveFluxOverrides::class);

    expect($task->key())->toBe('remove-flux-overrides')
        ->and($task->label())->toBe('Remove published Flux overrides');
});

it('removes the configured views from the app flux folder', function () {
    config()->set('tailor.settings.tasks.remove-flux-overrides.views', ['navlist/group']);

    $removeFluxViews = Mockery::mock(RemoveFluxViews::class);
    $removeFluxViews->shouldReceive('execute')
        ->once()
        ->with(resource_path('views/flux'), ['navlist/group'])
        ->andReturn(['navlist/group']);
    $this->app->instance(RemoveFluxViews::class, $removeFluxViews);

    expect(app(RemoveFluxOverrides::class)->apply())->toBe([]);
});

it('removes nothing when no views are configured', function () {
    config()->set('tailor.settings.tasks.remove-flux-overrides.views', null);

    $removeFluxViews = Mockery::mock(RemoveFluxViews::class);
    $removeFluxViews->shouldReceive('execute')
        ->once()
        ->with(resource_path('views/flux'), [])
        ->andReturn([]);
    $this->app->instance(RemoveFluxViews::class, $removeFluxViews);

    expect(app(RemoveFluxOverrides::class)->apply())->toBe([]);
});

// Nothing this task touches rewrites a caller, so no view's mtime moves and Blade
// never recompiles the parents that folded the override in — a stale compiled copy
// renders the component as nothing at all.
it('clears the compiled views once it has removed an override', function () {
    fakeFluxRemoval(['navlist/group']);
    $stale = staleCompiledView();

    app(RemoveFluxOverrides::class)->apply();

    expect(file_exists($stale))->toBeFalse();
});

it('leaves the compiled views alone when it removed nothing', function () {
    fakeFluxRemoval([]);
    $stale = staleCompiledView();

    app(RemoveFluxOverrides::class)->apply();

    expect(file_exists($stale))->toBeTrue();

    unlink($stale);
});

it('leaves Laravel Prompts rendering to the command output after clearing', function () {
    fakeFluxRemoval(['navlist/group']);

    $output = new OutputStyle(new ArrayInput([]), new BufferedOutput);

    app(RemoveFluxOverrides::class)->apply($output);

    // Artisan::call() re-points Prompts at the NullOutput it is handed, which would
    // leave the run's remaining prompts — "remove the Tailor package now?" — invisible.
    expect(promptOutput())->toBe($output);
});
