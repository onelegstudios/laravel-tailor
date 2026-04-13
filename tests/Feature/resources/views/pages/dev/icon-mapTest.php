<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\get;
use function Pest\Laravel\withoutVite;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    withoutVite();
});

test('guests can view the package icon map page', function (): void {
    get(route('dev.icon-map'))
        ->assertOk()
        ->assertSee('Icon Set Comparison')
        ->assertSee('arrow-path')
        ->assertSee('refresh-cw')
        ->assertSee('Heroicons package')
        ->assertSee('data-lucide="refresh-cw"', false);
});

test('icon map page component renders icon mappings', function (): void {
    Livewire::test('tailor::pages.dev.icon-map')
        ->assertSee('Icon Set Comparison')
        ->assertSee('arrow-path')
        ->assertSee('refresh-cw')
        ->assertSee('Heroicons package')
        ->assertSee('data-lucide="refresh-cw"', false);
});

test('icon map page component uses runtime config mappings', function (): void {
    config([
        'tailor.icons.mappings' => [
            'arrow-path' => 'from-config-repository',
        ],
    ]);

    Livewire::test('tailor::pages.dev.icon-map')
        ->assertSee('arrow-path')
        ->assertSee('from-config-repository')
        ->assertSee('data-lucide="from-config-repository"', false)
        ->assertDontSee('refresh-cw');
});

test('icon map page sorts icon mappings alphabetically by key', function (): void {
    config([
        'tailor.icons.mappings' => [
            'cog' => 'settings',
            'check' => 'check',
            'book-open-text' => 'book-open-text',
        ],
    ]);

    Livewire::test('tailor::pages.dev.icon-map')
        ->assertSet('icons.0.key', 'book-open-text')
        ->assertSet('icons.1.key', 'check')
        ->assertSet('icons.2.key', 'cog');
});

test('icon map page keeps the card layout through extra large screens', function (): void {
    get(route('dev.icon-map'))
        ->assertOk()
        ->assertSee('data-test="icon-map-mobile-cards"', false)
        ->assertSee('class="grid gap-4 p-4 sm:p-6 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 2xl:hidden"', false)
        ->assertSee('data-test="icon-map-desktop-table"', false)
        ->assertSee('class="hidden 2xl:block"', false)
        ->assertSee('wire:key="icon-map-card-arrow-path"', false)
        ->assertSee('wire:key="icon-map-table-arrow-path"', false);
});

test('icon map page initializes lucide previews once per page visit', function (): void {
    get(route('dev.icon-map'))
        ->assertOk()
        ->assertSee('script data-navigate-once src="https://unpkg.com/lucide@0.511.0/dist/umd/lucide.min.js"', false)
        ->assertSee("document.addEventListener('livewire:navigated', renderTailorLucideIcons, { once: true });", false)
        ->assertDontSee('renderTailorLucideIcons();', false)
        ->assertDontSee("document.addEventListener('livewire:navigated', renderTailorLucideIcons);", false);
});
