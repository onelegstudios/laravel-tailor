<?php

namespace Onelegstudios\Tailor\Actions;

use Illuminate\Filesystem\Filesystem;

class MoveComponentViews
{
    public function __construct(
        private readonly Filesystem $files,
    ) {}

    /**
     * Relocate the starter kit's non-routed Livewire page components out of the
     * pages namespace folder (views/pages) and into views/components, preserving
     * their subpath, then rewrite every pages:: reference in the views and tests
     * to the bare component name they now resolve as.
     *
     * A component is left in place when it is directly routed — its qualified
     * name (pages::x.y) appears as a quoted literal in any routes/*.php file — or
     * when it lives in an excluded top-level folder (auth is owned by the
     * move-auth task). Everything else under pages/, including anonymous Blade
     * views such as settings/layout, is moved.
     *
     * A target that already exists is never clobbered; its qualified name is
     * returned instead. A missing pages/ folder, an already-moved component, and
     * a missing tests/ folder are all handled gracefully, so the task can be
     * re-run safely — a second run finds nothing to move and rewrites nothing.
     *
     * The rewrite pass keys off the names moved in this same call, so an
     * interruption between moving and rewriting would leave stale references on a
     * re-run; execute() runs synchronously, so this is not a concern in practice.
     *
     * @param  array<int, string>  $excludeFolders  top-level pages/ folders to leave in place
     * @return array<int, string> qualified names (pages::x.y) that could not be moved
     */
    public function execute(string $viewsPath, string $routesPath, string $testsPath, array $excludeFolders = ['auth']): array
    {
        $pagesPath = $viewsPath.'/pages';

        if (! $this->files->isDirectory($pagesPath)) {
            return [];
        }

        $routed = $this->routedNames($routesPath);

        $failed = [];
        $movedNames = [];

        foreach ($this->files->allFiles($pagesPath) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relative = ltrim(str_replace($pagesPath, '', $file->getPathname()), '/');

            if (in_array(explode('/', $relative)[0], $excludeFolders, true)) {
                continue;
            }

            $dotted = $this->dottedName($relative);

            if (in_array('pages::'.$dotted, $routed, true)) {
                continue;
            }

            $target = $viewsPath.'/components/'.$relative;

            if ($this->files->exists($target)) {
                $failed[] = 'pages::'.$dotted;

                continue;
            }

            $this->files->ensureDirectoryExists(dirname($target));
            $this->files->move($file->getPathname(), $target);

            $movedNames[] = $dotted;
        }

        $this->rewriteReferences($viewsPath, $testsPath, $movedNames);

        $this->pruneEmptyDirectories($pagesPath);

        return $failed;
    }

    /**
     * The qualified pages:: names that are directly reachable from a route. Any
     * quoted pages:: literal in a routes/*.php file counts, which is agnostic to
     * the routing helper used (Route::livewire, Route::get, a redirect, etc.) and
     * fails safe: an unexpected literal leaves the component in place rather than
     * moving it and breaking the route.
     *
     * @return array<int, string>
     */
    private function routedNames(string $routesPath): array
    {
        $routed = [];

        foreach ($this->files->glob($routesPath.'/*.php') as $routeFile) {
            preg_match_all('/[\'"](pages::[^\'"]+)[\'"]/', $this->files->get($routeFile), $matches);

            $routed = array_merge($routed, $matches[1]);
        }

        return $routed;
    }

    /**
     * Derive the dotted component name Livewire resolves an SFC to from its path
     * relative to pages/: drop the .blade.php extension, strip the ⚡ marker (and
     * its optional variation selector) exactly as Livewire's Finder does, then
     * turn directory separators into dots.
     */
    private function dottedName(string $relative): string
    {
        $withoutExtension = substr($relative, 0, -strlen('.blade.php'));

        $stripped = preg_replace('/\x{26A1}[\x{FE0E}\x{FE0F}]?/u', '', $withoutExtension) ?? $withoutExtension;

        return str_replace('/', '.', $stripped);
    }

    /**
     * Rewrite every reference to a moved component from its pages:: name to the
     * bare name it now resolves as under views/components. A single token
     * replacement covers all reference forms — <livewire:pages::x.y>,
     * <x-pages::x.y> and its closing tag, and Livewire::test('pages::x.y') — since
     * each embeds the literal pages::x.y string.
     *
     * The pattern is anchored to the exact moved names, so routed pages and the
     * auth views (never moved, never in $movedNames) are provably untouched. The
     * lookbehind allows the real prefixes (: - ' " whitespace) while blocking a
     * word-character run, and the lookahead stops a shorter name from matching
     * inside a longer sibling.
     *
     * @param  array<int, string>  $movedNames  dotted names, e.g. settings.delete-user-form
     */
    private function rewriteReferences(string $viewsPath, string $testsPath, array $movedNames): void
    {
        if ($movedNames === []) {
            return;
        }

        $files = $this->bladeFiles($viewsPath);

        if ($this->files->isDirectory($testsPath)) {
            $files = array_merge($files, $this->phpFiles($testsPath));
        }

        foreach ($files as $path) {
            $original = $this->files->get($path);
            $updated = $original;

            foreach ($movedNames as $name) {
                $updated = preg_replace(
                    '/(?<![\w])pages::'.preg_quote($name, '/').'(?![\w.\-])/',
                    $name,
                    $updated,
                ) ?? $updated;
            }

            if ($updated !== $original) {
                $this->files->put($path, $updated);
            }
        }
    }

    /**
     * Remove now-empty descendant directories of pages/ bottom-up, leaving pages/
     * itself in place — the pages namespace stays registered and may hold routed
     * pages or gain new ones.
     */
    private function pruneEmptyDirectories(string $pagesPath): void
    {
        $directories = $this->files->allDirectories($pagesPath);

        usort($directories, fn (string $a, string $b): int => substr_count($b, '/') <=> substr_count($a, '/'));

        foreach ($directories as $directory) {
            if ($this->files->files($directory) === [] && $this->files->directories($directory) === []) {
                $this->files->deleteDirectory($directory);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function bladeFiles(string $path): array
    {
        return $this->filesMatching($path, '.blade.php');
    }

    /**
     * @return array<int, string>
     */
    private function phpFiles(string $path): array
    {
        return $this->filesMatching($path, '.php');
    }

    /**
     * @return array<int, string>
     */
    private function filesMatching(string $path, string $suffix): array
    {
        $matches = [];

        foreach ($this->files->allFiles($path) as $file) {
            if (str_ends_with($file->getFilename(), $suffix)) {
                $matches[] = $file->getPathname();
            }
        }

        return $matches;
    }
}
