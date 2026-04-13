<?php

namespace Onelegstudios\Tailor\Tests;

use Illuminate\Routing\Router;
use Livewire\LivewireServiceProvider;
use Onelegstudios\Tailor\TailorServiceProvider;

class WithoutLivewireTestCase extends TestCase
{
    protected function setUp(): void
    {
        Router::flushMacros();

        parent::setUp();
    }

    protected function getApplicationProviders($app)
    {
        return array_values(array_filter(
            parent::getApplicationProviders($app),
            static fn (string $provider): bool => ! in_array($provider, [
                LivewireServiceProvider::class,
                'Flux\\FluxServiceProvider',
                'Livewire\\Flux\\FluxServiceProvider',
            ], true),
        ));
    }

    protected function getPackageProviders($app)
    {
        return [
            TailorServiceProvider::class,
        ];
    }
}
