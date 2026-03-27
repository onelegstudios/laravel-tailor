<?php
namespace Onelegstudios\Tailor\Support;

use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class LivewireWorkbenchRefresher
{
    /**
     * @var array<int, string>
     */
    protected array $excludedPaths = [
        '.git',
        'node_modules',
        'vendor',
    ];

    public function __construct(
        protected Filesystem $files,
    ) {}

    public function refresh(string $workbenchPath, ?string $sourcePath = null): void
    {
        $temporaryPath = null;

        try {
            $resolvedSourcePath = $sourcePath !== null
                ? $this->validateSourcePath($sourcePath)
                : $this->generateStarterKit($temporaryPath = $this->makeTemporaryPath());

            $this->guardWorkbenchPath($workbenchPath);

            $this->files->deleteDirectory($workbenchPath);
            $this->files->ensureDirectoryExists($workbenchPath);

            $this->mirrorStarterKit($resolvedSourcePath, $workbenchPath);
            $this->ensureTestbenchProviders($workbenchPath);
        } finally {
            if ($temporaryPath !== null) {
                $this->files->deleteDirectory($temporaryPath);
            }
        }
    }

    protected function makeTemporaryPath(): string
    {
        $temporaryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tailor-livewire-workbench-' . bin2hex(random_bytes(8));

        $this->files->ensureDirectoryExists($temporaryPath);

        return $temporaryPath;
    }

    protected function generateStarterKit(string $temporaryPath): string
    {
        $starterKitPath = $temporaryPath . DIRECTORY_SEPARATOR . 'starter-kit';
        $laravelBinary  = (new ExecutableFinder)->find('laravel');

        if ($laravelBinary !== null) {
            $this->runProcess([
                $laravelBinary,
                'new',
                $starterKitPath,
                '--livewire',
                '--no-interaction',
                '--quiet',
            ]);

            return $starterKitPath;
        }

        $composerBinary = (new ExecutableFinder)->find('composer');

        if ($composerBinary === null) {
            throw new RuntimeException('Neither the Laravel installer nor Composer could be found on PATH.');
        }

        $this->runProcess([
            $composerBinary,
            'create-project',
            'laravel/livewire-starter-kit',
            $starterKitPath,
            '--no-interaction',
            '--prefer-dist',
            '--quiet',
        ]);

        return $starterKitPath;
    }

    /**
     * @param  list<string>  $command
     */
    protected function runProcess(array $command): void
    {
        $process = new Process($command, dirname(__DIR__, 2));
        $process->setTimeout(null);
        $process->run();

        if ($process->isSuccessful()) {
            return;
        }

        $errorOutput = trim($process->getErrorOutput());
        $output      = trim($process->getOutput());

        throw new RuntimeException($errorOutput !== '' ? $errorOutput : $output);
    }

    protected function validateSourcePath(string $sourcePath): string
    {
        $resolvedPath = realpath($sourcePath);

        if ($resolvedPath === false || ! is_dir($resolvedPath)) {
            throw new InvalidArgumentException('The provided starter kit source path does not exist.');
        }

        foreach (['composer.json', 'bootstrap/app.php', 'routes/web.php'] as $requiredFile) {
            if (! $this->files->isFile($resolvedPath . DIRECTORY_SEPARATOR . $requiredFile)) {
                throw new InvalidArgumentException("The provided starter kit source is missing [{$requiredFile}].");
            }
        }

        return $resolvedPath;
    }

    protected function guardWorkbenchPath(string $workbenchPath): void
    {
        $trimmedPath = trim($workbenchPath);

        if ($trimmedPath === '' || $trimmedPath === DIRECTORY_SEPARATOR) {
            throw new InvalidArgumentException('Refusing to refresh an empty or root workbench path.');
        }
    }

    protected function mirrorStarterKit(string $sourcePath, string $workbenchPath): void
    {
        $directories = Finder::create()
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->directories()
            ->in($sourcePath)
            ->sortByName();

        foreach ($directories as $directory) {
            $relativePath = $directory->getRelativePathname();

            if ($this->shouldExclude($relativePath)) {
                continue;
            }

            $this->files->ensureDirectoryExists($workbenchPath . DIRECTORY_SEPARATOR . $relativePath);
        }

        $files = Finder::create()
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->files()
            ->in($sourcePath)
            ->sortByName();

        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();

            if ($this->shouldExclude($relativePath)) {
                continue;
            }

            $targetPath = $workbenchPath . DIRECTORY_SEPARATOR . $relativePath;

            $this->files->ensureDirectoryExists(dirname($targetPath));
            $this->files->copy($file->getRealPath(), $targetPath);

            if ($relativePath === 'artisan') {
                $this->files->chmod($targetPath, 0755);
            }
        }
    }

    protected function shouldExclude(string $relativePath): bool
    {
        $firstSegment = explode(DIRECTORY_SEPARATOR, $relativePath)[0];

        return in_array($firstSegment, $this->excludedPaths, true);
    }

    protected function ensureTestbenchProviders(string $workbenchPath): void
    {
        $providersPath = $workbenchPath . DIRECTORY_SEPARATOR . 'bootstrap/providers.php';

        if (! $this->files->isFile($providersPath)) {
            return;
        }

        $providers = $this->files->get($providersPath);

        foreach ([
            'Livewire\\LivewireServiceProvider::class,',
            'Livewire\\Flux\\FluxServiceProvider::class,',
        ] as $providerClass) {
            if (str_contains($providers, $providerClass)) {
                continue;
            }

            $providers = str_replace(
                '];',
                "    {$providerClass}\n];",
                $providers,
            );
        }

        $this->files->put($providersPath, $providers);
    }
}
