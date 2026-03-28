<?php

use Illuminate\Testing\PendingCommand;
use Onelegstudios\Tailor\Enums\InstallFeature;

use function Pest\Laravel\artisan;

it('dispatches the selected feature commands', function (): void {
    $command = artisan('tailor:install');

    assert($command instanceof PendingCommand);

    $command->expectsChoice(
        'Which starter kit features would you like to tailor?',
        ['useLucideIcons'],
        InstallFeature::options(),
    )
        ->expectsOutput('Configured Lucide icons.')
        ->expectsOutput('Tailor install complete.')
        ->assertExitCode(0);
});

it('exits cleanly when no features are selected', function (): void {
    $command = artisan('tailor:install');

    assert($command instanceof PendingCommand);

    $command->expectsChoice(
        'Which starter kit features would you like to tailor?',
        [],
        InstallFeature::options(),
    )
        ->expectsOutput('No features selected.')
        ->doesntExpectOutput('Tailor install complete.')
        ->assertExitCode(0);
});
