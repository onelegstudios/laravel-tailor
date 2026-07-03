<?php

use Illuminate\Support\Facades\Route;

it('does not register the icon route outside local environment', function () {
    expect(app()->environment('local'))->toBeFalse()
        ->and(Route::has('tailor.icons'))->toBeFalse();
});
