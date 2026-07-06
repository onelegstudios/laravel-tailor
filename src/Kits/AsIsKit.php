<?php

namespace Onelegstudios\Tailor\Kits;

use Illuminate\Console\OutputStyle;

/**
 * Leave the starter kit exactly as it ships — a deliberate no-op. Offered so the
 * user can run Tailor for its tasks (e.g. moving the auth folder) without
 * touching the mixed Heroicon/Lucide icon set the starter kit comes with.
 */
class AsIsKit implements UiKit
{
    public function key(): string
    {
        return 'as-is';
    }

    public function label(): string
    {
        return 'Leave the starter kit as-is';
    }

    public function apply(?OutputStyle $output = null): array
    {
        return [];
    }
}
