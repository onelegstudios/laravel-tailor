<?php

namespace Onelegstudios\Tailor\Actions;

use Illuminate\Filesystem\Filesystem;

class GroupComponentViews
{
    public function __construct(
        private readonly Filesystem $files,
    ) {}

    /**
     * Sort the flat components at the root of views/components into the subfolder
     * each one belongs to, then rewrite every <x-name> reference in the views and
     * tests to the dotted name it now resolves as (<x-auth.auth-header>).
     *
     * Only the root of components/ is considered: a component already sitting in a
     * subfolder (settings/layout, moved there by the move-components task) is
     * already grouped and is left alone. A root component with no group in $groups
     * is left in place and returned, so an unrecognised component — a kit's own, or
     * one the developer added — is never guessed at.
     *
     * A target that already exists is never clobbered; its name is returned
     * instead. A missing components/ folder is handled gracefully, and grouping is
     * idempotent: a second run finds the root empty of grouped names, moves
     * nothing, and rewrites nothing.
     *
     * The rewrite pass keys off the names moved in this same call, so an
     * interruption between moving and rewriting would leave stale references on a
     * re-run; execute() runs synchronously, so this is not a concern in practice.
     *
     * @param  array<string, array<int, string>>  $groups  folder => component names it holds
     * @return array<int, string> root component names that were not grouped
     */
    public function execute(string $viewsPath, string $testsPath, array $groups): array
    {
        $componentsPath = $viewsPath.'/components';

        if (! $this->files->isDirectory($componentsPath)) {
            return [];
        }

        $folders = $this->foldersByComponent($groups);

        $ungrouped = [];
        $movedNames = [];

        foreach ($this->files->files($componentsPath) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $name = substr($file->getFilename(), 0, -strlen('.blade.php'));

            if (! isset($folders[$name])) {
                $ungrouped[] = $name;

                continue;
            }

            $folder = $folders[$name];
            $target = $componentsPath.'/'.$folder.'/'.$file->getFilename();

            if ($this->files->exists($target)) {
                $ungrouped[] = $name;

                continue;
            }

            $this->files->ensureDirectoryExists(dirname($target));
            $this->files->move($file->getPathname(), $target);

            $movedNames[$name] = $folder.'.'.$name;
        }

        $this->rewriteReferences($viewsPath, $testsPath, $movedNames);

        return $ungrouped;
    }

    /**
     * Invert the configured folder => names map into the name => folder lookup the
     * move loop wants. A name listed under two folders resolves to the first, so a
     * duplicated entry cannot make the outcome depend on filesystem order.
     *
     * @param  array<string, array<int, string>>  $groups
     * @return array<string, string>
     */
    private function foldersByComponent(array $groups): array
    {
        $folders = [];

        foreach ($groups as $folder => $names) {
            foreach ($names as $name) {
                $folders[$name] ??= $folder;
            }
        }

        return $folders;
    }

    /**
     * Rewrite every reference to a grouped component from its bare name to the
     * dotted name it now resolves as. Both tag forms embed the name directly after
     * the x- prefix — <x-app-logo ...> and its </x-app-logo> closing tag — so
     * anchoring on that prefix covers self-closing tags, multi-line attribute
     * lists, and slot-bearing usages alike.
     *
     * The lookahead stops a shorter name from matching inside a longer sibling
     * (x-app-logo must not match the x-app-logo-icon tag), and anchoring on <x- or
     * </x- keeps the pattern away from Alpine's x- attributes and from a Flux
     * component that happens to share a name.
     *
     * @param  array<string, string>  $movedNames  bare name => dotted name, e.g. app-logo => branding.app-logo
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

            foreach ($movedNames as $name => $dotted) {
                $updated = preg_replace(
                    '/(?<=<x-|<\/x-)'.preg_quote($name, '/').'(?![\w.\-])/',
                    $dotted,
                    $updated,
                ) ?? $updated;
            }

            if ($updated !== $original) {
                $this->files->put($path, $updated);
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
