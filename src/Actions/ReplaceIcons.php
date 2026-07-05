<?php

namespace Onelegstudios\Tailor\Actions;

use Illuminate\Filesystem\Filesystem;

class ReplaceIcons
{
    public function __construct(
        private readonly Filesystem $files,
    ) {}

    /**
     * Rename icon references in every blade file under $viewPath. The map is
     * direction-agnostic — the Lucide kit passes heroicon => lucide, the
     * Heroicons kit passes lucide => heroicon — so this simply swaps each source
     * name for its target wherever an icon attribute or <flux:icon.*> tag uses it.
     *
     * @param  array<string, string>  $map  source-icon-name => target-icon-name
     */
    public function execute(string $viewPath, array $map): void
    {
        $replacements = array_filter(
            $map,
            fn (string $target, string $source): bool => $target !== '' && $target !== $source,
            ARRAY_FILTER_USE_BOTH,
        );

        if (empty($replacements)) {
            return;
        }

        foreach ($this->files->allFiles($viewPath) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $original = $this->files->get($file->getPathname());
            $updated = $this->replaceInContents($original, $replacements);

            if ($updated !== $original) {
                $this->files->put($file->getPathname(), $updated);
            }
        }
    }

    /**
     * @param  array<string, string>  $map
     */
    private function replaceInContents(string $contents, array $map): string
    {
        foreach ($map as $source => $target) {
            $quoted = preg_quote($source, '/');

            // icon="name", icon:trailing="name", icon-trailing="name", etc.
            $contents = preg_replace(
                '/\b(icon(?:[:-](?:trailing|leading))?)="'.$quoted.'"/',
                '$1="'.$target.'"',
                $contents,
            ) ?? $contents;

            // <flux:icon.name (component syntax)
            $contents = preg_replace(
                '/<(flux:icon\.)'.$quoted.'(?=[\s\/>])/',
                '<$1'.$target,
                $contents,
            ) ?? $contents;
        }

        return $contents;
    }
}
