<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\get;
use function Pest\Laravel\withoutVite;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    withoutVite();
});

test('preview pages render the development layout', function (): void {
    get(route('dev.icon-map'))
        ->assertOk()
        ->assertSee('Development Preview')
        ->assertSee('Developer previews are available only in local and testing environments.')
        ->assertSee('bg-zinc-500 text-white shadow-sm shadow-zinc-500/30 dark:bg-zinc-400 dark:text-zinc-950 dark:shadow-zinc-400/20', false)
        ->assertDontSee('bg-sky-500 text-white shadow-sm shadow-sky-500/30 dark:bg-sky-400 dark:text-zinc-950 dark:shadow-sky-400/20', false);
});
