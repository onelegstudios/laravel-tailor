<?php

use Flux\FluxServiceProvider;
use Illuminate\Support\Facades\Route;
use Livewire\Mechanisms\HandleRouting\LivewirePageController;

if (! app()->environment(['local', 'testing'])
    || ! class_exists(LivewirePageController::class)
    || ! class_exists(FluxServiceProvider::class)) {
    return;
}

Route::middleware('web')->group(function (): void {
    Route::get('dev', fn () => redirect()->route('dev.icon-map'))->name('dev');

    $iconMapRoute = Route::get('dev/icon-map', LivewirePageController::class)
        ->name('dev.icon-map');

    $iconMapRoute->action['livewire_component'] = 'tailor-pages::dev.icon-map';
});