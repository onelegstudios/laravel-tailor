<?php

namespace Onelegstudios\Tailor\Tests\Concerns;

use Illuminate\Foundation\Application;

/**
 * Boots the package as if the consuming application were running locally, so
 * the local-only Tailor routes get registered.
 */
trait RunsInLocalEnvironment
{
    protected function defineEnvironment($app): void
    {
        /** @var Application $app */
        $app['env'] = 'local';
    }
}
