<?php

use Illuminate\Console\OutputStyle;
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

// Narrating the run belongs to TailorCommand, which announces every task by its
// label and knows whether one is worth mentioning. A task that reports itself as
// well says the same thing twice, and this is the only one that ever did.
it('leaves the narrating to the command', function () {
    fakeFluxRemoval(['navlist/group']);
    $buffer = new BufferedOutput;

    app(RemoveFluxOverrides::class)->apply(new OutputStyle(new ArrayInput([]), $buffer));

    expect($buffer->fetch())->toBe('');
});

// Deleting a view leaves the compiled views stale, and discarding them is
// TailorCommand's to do once the run is over — asserted there, see "clears the
// compiled views once the run is over".
