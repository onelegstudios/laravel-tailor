<?php

use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Support\LivewireWorkbenchRefresher;
use Onelegstudios\Tailor\Support\WorkbenchRefreshRunner;

function tailorWorkbenchRunnerTemporaryPath(string $suffix): string
{
    return sys_get_temp_dir().DIRECTORY_SEPARATOR.'tailor-runner-'.$suffix.'-'.bin2hex(random_bytes(8));
}

function deleteTailorWorkbenchRunnerTemporaryPath(string $path): void
{
    (new Filesystem)->deleteDirectory($path);
}

it('defaults the refresher when none is provided', function (): void {
    $runner = new class(new Filesystem) extends WorkbenchRefreshRunner {};
    $refresher = (new ReflectionProperty(WorkbenchRefreshRunner::class, 'refresher'))->getValue($runner);

    expect($refresher)->toBeInstanceOf(LivewireWorkbenchRefresher::class);
});

it('skips refresh when the project is not a git checkout', function (): void {
    $projectRoot = tailorWorkbenchRunnerTemporaryPath('no-git');
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
        deleteTailorWorkbenchRunnerTemporaryPath($projectRoot);
    }
});

it('refreshes and builds when the project is a git checkout', function (): void {
    $projectRoot = tailorWorkbenchRunnerTemporaryPath('git');
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
        deleteTailorWorkbenchRunnerTemporaryPath($projectRoot);
    }
});

it('returns a failure code when refreshing the workbench throws', function (): void {
    $projectRoot = tailorWorkbenchRunnerTemporaryPath('refresh-failure');
    $runner = new class(new Filesystem) extends WorkbenchRefreshRunner
    {
        public bool $built = false;

        protected function refreshWorkbench(string $projectRoot): void
        {
            throw new RuntimeException('Refresh failed.');
        }

        protected function buildWorkbench(string $projectRoot): void
        {
            $this->built = true;
        }
    };

    try {
        mkdir($projectRoot.DIRECTORY_SEPARATOR.'.git', 0777, true);

        expect($runner->run($projectRoot))->toBe(1);
        expect($runner->built)->toBeFalse();
    } finally {
        deleteTailorWorkbenchRunnerTemporaryPath($projectRoot);
    }
});

it('returns a failure code when building the workbench throws', function (): void {
    $projectRoot = tailorWorkbenchRunnerTemporaryPath('build-failure');
    $runner = new class(new Filesystem) extends WorkbenchRefreshRunner
    {
        public bool $refreshed = false;

        protected function refreshWorkbench(string $projectRoot): void
        {
            $this->refreshed = true;
        }

        protected function buildWorkbench(string $projectRoot): void
        {
            throw new RuntimeException('Build failed.');
        }
    };

    try {
        mkdir($projectRoot.DIRECTORY_SEPARATOR.'.git', 0777, true);

        expect($runner->run($projectRoot))->toBe(1);
        expect($runner->refreshed)->toBeTrue();
    } finally {
        deleteTailorWorkbenchRunnerTemporaryPath($projectRoot);
    }
});

it('refreshes the workbench directory beneath the project root', function (): void {
    $projectRoot = tailorWorkbenchRunnerTemporaryPath('refresh-path');
    $refresher = new class(new Filesystem) extends LivewireWorkbenchRefresher
    {
        public ?string $workbenchPath = null;

        public function refresh(string $workbenchPath, ?string $sourcePath = null): void
        {
            $this->workbenchPath = $workbenchPath;
        }
    };
    $runner = new class(new Filesystem, $refresher) extends WorkbenchRefreshRunner {};

    (new ReflectionMethod(WorkbenchRefreshRunner::class, 'refreshWorkbench'))->invoke($runner, $projectRoot);

    expect($refresher->workbenchPath)->toBe($projectRoot.DIRECTORY_SEPARATOR.'workbench');
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

it('runs the testbench skeleton build from the project root', function (): void {
    $projectRoot = tailorWorkbenchRunnerTemporaryPath('skeleton-build');
    $markerPath = $projectRoot.DIRECTORY_SEPARATOR.'workbench-built.txt';
    $runner = new class(new Filesystem) extends WorkbenchRefreshRunner {};

    try {
        mkdir($projectRoot.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'bin', 0777, true);
        (new Filesystem)->put(
            $projectRoot.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'testbench',
            "<?php\nfile_put_contents(".var_export($markerPath, true).", getcwd());\n"
        );

        (new ReflectionMethod(WorkbenchRefreshRunner::class, 'buildWorkbenchSkeleton'))->invoke($runner, $projectRoot);

        expect((new Filesystem)->get($markerPath))->toBe($projectRoot);
    } finally {
        deleteTailorWorkbenchRunnerTemporaryPath($projectRoot);
    }
});

it('surfaces testbench skeleton build failures from stderr', function (): void {
    $projectRoot = tailorWorkbenchRunnerTemporaryPath('skeleton-failure');
    $runner = new class(new Filesystem) extends WorkbenchRefreshRunner {};

    try {
        mkdir($projectRoot.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'bin', 0777, true);
        (new Filesystem)->put(
            $projectRoot.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'testbench',
            "<?php\nfwrite(STDERR, 'Skeleton build failed.');\nexit(1);\n"
        );

        expect(fn () => (new ReflectionMethod(WorkbenchRefreshRunner::class, 'buildWorkbenchSkeleton'))->invoke($runner, $projectRoot))
            ->toThrow(RuntimeException::class, 'Skeleton build failed.');
    } finally {
        deleteTailorWorkbenchRunnerTemporaryPath($projectRoot);
    }
});

it('falls back to stdout when the testbench skeleton build has no stderr output', function (): void {
    $projectRoot = tailorWorkbenchRunnerTemporaryPath('skeleton-stdout-failure');
    $runner = new class(new Filesystem) extends WorkbenchRefreshRunner {};

    try {
        mkdir($projectRoot.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'bin', 0777, true);
        (new Filesystem)->put(
            $projectRoot.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'testbench',
            "<?php\necho 'Skeleton build stdout failure.';\nexit(1);\n"
        );

        expect(fn () => (new ReflectionMethod(WorkbenchRefreshRunner::class, 'buildWorkbenchSkeleton'))->invoke($runner, $projectRoot))
            ->toThrow(RuntimeException::class, 'Skeleton build stdout failure.');
    } finally {
        deleteTailorWorkbenchRunnerTemporaryPath($projectRoot);
    }
});

it('skips npm installation and asset builds when the workbench has no package manifest', function (): void {
    $projectRoot = tailorWorkbenchRunnerTemporaryPath('no-package');
    $runner = new class(new Filesystem) extends WorkbenchRefreshRunner
    {
        public array $commands = [];

        protected function resolveNpmCommand(): array
        {
            return ['npm'];
        }

        protected function runWorkbenchProcess(string $projectRoot, array $command): void
        {
            $this->commands[] = [
                'projectRoot' => $projectRoot,
                'command' => $command,
            ];
        }
    };

    try {
        mkdir($projectRoot.DIRECTORY_SEPARATOR.'workbench', 0777, true);

        (new ReflectionMethod(WorkbenchRefreshRunner::class, 'installWorkbenchDependencies'))->invoke($runner, $projectRoot);
        (new ReflectionMethod(WorkbenchRefreshRunner::class, 'buildWorkbenchAssets'))->invoke($runner, $projectRoot);

        expect($runner->commands)->toBe([]);
    } finally {
        deleteTailorWorkbenchRunnerTemporaryPath($projectRoot);
    }
});

it('runs the expected npm commands when the workbench has a package manifest', function (): void {
    $projectRoot = tailorWorkbenchRunnerTemporaryPath('package');
    $runner = new class(new Filesystem) extends WorkbenchRefreshRunner
    {
        public array $commands = [];

        protected function resolveNpmCommand(): array
        {
            return ['npm'];
        }

        protected function runWorkbenchProcess(string $projectRoot, array $command): void
        {
            $this->commands[] = [
                'projectRoot' => $projectRoot,
                'command' => $command,
            ];
        }
    };

    try {
        mkdir($projectRoot.DIRECTORY_SEPARATOR.'workbench', 0777, true);
        (new Filesystem)->put($projectRoot.DIRECTORY_SEPARATOR.'workbench'.DIRECTORY_SEPARATOR.'package.json', "{}\n");

        (new ReflectionMethod(WorkbenchRefreshRunner::class, 'installWorkbenchDependencies'))->invoke($runner, $projectRoot);
        (new ReflectionMethod(WorkbenchRefreshRunner::class, 'buildWorkbenchAssets'))->invoke($runner, $projectRoot);

        expect($runner->commands)->toBe([
            [
                'projectRoot' => $projectRoot,
                'command' => ['npm', 'install', '--no-audit', '--no-fund', '--package-lock=false'],
            ],
            [
                'projectRoot' => $projectRoot,
                'command' => ['npm', 'run', 'build'],
            ],
        ]);
    } finally {
        deleteTailorWorkbenchRunnerTemporaryPath($projectRoot);
    }
});

it('fails when npm cannot be found on PATH', function (): void {
    $runner = new class(new Filesystem) extends WorkbenchRefreshRunner {};

    $originalPath = getenv('PATH');
    $originalServerPath = $_SERVER['PATH'] ?? null;
    $originalEnvPath = $_ENV['PATH'] ?? null;

    try {
        putenv('PATH=');
        unset($_SERVER['PATH'], $_ENV['PATH']);

        expect(fn () => (new ReflectionMethod(WorkbenchRefreshRunner::class, 'resolveNpmCommand'))->invoke($runner))
            ->toThrow(RuntimeException::class, 'Unable to build workbench assets because npm could not be found on PATH.');
    } finally {
        $originalPath === false ? putenv('PATH') : putenv('PATH='.$originalPath);

        if ($originalServerPath === null) {
            unset($_SERVER['PATH']);
        } else {
            $_SERVER['PATH'] = $originalServerPath;
        }

        if ($originalEnvPath === null) {
            unset($_ENV['PATH']);
        } else {
            $_ENV['PATH'] = $originalEnvPath;
        }
    }
});

it('prefers stderr when a workbench process fails', function (): void {
    $projectRoot = tailorWorkbenchRunnerTemporaryPath('stderr');
    $scriptPath = $projectRoot.DIRECTORY_SEPARATOR.'workbench'.DIRECTORY_SEPARATOR.'fail.php';
    $runner = new class(new Filesystem) extends WorkbenchRefreshRunner {};

    try {
        mkdir($projectRoot.DIRECTORY_SEPARATOR.'workbench', 0777, true);
        (new Filesystem)->put($scriptPath, "<?php\nfwrite(STDERR, 'stderr failure');\nexit(1);\n");

        expect(fn () => (new ReflectionMethod(WorkbenchRefreshRunner::class, 'runWorkbenchProcess'))->invoke($runner, $projectRoot, [PHP_BINARY, $scriptPath]))
            ->toThrow(RuntimeException::class, 'stderr failure');
    } finally {
        deleteTailorWorkbenchRunnerTemporaryPath($projectRoot);
    }
});

it('falls back to stdout when stderr is empty', function (): void {
    $projectRoot = tailorWorkbenchRunnerTemporaryPath('stdout');
    $scriptPath = $projectRoot.DIRECTORY_SEPARATOR.'workbench'.DIRECTORY_SEPARATOR.'fail.php';
    $runner = new class(new Filesystem) extends WorkbenchRefreshRunner {};

    try {
        mkdir($projectRoot.DIRECTORY_SEPARATOR.'workbench', 0777, true);
        (new Filesystem)->put($scriptPath, "<?php\necho 'stdout failure';\nexit(1);\n");

        expect(fn () => (new ReflectionMethod(WorkbenchRefreshRunner::class, 'runWorkbenchProcess'))->invoke($runner, $projectRoot, [PHP_BINARY, $scriptPath]))
            ->toThrow(RuntimeException::class, 'stdout failure');
    } finally {
        deleteTailorWorkbenchRunnerTemporaryPath($projectRoot);
    }
});
