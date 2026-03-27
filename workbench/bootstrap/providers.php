<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    Livewire\LivewireServiceProvider::class,
    Livewire\Flux\FluxServiceProvider::class,
];
