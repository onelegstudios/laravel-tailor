<?php

namespace Onelegstudios\Tailor\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Onelegstudios\Tailor\Tailor
 */
class Tailor extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Onelegstudios\Tailor\Tailor::class;
    }
}
