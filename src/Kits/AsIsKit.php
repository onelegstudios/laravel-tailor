<?php

namespace Onelegstudios\Tailor\Kits;

use Illuminate\Console\OutputStyle;

/**
 * Keep the mixed Heroicon/Lucide icon set the starter kit ships with — a
 * deliberate no-op. Offered so the user can run Tailor for its tasks (e.g.
 * moving the auth folder) without touching the icon set at all.
 */
class AsIsKit implements UiKit
{
    public function key(): string
    {
        return 'as-is';
    }

    public function label(): string
    {
        return 'Flux with mixed icons';
    }

    public function apply(?OutputStyle $output = null): array
    {
        return [];
    }
}
