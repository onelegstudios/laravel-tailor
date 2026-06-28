<?php

namespace Onelegstudios\Tailor\Actions\Concerns;

use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\NullOutput;

trait DownloadsIcons
{
    /**
     * Download each icon via Flux's flux:icon command, reporting progress as it
     * goes and warning about any icon that did not end up on disk.
     *
     * @param  array<int, string>  $icons
     * @return array<int, string> the icons that failed to download
     */
    protected function downloadIcons(string $iconPath, array $icons, ?OutputStyle $output = null): array
    {
        $icons = array_values(array_unique(array_filter($icons)));

        if ($icons === []) {
            return [];
        }

        $total = count($icons);
        $output?->writeln("<info>Downloading {$total} icon(s) from Lucide...</info>");

        $failed = [];

        foreach ($icons as $index => $icon) {
            Artisan::call('flux:icon', ['icons' => [$icon]], new NullOutput);

            $downloaded = $this->files->exists($iconPath.'/'.$icon.'.blade.php');

            if (! $downloaded) {
                $failed[] = $icon;
            }

            $marker = $downloaded ? '<info>✓</info>' : '<fg=red>✗</>';
            $output?->writeln(sprintf('  [%d/%d] %s %s', $index + 1, $total, $marker, $icon));
        }

        if ($failed !== []) {
            $output?->newLine();
            $output?->writeln('<fg=red>⚠ '.count($failed).' icon(s) failed to download: '.implode(', ', $failed).'</>');
        }

        return $failed;
    }
}
