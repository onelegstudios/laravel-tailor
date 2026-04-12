<?php

namespace Onelegstudios\Tailor\Tests;

use Flux\FluxServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Livewire\LivewireServiceProvider;
use Onelegstudios\Tailor\TailorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(self::resolveFactoryName(...));
    }

    private static function resolveFactoryName(string $modelName): string
    {
        $appFactory = 'Database\\Factories\\'.class_basename($modelName).'Factory';

        if (class_exists($appFactory)) {
            return $appFactory;
        }

        return 'Onelegstudios\\Tailor\\Database\\Factories\\'.class_basename($modelName).'Factory';
    }

    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            FluxServiceProvider::class,
            TailorServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('session.driver', 'array');

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }
}
