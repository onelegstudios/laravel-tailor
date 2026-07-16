<?php

namespace Onelegstudios\Tailor\Actions;

use Illuminate\Filesystem\Filesystem;

class RemoveFluxViews
{
    public function __construct(
        private readonly Filesystem $files,
    ) {}

    /**
     * Delete the named published Flux view overrides from $fluxPath, so Flux
     * resolves each component from the package again. A view is named the way
     * Flux addresses it — navlist/group for flux/navlist/group.blade.php — and
     * the folder it leaves behind is removed once nothing else lives in it, so
     * the kit is left with no trace of the override.
     *
     * Missing files and a missing directory are ignored, so a kit that has
     * already been tailored can be re-run safely. Icons are published into
     * flux/icon by the lucide kit and are never touched here; only the views
     * listed by the caller are removed.
     *
     * @param  array<int, string>  $views  view names (without the .blade.php suffix)
     * @return array<int, string> the views that were removed
     */
    public function execute(string $fluxPath, array $views): array
    {
        if (! $this->files->isDirectory($fluxPath)) {
            return [];
        }

        $removed = [];

        foreach (array_unique(array_filter($views)) as $view) {
            $path = $fluxPath.'/'.$view.'.blade.php';

            if (! $this->files->exists($path)) {
                continue;
            }

            $this->files->delete($path);

            $removed[] = $view;

            $this->pruneEmptyFolders(dirname($path), $fluxPath);
        }

        return $removed;
    }

    /**
     * Walk up from the folder the view was removed from, deleting each level
     * that is now empty, and stop at $fluxPath — flux/ itself stays, both
     * because the lucide kit publishes icons into it and because an empty
     * flux/ is where the next publish lands.
     */
    private function pruneEmptyFolders(string $path, string $fluxPath): void
    {
        while ($path !== $fluxPath && str_starts_with($path, $fluxPath)) {
            if ($this->files->files($path) !== [] || $this->files->directories($path) !== []) {
                return;
            }

            $this->files->deleteDirectory($path);

            $path = dirname($path);
        }
    }
}
