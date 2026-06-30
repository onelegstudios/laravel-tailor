<?php

namespace Onelegstudios\Tailor\Kits;

use Illuminate\Console\OutputStyle;

/**
 * Tall Stack UI. Not yet implemented — selecting it currently does nothing.
 */
class TallStackKit implements UiKit
{
    public function key(): string
    {
        return 'tall-stack';
    }

    public function label(): string
    {
        return 'Tall Stack UI';
    }

    public function apply(?OutputStyle $output = null): array
    {
        return [];
    }
}
