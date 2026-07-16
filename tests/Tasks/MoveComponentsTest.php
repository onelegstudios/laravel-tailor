<?php

use Onelegstudios\Tailor\Actions\MoveComponentViews;
use Onelegstudios\Tailor\Tasks\MoveComponents;

it('is registered as the move-components task', function () {
    $task = app(MoveComponents::class);

    expect($task->key())->toBe('move-components')
        ->and($task->label())->toBe('Move non-routed pages components');
});

it('moves the components using the app paths and reports no failures', function () {
    $moveComponentViews = Mockery::mock(MoveComponentViews::class);
    $moveComponentViews->shouldReceive('execute')
        ->once()
        ->with(resource_path('views'), base_path('routes'), base_path('tests'))
        ->andReturn([]);
    $this->app->instance(MoveComponentViews::class, $moveComponentViews);

    expect(app(MoveComponents::class)->apply())->toBe([]);
});

it('surfaces the names the action could not move', function () {
    $moveComponentViews = Mockery::mock(MoveComponentViews::class);
    $moveComponentViews->shouldReceive('execute')
        ->once()
        ->andReturn(['pages::settings.layout']);
    $this->app->instance(MoveComponentViews::class, $moveComponentViews);

    expect(app(MoveComponents::class)->apply())->toBe(['pages::settings.layout']);
});
