<?php

use Onelegstudios\Tailor\Actions\ConvertPartialViews;
use Onelegstudios\Tailor\Tasks\ConvertPartials;

it('is registered as the convert-partials task', function () {
    $task = app(ConvertPartials::class);

    expect($task->key())->toBe('convert-partials')
        ->and($task->label())->toBe('Convert partials into components');
});

it('converts the partials using the app paths and the configured props', function () {
    config()->set('tailor.settings.tasks.convert-partials.props', [
        'head' => ['title'],
    ]);

    $convertPartialViews = Mockery::mock(ConvertPartialViews::class);
    $convertPartialViews->shouldReceive('execute')
        ->once()
        ->with(resource_path('views'), base_path('tests'), ['head' => ['title']])
        ->andReturn([]);
    $this->app->instance(ConvertPartialViews::class, $convertPartialViews);

    expect(app(ConvertPartials::class)->apply())->toBe([]);
});

it('converts with no props when none are configured', function () {
    config()->set('tailor.settings.tasks.convert-partials.props', null);

    $convertPartialViews = Mockery::mock(ConvertPartialViews::class);
    $convertPartialViews->shouldReceive('execute')
        ->once()
        ->with(resource_path('views'), base_path('tests'), [])
        ->andReturn([]);
    $this->app->instance(ConvertPartialViews::class, $convertPartialViews);

    expect(app(ConvertPartials::class)->apply())->toBe([]);
});

it('surfaces the partials the action could not convert', function () {
    $convertPartialViews = Mockery::mock(ConvertPartialViews::class);
    $convertPartialViews->shouldReceive('execute')
        ->once()
        ->andReturn(['partials.head']);
    $this->app->instance(ConvertPartialViews::class, $convertPartialViews);

    expect(app(ConvertPartials::class)->apply())->toBe(['partials.head']);
});
