<?php

namespace Onelegstudios\Tailor\Actions;

use Illuminate\Filesystem\Filesystem;

class ReplaceHeroicons
{
    public function __construct(
        private readonly Filesystem $files,
    ) {}

    /**
     * Replace Heroicon names with their Lucide equivalents in all blade files
     * found under $viewPath.
     *
     * @param  array<string, string>  $map  heroicon-name => lucide-name
     */
    public function execute(string $viewPath, array $map): void
    {
        $replacements = array_filter(
            $map,
            fn (string $lucide, string $heroicon): bool => $lucide !== '' && $lucide !== $heroicon,
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
        foreach ($map as $heroicon => $lucide) {
            $quoted = preg_quote($heroicon, '/');

            // icon="name", icon:trailing="name", icon-trailing="name", etc.
            $contents = preg_replace(
                '/\b(icon(?:[:-](?:trailing|leading))?)="'.$quoted.'"/',
                '$1="'.$lucide.'"',
                $contents,
            ) ?? $contents;

            // <flux:icon.name (component syntax)
            $contents = preg_replace(
                '/<(flux:icon\.)'.$quoted.'(?=[\s\/>])/',
                '<$1'.$lucide,
                $contents,
            ) ?? $contents;
        }

        return $contents;
    }
}
