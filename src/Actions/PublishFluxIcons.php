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
     * @param  array<string, string>  $normal  original Flux icon name => Lucide replacement
     * @param  array<string, string>  $animated  original animated icon name => Lucide replacement
     */
    public function applyAliases(string $iconPath, array $normal, array $animated): void
    {
        foreach ($normal as $original => $replacement) {
            $this->alias($iconPath, $replacement, $original);
        }

        foreach ($animated as $original => $replacement) {
            $this->alias($iconPath, $replacement, $original, animate: true);
        }
    }

    /**
     * Copy the downloaded $replacement icon to a blade named after $original so
     * Flux's internal components (which reference the original name) resolve to
     * the Lucide glyph. When $animate is set, a Tailwind animation class is added
     * to the copy so it keeps moving.
     */
    private function alias(string $iconPath, string $replacement, string $original, bool $animate = false): void
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
