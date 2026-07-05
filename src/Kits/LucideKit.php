<?php

namespace Onelegstudios\Tailor\Kits;

use Illuminate\Console\OutputStyle;
use Onelegstudios\Tailor\Actions\PublishLucideIcons;
use Onelegstudios\Tailor\Actions\ReplaceHeroicons;
use Onelegstudios\Tailor\Services\PublishFluxIcons;

/**
 * Flux with Lucide Icons — swaps the starter kit's Heroicons for their Lucide
 * equivalents and re-aliases the icons Flux references internally.
 */
class LucideKit implements UiKit
{
    public function __construct(
        private readonly ReplaceHeroicons $replaceHeroicons,
        private readonly PublishLucideIcons $publishLucideIcons,
        private readonly PublishFluxIcons $publishFluxIcons,
    ) {}

    public function key(): string
    {
        return 'lucide';
    }

    public function label(): string
    {
        return 'Flux with Lucide Icons';
    }

    public function apply(?OutputStyle $output = null): array
    {
        $iconPath = resource_path('views/flux/icon');

        $starterKit = config('tailor.icons.starter-kit', []);

        // Flatten the grouped heroicon/lucide maps into a single lookup. The
        // leading [] keeps this safe when the config is empty, and filtering to
        // arrays ignores a malformed entry rather than raising a TypeError.
        $map = array_merge([], ...array_values(array_filter($starterKit, 'is_array')));

        $flux = config('tailor.icons.flux', []);
        $normal = $flux['normal'] ?? [];
        $animated = $flux['animated'] ?? [];

        // Gather every icon name up front — the starter-kit replacements plus
        // the Lucide glyphs Flux's own components need — so the whole set is
        // downloaded in a single pass.
        $icons = [
            ...array_values($map),
            ...$this->publishFluxIcons->replacements($normal, $animated),
        ];

        $failed = $this->publishLucideIcons->execute($iconPath, $icons, $output);

        // Only mutate the app once every icon is on disk, so a failed download
        // leaves the views and icon directory untouched rather than half-tailored.
        if ($failed === []) {
            $this->replaceHeroicons->execute(resource_path('views'), $map);

            // Starter-kit glyphs are referenced directly by their Lucide name, so
            // they must survive the Flux aliasing pass even when a Flux icon shares
            // the same replacement.
            $this->publishFluxIcons->applyAliases($iconPath, $normal, $animated, array_values($map));
        }

        return $failed;
    }
}
