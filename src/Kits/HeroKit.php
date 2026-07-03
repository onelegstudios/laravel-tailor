<?php

namespace Onelegstudios\Tailor\Kits;

use Illuminate\Console\OutputStyle;

/**
 * Flux with Heroicons — the starter kit's default state, so there is nothing
 * to apply.
 */
class HeroKit implements UiKit
{
    public function key(): string
    {
        return 'hero';
    }

    public function label(): string
    {
        return 'Flux with Heroicons';
    }

    public function apply(?OutputStyle $output = null): array
    {
        return [];
    }
}
