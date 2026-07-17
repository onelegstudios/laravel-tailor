<?php

use Onelegstudios\Tailor\Actions\RemoveTailorPackage;

$root = dirname(__DIR__, 2);

// The self-uninstall hands this name to Composer, so a rename of the package
// that misses the constant leaves `tailor` removing something the app never
// installed. The action's own tests assert against the constant and so can't
// catch that; pin it to composer.json instead.
it('names the package the way composer.json does', function () use ($root) {
    $name = json_decode(file_get_contents($root.'/composer.json'), true)['name'];

    expect(RemoveTailorPackage::PACKAGE)->toBe($name);
});
