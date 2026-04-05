<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Onelegstudios\Tailor\Support\LivewireWorkbenchRefresher;

final class LivewireWorkbenchRefresherCommandHarness extends LivewireWorkbenchRefresher
{
    public array $commands = [];

    public function __construct(
        Filesystem $files,
        protected ?string $laravelBinary = null,
        protected ?string $composerBinary = null,
    ) {
        parent::__construct($files);
    }

    public function generateStarterKitForTest(string $temporaryPath): string
    {
        return $this->generateStarterKit($temporaryPath);
    }

    protected function resolveLaravelBinary(): ?string
    {
        return $this->laravelBinary;
    }

    protected function resolveComposerBinary(): ?string
    {
        return $this->composerBinary;
    }

    protected function runProcess(array $command): void
    {
        $this->commands[] = $command;
    }
}

it('refreshes a workbench target from a provided livewire starter kit source', function (): void {
    $sandboxPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'tailor-refresh-workbench-'.Str::uuid();
    $sourcePath = $sandboxPath.DIRECTORY_SEPARATOR.'source';
    $workbenchPath = $sandboxPath.DIRECTORY_SEPARATOR.'workbench';

    try {
        File::ensureDirectoryExists($sourcePath.DIRECTORY_SEPARATOR.'app/Providers');
        File::ensureDirectoryExists($sourcePath.DIRECTORY_SEPARATOR.'bootstrap/cache');
        File::ensureDirectoryExists($sourcePath.DIRECTORY_SEPARATOR.'config');
        File::ensureDirectoryExists($sourcePath.DIRECTORY_SEPARATOR.'database/factories');
        File::ensureDirectoryExists($sourcePath.DIRECTORY_SEPARATOR.'database/seeders');
        File::ensureDirectoryExists($sourcePath.DIRECTORY_SEPARATOR.'public');
        File::ensureDirectoryExists($sourcePath.DIRECTORY_SEPARATOR.'resources/views');
        File::ensureDirectoryExists($sourcePath.DIRECTORY_SEPARATOR.'routes');
        File::ensureDirectoryExists($sourcePath.DIRECTORY_SEPARATOR.'storage/framework/views');
        File::ensureDirectoryExists($sourcePath.DIRECTORY_SEPARATOR.'vendor');

        File::put($sourcePath.DIRECTORY_SEPARATOR.'composer.json', <<<'JSON'
{
    "name": "laravel/livewire-starter-kit"
}
JSON);
        File::put($sourcePath.DIRECTORY_SEPARATOR.'artisan', "#!/usr/bin/env php\n<?php\n");
        File::put($sourcePath.DIRECTORY_SEPARATOR.'bootstrap/app.php', "<?php\n");
        File::put($sourcePath.DIRECTORY_SEPARATOR.'bootstrap/providers.php', "<?php\n\nreturn [\n    App\\Providers\\AppServiceProvider::class,\n];\n");
        File::put($sourcePath.DIRECTORY_SEPARATOR.'config/fortify.php', "<?php\n\nreturn [];\n");
        File::put($sourcePath.DIRECTORY_SEPARATOR.'routes/web.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::view('/', 'welcome');\n");
        File::put($sourcePath.DIRECTORY_SEPARATOR.'resources/views/welcome.blade.php', '<div>starter kit</div>');
        File::put($sourcePath.DIRECTORY_SEPARATOR.'database/seeders/DatabaseSeeder.php', "<?php\n\nnamespace Database\\Seeders;\n\nclass DatabaseSeeder {}\n");
        File::put($sourcePath.DIRECTORY_SEPARATOR.'database/factories/UserFactory.php', "<?php\n\nnamespace Database\\Factories;\n\nclass UserFactory {}\n");
        File::put($sourcePath.DIRECTORY_SEPARATOR.'public/favicon.ico', 'icon');
        File::put($sourcePath.DIRECTORY_SEPARATOR.'.env.example', "APP_NAME=Tailor\n");
        File::put($sourcePath.DIRECTORY_SEPARATOR.'vendor/autoload.php', "<?php\n");

        File::ensureDirectoryExists($workbenchPath.DIRECTORY_SEPARATOR.'app/Providers');
        File::put($workbenchPath.DIRECTORY_SEPARATOR.'app/Providers/WorkbenchServiceProvider.php', "<?php\n");

        $refresher = new LivewireWorkbenchRefresher(new Filesystem);
        $refresher->refresh(
            workbenchPath: $workbenchPath,
            sourcePath: $sourcePath,
        );

        expect(File::exists($workbenchPath.DIRECTORY_SEPARATOR.'app/Providers/WorkbenchServiceProvider.php'))->toBeFalse();
        expect(File::get($workbenchPath.DIRECTORY_SEPARATOR.'bootstrap/providers.php'))->toContain('App\\Providers\\AppServiceProvider::class');
        expect(File::get($workbenchPath.DIRECTORY_SEPARATOR.'bootstrap/providers.php'))->toContain('Livewire\\LivewireServiceProvider::class');
        expect(File::get($workbenchPath.DIRECTORY_SEPARATOR.'bootstrap/providers.php'))->toContain('Livewire\\Flux\\FluxServiceProvider::class');
        expect(File::get($workbenchPath.DIRECTORY_SEPARATOR.'resources/views/welcome.blade.php'))->toBe('<div>starter kit</div>');
        expect(File::exists($workbenchPath.DIRECTORY_SEPARATOR.'.env.example'))->toBeTrue();
        expect(File::exists($workbenchPath.DIRECTORY_SEPARATOR.'storage/framework/views'))->toBeTrue();
        expect(File::exists($workbenchPath.DIRECTORY_SEPARATOR.'vendor/autoload.php'))->toBeFalse();
    } finally {
        File::deleteDirectory($sandboxPath);
    }
});

it('generates the starter kit with team support when the Laravel installer is available', function (): void {
    $temporaryPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'tailor-refresh-workbench-'.Str::uuid();
    $starterKitPath = $temporaryPath.DIRECTORY_SEPARATOR.'starter-kit';
    $refresher = new LivewireWorkbenchRefresherCommandHarness(
        new Filesystem,
        laravelBinary: 'laravel',
    );

    expect($refresher->generateStarterKitForTest($temporaryPath))->toBe($starterKitPath);
    expect($refresher->commands)->toBe([[
        'laravel',
        'new',
        $starterKitPath,
        '--livewire',
        '--teams',
        '--no-interaction',
        '--quiet',
    ]]);
});

it('fails when the provided source path is invalid', function (): void {
    $refresher = new LivewireWorkbenchRefresher(new Filesystem);

    expect(fn () => $refresher->refresh(
        workbenchPath: sys_get_temp_dir().DIRECTORY_SEPARATOR.'unused-workbench',
        sourcePath: sys_get_temp_dir().DIRECTORY_SEPARATOR.'missing-livewire-source',
    ))->toThrow(
        InvalidArgumentException::class,
        'The provided starter kit source path does not exist.',
    );
});
