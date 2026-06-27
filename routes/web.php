<?php

use Illuminate\Support\Facades\Route;

/*
 * Local-development only. Registered from TailorServiceProvider exclusively
 * when the application is running in the "local" environment.
 */
Route::middleware('web')->group(function () {
    Route::livewire('/tailor/icons', 'tailor::icon-list')->name('tailor.icons');
});
