<?php

namespace Onelegstudios\Tailor\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Onelegstudios\Tailor\TailorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(self::resolveFactoryName(...));
    }

    /**
     * @param  class-string<Model>  $modelName
     * @return class-string<Factory<Model>>
     */
    private static function resolveFactoryName(string $modelName): string
    {
        /** @var class-string<Factory<Model>> $factoryName */
        $factoryName = 'Onelegstudios\\Tailor\\Database\\Factories\\'.class_basename($modelName).'Factory';

        return $factoryName;
    }

    protected function getPackageProviders($app)
    {
        return [
            TailorServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }
}
