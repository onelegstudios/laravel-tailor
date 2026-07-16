<?php

use Onelegstudios\Tailor\Actions\GroupComponentViews;
use Onelegstudios\Tailor\Tasks\GroupComponents;

it('is registered as the group-components task', function () {
    $task = app(GroupComponents::class);

    expect($task->key())->toBe('group-components')
        ->and($task->label())->toBe('Group components into subfolders');
});

it('groups the components using the app paths and the configured groups', function () {
    config()->set('tailor.settings.tasks.group-components.groups', [
        'branding' => ['app-logo'],
    ]);

    $groupComponentViews = Mockery::mock(GroupComponentViews::class);
    $groupComponentViews->shouldReceive('execute')
        ->once()
        ->with(resource_path('views'), base_path('tests'), ['branding' => ['app-logo']])
        ->andReturn([]);
    $this->app->instance(GroupComponentViews::class, $groupComponentViews);

    expect(app(GroupComponents::class)->apply())->toBe([]);
});

it('groups nothing when no groups are configured', function () {
    config()->set('tailor.settings.tasks.group-components.groups', null);

    $groupComponentViews = Mockery::mock(GroupComponentViews::class);
    $groupComponentViews->shouldReceive('execute')
        ->once()
        ->with(resource_path('views'), base_path('tests'), [])
        ->andReturn([]);
    $this->app->instance(GroupComponentViews::class, $groupComponentViews);

    expect(app(GroupComponents::class)->apply())->toBe([]);
});

it('surfaces the components the action could not group', function () {
    $groupComponentViews = Mockery::mock(GroupComponentViews::class);
    $groupComponentViews->shouldReceive('execute')
        ->once()
        ->andReturn(['team-switcher']);
    $this->app->instance(GroupComponentViews::class, $groupComponentViews);

    expect(app(GroupComponents::class)->apply())->toBe(['team-switcher']);
});
