<?php

namespace Onelegstudios\Tailor\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class WorkbenchRefreshRunner
{
    protected LivewireWorkbenchRefresher $refresher;

    public function __construct(
        protected Filesystem $files,
        ?LivewireWorkbenchRefresher $refresher = null,
    ) {
        $this->refresher = $refresher ?? new LivewireWorkbenchRefresher($this->files);
    }

    public function run(string $projectRoot): int
    {
        if (! $this->files->isDirectory($projectRoot.DIRECTORY_SEPARATOR.'.git')) {
            fwrite(STDOUT, "Skipping workbench refresh outside a git checkout.\n");

            return 0;
        }

        fwrite(STDOUT, "Refreshing workbench from the latest Livewire starter kit...\n");

        try {
            $this->refreshWorkbench($projectRoot);
            $this->buildWorkbench($projectRoot);
        } catch (Throwable $throwable) {
            fwrite(STDERR, $throwable->getMessage()."\n");

            return 1;
        }

        fwrite(STDOUT, "Workbench refreshed.\n");

        return 0;
    }

    protected function refreshWorkbench(string $projectRoot): void
    {
        $this->refresher->refresh($projectRoot.DIRECTORY_SEPARATOR.'workbench');
    }

    protected function buildWorkbench(string $projectRoot): void
    {
        $process = new Process([
            PHP_BINARY,
            'vendor/bin/testbench',
            'workbench:build',
            '--ansi',
        ], $projectRoot);

        $process->setTimeout(null);
        $process->run();

        if ($process->isSuccessful()) {
            return;
        }

        $errorOutput = trim($process->getErrorOutput());
        $output = trim($process->getOutput());

        throw new RuntimeException($errorOutput !== '' ? $errorOutput : $output);
    }
}
