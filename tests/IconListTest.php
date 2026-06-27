<?php

use Livewire\Livewire;

beforeEach(function () {
    config()->set('tailor.icons', [
        'starter-kit' => [
            'heroicons' => [
                'magnifying-glass' => 'search',
                'home' => 'house',
                'trash' => '',
            ],
            'lucide' => [
                'layout-grid' => 'layout-grid',
            ],
        ],
        'flux' => [
            'normal' => [
                'calendar' => 'calendar',
            ],
        ],
    ]);
});

it('lists every configured icon with its original and replacement', function () {
    Livewire::test('tailor::icon-list')
        ->assertOk()
        ->assertSee('magnifying-glass')
        ->assertSee('search')
        ->assertSee('house')
        ->assertSee('layout-grid')
        ->assertSee('calendar')
        ->assertSee('5 icons');
});

it('renders the original heroicon glyph and the lucide replacement glyph', function () {
    Livewire::test('tailor::icon-list')
        ->assertSeeHtml('data-hero="magnifying-glass"')
        ->assertSeeHtml('data-lucide="search"')
        ->assertSeeHtml('data-lucide="layout-grid"');
});

it('renders an animated spinner for flux animated icons', function () {
    config()->set('tailor.icons', [
        'flux' => [
            'animated' => ['loading' => 'loader-circle'],
        ],
    ]);

    Livewire::test('tailor::icon-list')
        ->assertSee('loading')
        ->assertSeeHtml('tailor-spinner')                // original Flux loading glyph
        ->assertSeeHtml('data-lucide="loader-circle"');  // lucide replacement
});

it('marks icons without a Lucide mapping', function () {
    Livewire::test('tailor::icon-list')
        ->assertSee('trash')
        ->assertSee('no mapping');
});

it('filters icons by original or replacement name', function () {
    Livewire::test('tailor::icon-list')
        ->set('search', 'house')
        ->assertSee('home')
        ->assertSee('house')
        ->assertDontSee('calendar')
        ->assertDontSee('magnifying-glass');
});

it('shows an empty state when nothing matches', function () {
    Livewire::test('tailor::icon-list')
        ->set('search', 'definitely-not-an-icon')
        ->assertSee('No icons match');
});
