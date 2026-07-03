<?php

namespace Onelegstudios\Tailor\Tests\Fixtures\Overrides\Kits;

use Illuminate\Console\OutputStyle;
use Onelegstudios\Tailor\Kits\UiKit;

/**
 * Stands in for an app-provided override at app/Tailor/Kits/LucideKit.php.
 */
class LucideKit implements UiKit
{
    public function key(): string
    {
        return 'lucide';
    }

    public function label(): string
    {
        return 'Overridden Lucide';
    }

    public function apply(?OutputStyle $output = null): array
    {
        return [];
    }
}
