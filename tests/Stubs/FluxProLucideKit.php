<?php

namespace Onelegstudios\Tailor\Tests\Stubs;

use Onelegstudios\Tailor\Kits\LucideKit;

/**
 * LucideKit pinned to "Flux Pro is installed". Pins the answer rather than
 * relying on flux-pro being present in vendor, so the Pro path is covered even
 * for a contributor without the license auth that dev dependency needs.
 */
class FluxProLucideKit extends LucideKit
{
    protected function fluxProIsInstalled(): bool
    {
        return true;
    }
}
