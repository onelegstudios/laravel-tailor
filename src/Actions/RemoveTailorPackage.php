<?php

namespace Onelegstudios\Tailor\Actions;

use Illuminate\Console\OutputStyle;
use Illuminate\Support\Composer;

class RemoveTailorPackage
{
    /**
     * The Composer package name for Tailor.
     */
    public const PACKAGE = 'onelegstudios/laravel-tailor';

    public function __construct(
        private readonly Composer $composer,
    ) {}

    /**
     * Remove the Tailor package from the application as a dev dependency via
     * Composer, streaming Composer's output as it runs.
     *
     * @return bool whether the package was removed successfully
     */
    public function execute(?OutputStyle $output = null): bool
    {
        $output?->writeln('<info>Removing '.self::PACKAGE.' via Composer...</info>');

        $removed = $this->composer->removePackages([self::PACKAGE], dev: true, output: $output);

        if (! $removed) {
            $output?->writeln('<fg=red>⚠ Could not remove '.self::PACKAGE.' automatically. Remove it manually with: composer remove '.self::PACKAGE.' --dev</>');
        }

        return $removed;
    }
}
