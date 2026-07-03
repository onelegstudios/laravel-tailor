<?php

use Illuminate\Support\Facades\Route;
use Onelegstudios\Tailor\Tests\Concerns\RunsInLocalEnvironment;

uses(RunsInLocalEnvironment::class);

it('registers the icon route in local environment', function () {
    expect(app()->environment('local'))->toBeTrue()
        ->and(Route::has('tailor.icons'))->toBeTrue();
});

it('renders the icon reference page', function () {
    config()->set('tailor.icons', [
        'starter-kit' => [
            'heroicons' => [
                'magnifying-glass' => 'search',
            ],
        ],
    ]);

    $this->get('/tailor/icons')
        ->assertOk()
        ->assertSee('Tailor icons')
        ->assertSee('magnifying-glass');
});
