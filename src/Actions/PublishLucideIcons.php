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
     * Download the given Lucide icons into the flux/icon directory, then remove
     * any previous icon blades that are not part of the new set. The cleanup is
     * skipped when a download fails, so a partial download never leaves the kit
     * without its icons.
     *
     * @param  array<int, string>  $icons  Lucide icon names to download.
     * @return array<int, string> the icons that failed to download
     */
    public function execute(string $iconPath, array $icons, ?OutputStyle $output = null): array
    {
        $failed = $this->downloadIcons($iconPath, $icons, $output);

        if ($failed === []) {
            $this->removeStaleIcons($iconPath, $icons);
        }

        return $failed;
    }

    /**
     * Delete every existing icon blade that is not part of the freshly
     * downloaded set, leaving the kit with exactly the new Lucide icons. Other
     * file types in the directory are left untouched.
     *
     * @param  array<int, string>  $keep  the icons that were just downloaded
     */
    private function removeStaleIcons(string $iconPath, array $keep): void
    {
        if (! $this->files->isDirectory($iconPath)) {
            return;
        }

        $keep = array_values(array_unique(array_filter($keep)));

        foreach ($this->files->files($iconPath) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $icon = substr($file->getFilename(), 0, -strlen('.blade.php'));

            if (! in_array($icon, $keep, true)) {
                $this->files->delete($file->getPathname());
            }
        }
    }
}
