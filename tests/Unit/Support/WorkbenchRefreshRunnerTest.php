<?php

use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Support\LivewireWorkbenchRefresher;
use Onelegstudios\Tailor\Support\WorkbenchRefreshRunner;

it('defaults the refresher when none is provided', function (): void {
    $runner = new class(new Filesystem) extends WorkbenchRefreshRunner {};
    $refresher = (new ReflectionProperty(WorkbenchRefreshRunner::class, 'refresher'))->getValue($runner);

    expect($refresher)->toBeInstanceOf(LivewireWorkbenchRefresher::class);
});

it('skips refresh when the project is not a git checkout', function (): void {
    $projectRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'tailor-runner-no-git-'.bin2hex(random_bytes(8));
    $runner = new class(new Filesystem) extends WorkbenchRefreshRunner
    {
        public bool $refreshed = false;

        public bool $built = false;

        protected function refreshWorkbench(string $projectRoot): void
        {
            $this->refreshed = true;
        }

        protected function buildWorkbench(string $projectRoot): void
        {
            $this->built = true;
        }
    };

    try {
        mkdir($projectRoot, 0777, true);

        expect($runner->run($projectRoot))->toBe(0);
        expect($runner->refreshed)->toBeFalse();
        expect($runner->built)->toBeFalse();
    } finally {
        (new Filesystem)->deleteDirectory($projectRoot);
    }
});

it('refreshes and builds when the project is a git checkout', function (): void {
    $projectRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'tailor-runner-git-'.bin2hex(random_bytes(8));
    $runner = new class(new Filesystem) extends WorkbenchRefreshRunner
    {
        public bool $refreshed = false;

        public bool $built = false;

        protected function refreshWorkbench(string $projectRoot): void
        {
            $this->refreshed = true;
        }

        protected function buildWorkbench(string $projectRoot): void
        {
            $this->built = true;
        }
    };

    try {
        mkdir($projectRoot.DIRECTORY_SEPARATOR.'.git', 0777, true);

        expect($runner->run($projectRoot))->toBe(0);
        expect($runner->refreshed)->toBeTrue();
        expect($runner->built)->toBeTrue();
    } finally {
        (new Filesystem)->deleteDirectory($projectRoot);
    }
});

it('builds the testbench skeleton before installing and building frontend assets', function (): void {
    $runner = new class(new Filesystem) extends WorkbenchRefreshRunner
    {
        public array $steps = [];

        protected function buildWorkbenchSkeleton(string $projectRoot): void
        {
            $this->steps[] = 'skeleton';
        }

        protected function installWorkbenchDependencies(string $projectRoot): void
        {
            $this->steps[] = 'install';
        }

        protected function buildWorkbenchAssets(string $projectRoot): void
        {
            $this->steps[] = 'assets';
        }
    };

    (new ReflectionMethod(WorkbenchRefreshRunner::class, 'buildWorkbench'))->invoke($runner, __DIR__);

    expect($runner->steps)->toBe(['skeleton', 'install', 'assets']);
});
