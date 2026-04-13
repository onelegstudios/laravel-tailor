<?php

namespace Onelegstudios\Tailor\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Onelegstudios\Tailor\Tests\WithoutLivewireTestCase;

class PreviewRoutesWithoutLivewireTest extends WithoutLivewireTestCase
{
    public function test_preview_routes_are_not_registered_without_livewire(): void
    {
        $this->assertFalse(Route::has('dev'));
        $this->assertFalse(Route::has('dev.icon-map'));

        $this->get('/dev')->assertNotFound();
        $this->get('/dev/icon-map')->assertNotFound();
    }
}
