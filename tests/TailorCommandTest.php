<?php

it('asks about the UI kit first, then the remaining options', function () {
    $this->artisan('tailor')
        ->expectsChoice('What UI kit do you want to use?', 'lucide', [
            'hero' => 'Flux with Heroicons',
            'lucide' => 'Flux with Lucide Icons',
            'tall-stack' => 'Tall Stack UI',
        ])
        ->expectsChoice('What else would you like to tailor?', ['move_auth'], [
            'move_auth' => 'Move the auth folder',
        ])
        ->assertSuccessful();
});

it('defaults the UI kit to Flux with Heroicons', function () {
    $this->artisan('tailor')
        ->expectsChoice('What UI kit do you want to use?', 'hero', [
            'hero' => 'Flux with Heroicons',
            'lucide' => 'Flux with Lucide Icons',
            'tall-stack' => 'Tall Stack UI',
        ])
        ->expectsChoice('What else would you like to tailor?', [], [
            'move_auth' => 'Move the auth folder',
        ])
        ->assertSuccessful();
});
