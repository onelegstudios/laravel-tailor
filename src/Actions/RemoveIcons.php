<?php

namespace Onelegstudios\Tailor\Actions;

use Illuminate\Filesystem\Filesystem;

class RemoveIcons
{
    public function __construct(
        private readonly Filesystem $files,
    ) {}

    /**
     * Delete the named icon blades from $iconPath. Used to drop the starter
     * kit's published Lucide overrides once their references have been swapped
     * back to Heroicons, which Flux resolves from its bundled set with no blade
     * on disk. Missing files and a missing directory are ignored, so a kit that
     * has already been tailored can be re-run safely.
     *
     * @param  array<int, string>  $icons  icon names (without the .blade.php suffix)
     */
    public function execute(string $iconPath, array $icons): void
    {
        if (! $this->files->isDirectory($iconPath)) {
            return;
        }

        foreach (array_unique(array_filter($icons)) as $icon) {
            $path = $iconPath.'/'.$icon.'.blade.php';

            if ($this->files->exists($path)) {
                $this->files->delete($path);
            }
        }
    }
}
