<?php

namespace Onelegstudios\Tailor\Actions;

use Illuminate\Console\OutputStyle;
use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Actions\Concerns\DownloadsIcons;

class PublishLucideIcons
{
    use DownloadsIcons;

    public function __construct(
        private readonly Filesystem $files,
    ) {}

    /**
     * Empty the flux/icon directory of any existing icon blades, then download
     * the given Lucide icons into it via Flux's flux:icon command.
     *
     * @param  array<int, string>  $icons  Lucide icon names to download.
     */
    public function execute(string $iconPath, array $icons, ?OutputStyle $output = null): void
    {
        $this->emptyExisting($iconPath);

        $this->downloadIcons($iconPath, $icons, $output);
    }

    /**
     * Delete every existing icon blade so the kit starts from a clean slate
     * before the fresh Lucide set is downloaded.
     */
    private function emptyExisting(string $iconPath): void
    {
        if (! $this->files->isDirectory($iconPath)) {
            return;
        }

        foreach ($this->files->files($iconPath) as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $this->files->delete($file->getPathname());
            }
        }
    }
}
