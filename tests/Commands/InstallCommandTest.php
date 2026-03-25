<?php

use Onelegstudios\Tailor\Enums\InstallFeature;

use function Pest\Laravel\artisan;

it('dispatches the selected feature commands', function (): void {
    artisan('tailor:install')
        ->expectsChoice(
            'Which starter kit features would you like to tailor?',
            ['authentication', 'billing'],
            InstallFeature::options(),
        )
        ->expectsOutput('Configured the dummy authentication feature.')
        ->expectsOutput('Configured the dummy billing feature.')
        ->doesntExpectOutput('Configured the dummy API feature.')
        ->doesntExpectOutput('Configured the dummy teams feature.')
        ->expectsOutput('Tailor install complete.')
        ->assertExitCode(0);
});

it('exits cleanly when no features are selected', function (): void {
    artisan('tailor:install')
        ->expectsChoice(
            'Which starter kit features would you like to tailor?',
            [],
            InstallFeature::options(),
        )
        ->expectsOutput('No features selected.')
        ->doesntExpectOutput('Tailor install complete.')
        ->assertExitCode(0);
});
