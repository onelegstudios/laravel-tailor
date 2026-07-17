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
     * Icons are fetched one flux:icon call at a time on purpose. The command
     * accepts a batch, but per-icon calls give us a progress line and a
     * pass/fail result for each glyph — flux:icon fetches sequentially either
     * way, so this costs no extra network round trips.
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
            $target = $iconPath.'/'.$icon.'.blade.php';

            // Clear any existing blade first so the post-download check reflects
            // this run alone. flux:icon leaves the old file in place when a fetch
            // fails, which would otherwise let a stale copy mask the failure.
            if ($this->files->exists($target)) {
                $this->files->delete($target);
            }

            // Kept quiet: the progress line below is this action's own report, and
            // TailorCommand takes the prompt output back once the kit has run, so
            // the re-pointing Artisan::call() does here needs no undoing.
            Artisan::call('flux:icon', ['icons' => [$icon]], new NullOutput);

            $downloaded = $this->files->exists($target);

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
