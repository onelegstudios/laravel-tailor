<?php

namespace Onelegstudios\Tailor;

use Livewire\Livewire;
use Onelegstudios\Tailor\Commands\MakeKitCommand;
use Onelegstudios\Tailor\Commands\MakeTaskCommand;
use Onelegstudios\Tailor\Commands\TailorCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TailorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-tailor')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_tailor_table')
            ->hasCommands([
                TailorCommand::class,
                MakeKitCommand::class,
                MakeTaskCommand::class,
            ]);
    }

    public function packageBooted(): void
    {
        // Resolve the package's single-file page components (e.g. the icon
        // reference page at resources/views/pages) under the "tailor" namespace.
        Livewire::addNamespace('tailor', viewPath: __DIR__.'/../resources/views/pages');

        // The icon reference page is a local-development aid only; never expose
        // its route in any other environment.
        if ($this->app->environment('local')) {
            $this->app->booted(function (): void {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });
        }
    }
}
