<?php

use Onelegstudios\Tailor\Actions\MoveAuthViews;
use Onelegstudios\Tailor\Tasks\MoveAuth;

it('is registered as the move-auth task', function () {
    $task = app(MoveAuth::class);

    expect($task->key())->toBe('move-auth')
        ->and($task->label())->toBe('Move the auth folder');
});

it('moves the auth views using the app paths and reports no failures', function () {
    $moveAuthViews = Mockery::mock(MoveAuthViews::class);
    $moveAuthViews->shouldReceive('execute')
        ->once()
        ->with(resource_path('views'), app_path('Providers/FortifyServiceProvider.php'))
        ->andReturnTrue();
    $this->app->instance(MoveAuthViews::class, $moveAuthViews);

    expect(app(MoveAuth::class)->apply())->toBe([]);
});
