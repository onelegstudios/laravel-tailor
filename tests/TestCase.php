<?php

namespace Onelegstudios\Tailor\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Filesystem\Filesystem;
use Livewire\LivewireServiceProvider;
use Onelegstudios\Tailor\TailorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * Relocate the application's base path to a unique temporary directory, so
     * that filesystem-mutating tests operate on their own app/ and resources/
     * trees rather than the Testbench skeleton every worker shares.
     *
     * Under parallel testing each test file runs in its own process but they
     * all resolve app_path()/resource_path() to the same skeleton on disk, so
     * two files writing to and deleting the same directory race and fail
     * intermittently. Pointing the base path at a per-test temp directory keeps
     * those writes isolated. The caller must delete the returned path in
     * afterEach.
     */
    protected function isolateApplicationPaths(): string
    {
        $base = sys_get_temp_dir().'/tailor-app-'.uniqid('', true);

        $files = new Filesystem;

        // ReplaceIcons scans resource_path('views') via Finder, which throws on
        // a missing directory, so seed it before we relocate.
        $files->ensureDirectoryExists($base.'/resources/views');

        // GeneratorCommand derives the app namespace from composer.json; copy the
        // skeleton's so make:tailor-* still resolves the App\ namespace.
        $files->copy($this->app->basePath('composer.json'), $base.'/composer.json');

        $this->app->setBasePath($base);

        return $base;
    }

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Onelegstudios\\Tailor\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            TailorServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }
}
