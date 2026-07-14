<?php

namespace Onelegstudios\Tailor\Actions;

use Illuminate\Filesystem\Filesystem;

class MoveAuthViews
{
    public function __construct(
        private readonly Filesystem $files,
    ) {}

    /**
     * Relocate the starter kit's Fortify auth screens out of their Livewire
     * namespace folder and into the plain views root, then repoint the view
     * names in FortifyServiceProvider::configureViews() at the new location.
     *
     * Two starter-kit layouts are supported: the pages variant keeps its blades
     * in views/pages/auth (referenced as pages::auth.*), the components variant
     * in views/livewire/auth (referenced as livewire.auth.*). Whichever exists is
     * moved to views/auth and both prefixes are rewritten to a bare auth.*.
     *
     * Missing source, an already-moved target, and a missing provider file are
     * all handled gracefully, so the task can be re-run safely.
     *
     * @return bool whether the auth folder was moved
     */
    public function execute(string $viewsPath, string $providerPath): bool
    {
        $source = collect([$viewsPath.'/pages/auth', $viewsPath.'/livewire/auth'])
            ->first(fn (string $path): bool => $this->files->isDirectory($path));

        $target = $viewsPath.'/auth';

        if ($source === null || $this->files->isDirectory($target)) {
            return false;
        }

        $this->files->moveDirectory($source, $target);

        $this->repointProviderViews($providerPath);

        return true;
    }

    /**
     * Swap the namespaced auth view names for the bare auth.* names now that the
     * blades live in the views root. Only one prefix is ever present, and each is
     * unique to the auth view names, so replacing both is safe.
     */
    private function repointProviderViews(string $providerPath): void
    {
        if (! $this->files->exists($providerPath)) {
            return;
        }

        $original = $this->files->get($providerPath);
        $updated = str_replace(['pages::auth.', 'livewire.auth.'], 'auth.', $original);

        if ($updated !== $original) {
            $this->files->put($providerPath, $updated);
        }
    }
}
