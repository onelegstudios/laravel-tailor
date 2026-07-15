<?php

use Onelegstudios\Tailor\Kits\AsIsKit;

it('is registered as the as-is UI kit', function () {
    $kit = app(AsIsKit::class);

    expect($kit->key())->toBe('as-is')
        ->and($kit->label())->toBe('Flux with mixed icons');
});

it('leaves the starter kit untouched and reports no failures', function () {
    expect(app(AsIsKit::class)->apply())->toBe([]);
});
