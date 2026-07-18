<?php

namespace Onelegstudios\Tailor\Tests\Stubs;

use Onelegstudios\Tailor\Kits\LucideKit;

/**
 * LucideKit pinned to "Flux Pro is not installed". Detection reads the Composer
 * runtime API, which reports this package's own dev dependencies — flux-pro among
 * them — so the free-only path can't be reached by resolving the real kit.
 */
class FreeOnlyLucideKit extends LucideKit
{
    protected function fluxProIsInstalled(): bool
    {
        return false;
    }
}
