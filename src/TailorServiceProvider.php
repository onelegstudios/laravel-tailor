<?php

namespace Onelegstudios\Tailor;

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
            ->hasCommand(TailorCommand::class);
    }
}
