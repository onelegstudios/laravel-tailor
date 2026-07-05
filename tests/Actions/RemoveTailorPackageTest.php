<?php

use Illuminate\Support\Composer;
use Onelegstudios\Tailor\Actions\RemoveTailorPackage;

it('removes the tailor package as a dev dependency via composer', function () {
    $composer = Mockery::mock(Composer::class);
    $composer->shouldReceive('removePackages')
        ->once()
        ->with([RemoveTailorPackage::PACKAGE], true, null)
        ->andReturnTrue();

    $action = new RemoveTailorPackage($composer);

    expect($action->execute())->toBeTrue();
});

it('reports failure when composer cannot remove the package', function () {
    $composer = Mockery::mock(Composer::class);
    $composer->shouldReceive('removePackages')
        ->once()
        ->with([RemoveTailorPackage::PACKAGE], true, null)
        ->andReturnFalse();

    $action = new RemoveTailorPackage($composer);

    expect($action->execute())->toBeFalse();
});
