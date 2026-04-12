<?php

namespace Onelegstudios\Tailor;

use Livewire\Livewire;
use Onelegstudios\Tailor\Commands\InstallCommand;
use Onelegstudios\Tailor\Commands\UseLucideIconsCommand;
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
            ->hasRoute('web')
            ->hasViews()
            ->hasMigration('create_laravel_tailor_table')
            ->hasCommand(InstallCommand::class)
            ->hasCommand(UseLucideIconsCommand::class);
    }

    public function packageBooted(): void
    {
        if (! class_exists(Livewire::class) || ! app()->bound('livewire.finder')) {
            return;
        }

        Livewire::addNamespace('tailor-pages', dirname(__DIR__).'/resources/views/pages');
    }
}
