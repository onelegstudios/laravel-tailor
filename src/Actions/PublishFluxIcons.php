<?php

namespace Onelegstudios\Tailor\Actions;

use Illuminate\Filesystem\Filesystem;

class PublishFluxIcons
{
    /**
     * Tailwind animation utility added to animated icons (e.g. the loading
     * spinner) so they keep moving once swapped for a static Lucide glyph.
     */
    private const ANIMATION_CLASS = 'animate-spin';

    public function __construct(
        private readonly Filesystem $files,
    ) {}

    /**
     * The Lucide replacement names that Flux's own components need downloaded.
     * Returned so the caller can fold them into a single download pass.
     *
     * @param  array<string, string>  $normal  original Flux icon name => Lucide replacement
     * @param  array<string, string>  $animated  original animated icon name => Lucide replacement
     * @return array<int, string>
     */
    public function replacements(array $normal, array $animated): array
    {
        return array_merge(array_values($normal), array_values($animated));
    }

    /**
     * Alias each downloaded replacement under its original Flux name so Flux's
     * internal references keep resolving. Animated icons get a Tailwind animation
     * class on the alias.
     *
     * Flux icons are only referenced internally, by their original name, so the
     * downloaded Lucide glyph is renamed rather than copied — unless its Lucide
     * name is also needed directly (a $preserve entry), in which case both the
     * original-named alias and the Lucide-named source are kept.
     *
     * @param  array<string, string>  $normal  original Flux icon name => Lucide replacement
     * @param  array<string, string>  $animated  original animated icon name => Lucide replacement
     * @param  array<int, string>  $preserve  Lucide names that must stay on disk under their own name
     */
    public function applyAliases(string $iconPath, array $normal, array $animated, array $preserve = []): void
    {
        foreach ($normal as $original => $replacement) {
            $this->alias($iconPath, $replacement, $original, $preserve);
        }

        foreach ($animated as $original => $replacement) {
            $this->alias($iconPath, $replacement, $original, $preserve, animate: true);
        }
    }

    /**
     * Write the downloaded $replacement icon to a blade named after $original so
     * Flux's internal components (which reference the original name) resolve to
     * the Lucide glyph. When $animate is set, a Tailwind animation class is added
     * so it keeps moving. The Lucide-named source is removed afterwards unless it
     * is listed in $preserve (something else references it by that name).
     *
     * @param  array<int, string>  $preserve
     */
    private function alias(string $iconPath, string $replacement, string $original, array $preserve, bool $animate = false): void
    {
        if ($replacement === '') {
            return;
        }

        // The downloaded file already serves the original name, unless we still
        // need to layer an animation class on top of it.
        if ($original === $replacement && ! $animate) {
            return;
        }

        $source = $iconPath.'/'.$replacement.'.blade.php';

        if (! $this->files->exists($source)) {
            return;
        }

        $contents = $this->files->get($source);

        if ($animate) {
            $contents = $this->addAnimation($contents);
        }

        $this->files->put($iconPath.'/'.$original.'.blade.php', $contents);

        // Rename rather than duplicate: drop the redundant Lucide-named copy
        // once it serves no purpose of its own.
        if ($original !== $replacement && ! in_array($replacement, $preserve, true)) {
            $this->files->delete($source);
        }
    }

    /**
     * Inject the Tailwind animation utility into the icon's base Flux classes.
     */
    private function addAnimation(string $contents): string
    {
        return str_replace(
            "Flux::classes('shrink-0')",
            "Flux::classes('shrink-0 ".self::ANIMATION_CLASS."')",
            $contents,
        );
    }
}
