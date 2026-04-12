<?php

use Illuminate\Support\Facades\Route;

if (app()->environment(['local', 'testing'])) {
    Route::get('dev', fn () => redirect()->route('dev.icon-map'))->name('dev');
    Route::livewire('dev/icon-map', 'tailor::pages.dev.icon-map')->name('dev.icon-map');
}
