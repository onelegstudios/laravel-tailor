<?php

use Illuminate\Support\Facades\Route;

use function Pest\Laravel\get;

test('dev landing page redirects to the package icon map page', function (): void {
    get(route('dev'))
        ->assertRedirectToRoute('dev.icon-map');
});

test('preview routes use the web middleware group', function (): void {
    expect(Route::getRoutes()->getByName('dev')?->gatherMiddleware())
        ->toContain('web');

    expect(Route::getRoutes()->getByName('dev.icon-map')?->gatherMiddleware())
        ->toContain('web');
});
