<?php

it('has the starter-kit fixture for each variant', function (string $variant) {
    $path = __DIR__."/fixtures/starter-kits/{$variant}";

    expect(is_dir($path))->toBeTrue();
    expect(file_exists("{$path}/composer.json"))->toBeTrue();
    expect(is_dir("{$path}/resources/views"))->toBeTrue();
})->with(['main', 'components', 'teams']);
