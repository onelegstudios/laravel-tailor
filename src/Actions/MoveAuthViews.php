<?php

namespace Onelegstudios\Tailor\Actions;

use Illuminate\Filesystem\Filesystem;

class MoveAuthViews
{
    public function __construct(
        private readonly Filesystem $files,
    ) {}

    /**
     * Relocate the pages starter kit's Fortify auth screens out of the pages
     * namespace folder and into the plain views root, then repoint the view
     * names in FortifyServiceProvider::configureViews() at the new location.
     *
     * Only the pages variant is moved: its blades live in views/pages/auth and
     * are referenced as pages::auth.*, which becomes views/auth and a bare
     * auth.*. The components variant keeps its blades in views/livewire/auth,
     * where they stay put.
     *
     * Missing source, an already-moved target, and a missing provider file are
     * all handled gracefully, so the task can be re-run safely.
     *
     * @return bool whether the auth folder was moved
     */
    public function execute(string $viewsPath, string $providerPath): bool
    {
        $source = $viewsPath.'/pages/auth';
        $target = $viewsPath.'/auth';

        if (! $this->files->isDirectory($source) || $this->files->isDirectory($target)) {
            return false;
        }

        $this->files->moveDirectory($source, $target);

        $this->repointProviderViews($providerPath);

        return true;
    }

    /**
     * Swap the namespaced auth view names for the bare auth.* names now that the
     * blades live in the views root. The prefix is unique to the auth view
     * names, so replacing it is safe.
     */
    private function repointProviderViews(string $providerPath): void
    {
        if (! $this->files->exists($providerPath)) {
            return;
        }

        $original = $this->files->get($providerPath);
        $updated = str_replace('pages::auth.', 'auth.', $original);

        if ($updated !== $original) {
            $this->files->put($providerPath, $updated);
        }
    }
}
