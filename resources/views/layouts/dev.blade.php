<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <title>
            {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
        </title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <flux:sidebar.brand name="Development Preview" href="{{ route('dev.icon-map') }}" wire:navigate>
                    <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-zinc-500 text-white shadow-sm shadow-zinc-500/30 dark:bg-zinc-400 dark:text-zinc-950 dark:shadow-zinc-400/20">
                        <flux:icon.beaker variant="mini" class="size-4.5" />
                    </x-slot>
                </flux:sidebar.brand>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group heading="Development" class="grid">
                    <flux:sidebar.item icon="swatch" :href="route('dev.icon-map')" :current="request()->routeIs('dev.icon-map')" wire:navigate>
                        Icon Set Comparison
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <div class="px-4 pb-4 text-xs leading-5 text-zinc-500 dark:text-zinc-400">
                Developer previews are available only in local and testing environments.
            </div>
        </flux:sidebar>

        <flux:header class="border-b border-zinc-200 bg-white/80 backdrop-blur lg:hidden dark:border-zinc-700 dark:bg-zinc-900/80">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <flux:brand name="Development Preview" href="{{ route('dev.icon-map') }}" class="ms-2" wire:navigate>
                <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-zinc-500 text-white shadow-sm shadow-zinc-500/30 dark:bg-zinc-400 dark:text-zinc-950 dark:shadow-zinc-400/20">
                    <flux:icon.beaker variant="mini" class="size-4.5" />
                </x-slot>
            </flux:brand>
            <flux:spacer />
        </flux:header>

        <flux:main class="min-h-screen">
            {{ $slot }}
        </flux:main>

        @fluxScripts
    </body>
</html>