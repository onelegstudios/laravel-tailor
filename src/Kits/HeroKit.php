<?php

namespace Onelegstudios\Tailor\Kits;

use Illuminate\Console\OutputStyle;
use Onelegstudios\Tailor\Actions\RemoveIcons;
use Onelegstudios\Tailor\Actions\ReplaceIcons;

/**
 * Flux with Heroicons only — swaps the handful of Lucide icons the starter kit
 * ships (its local overrides under resources/views/flux/icon) back to their Heroicon
 * equivalents, so the kit renders Heroicons throughout. The Heroicons Flux falls
 * back to are bundled with the package, so nothing needs downloading; the orphaned
 * Lucide blades are just removed once their references are rewritten.
 */
class HeroKit implements UiKit
{
    public function __construct(
        private readonly ReplaceIcons $replaceIcons,
        private readonly RemoveIcons $removeIcons,
    ) {}

    public function key(): string
    {
        return 'hero';
    }

    public function label(): string
    {
        return 'Flux with Heroicons only';
    }

    public function apply(?OutputStyle $output = null): array
    {
        $map = config("tailor.settings.kits.{$this->key()}.icons", []);

        // Keep only the entries that actually rewrite something — a real string
        // target that differs from the source — so a blank or self-mapping entry
        // never triggers a spurious rewrite or deletes an icon we left in place.
        $replacements = array_filter(
            $map,
            fn ($target, $source): bool => is_string($target) && $target !== '' && $target !== $source,
            ARRAY_FILTER_USE_BOTH,
        );

        if ($replacements === []) {
            return [];
        }

        $this->replaceIcons->execute(resource_path('views'), $replacements);

        // The Lucide blades now referenced by no view are safe to drop; their
        // Heroicon replacements resolve from Flux's bundled set with no blade.
        $this->removeIcons->execute(resource_path('views/flux/icon'), array_keys($replacements));

        return [];
    }
}
