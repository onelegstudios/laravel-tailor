<?php

use Illuminate\View\ComponentAttributeBag;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('tailor::layouts.dev'), Title('Icon Comparison')] class extends Component {
    /**
    * @var array<int, array{key: string, lucide: ?string, currentAppAvailable: bool, packageHeroiconHtml: ?string, renamed: bool}>
     */
    public array $icons = [];

    public int $renamedCount = 0;

    public int $sameNameCount = 0;

    public function mount(): void
    {
        $packageRoot = dirname((new \ReflectionClass(\Onelegstudios\Tailor\TailorServiceProvider::class))->getFileName(), 2);

        $icons = collect(config('tailor.icons.mappings', []))
            ->sortKeys()
            ->map(function (?string $lucide, string $key) use ($packageRoot): array {
                $currentAppAvailable = app()->bound('flux')
                    && \Flux\Flux::componentExists('icon.'.$key);

                $packageHeroiconView = collect([
                    base_path("vendor/livewire/flux/stubs/resources/views/flux/icon/{$key}.blade.php"),
                    $packageRoot."/vendor/livewire/flux/stubs/resources/views/flux/icon/{$key}.blade.php",
                ])->first(static fn (string $path): bool => is_file($path));

                $packageHeroiconHtml = null;

                if (is_string($packageHeroiconView)) {
                    $packageHeroiconHtml = view()->file($packageHeroiconView, [
                        'attributes' => new ComponentAttributeBag([
                            'class' => 'size-5 text-zinc-700 dark:text-zinc-200',
                        ]),
                        'variant' => 'outline',
                    ])->render();
                }

                return [
                    'key' => $key,
                    'lucide' => $lucide,
                    'currentAppAvailable' => $currentAppAvailable,
                    'packageHeroiconHtml' => $packageHeroiconHtml,
                    'renamed' => $lucide !== null && $lucide !== $key,
                ];
            })
            ->values()
            ->all();

        $this->icons = $icons;
        $this->renamedCount = collect($icons)->where('renamed', true)->count();
        $this->sameNameCount = collect($icons)->where('renamed', false)->count();
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">
                        {{ __('Development Preview') }}
                    </p>

                    <div class="space-y-1">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-2xl border border-zinc-200 bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800/80 dark:text-zinc-300">
                                <flux:icon name="cog" class="size-5" />
                            </span>

                            <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">
                                {{ __('Icon Set Comparison') }}
                            </h1>
                        </div>

                        <p class="max-w-3xl text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                            {{ __('Compare each icon key in config/tailor.php against the current Flux rendering, the package Heroicon when one exists, and the mapped Lucide target.') }}
                        </p>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
                    {{ __('Lucide previews are rendered in the browser from the Lucide CDN. If that column is blank, check the network connection in the browser.') }}
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/60">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Total') }}</p>
                    <p class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ count($icons) }}</p>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/60">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Renamed') }}</p>
                    <p class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ $renamedCount }}</p>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/60">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Same Name') }}</p>
                    <p class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ $sameNameCount }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-4 p-4 sm:p-6 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 2xl:hidden" data-test="icon-map-mobile-cards">
            @foreach ($icons as $icon)
                @php($currentIconName = $icon['key'])

                <article wire:key="icon-map-card-{{ $icon['key'] }}" class="h-full overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50/60 dark:border-zinc-800 dark:bg-zinc-950/40">
                    <div class="flex items-start justify-between gap-4 border-b border-zinc-200 px-4 py-4 dark:border-zinc-800">
                        <div class="space-y-1">
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Config key') }}</p>
                            <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $icon['key'] }}</h2>
                        </div>

                        <span @class([
                            'inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em]',
                            'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-200' => $icon['renamed'],
                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200' => ! $icon['renamed'],
                        ])>
                            {{ $icon['renamed'] ? __('Renamed') : __('Same') }}
                        </span>
                    </div>

                    <div class="grid gap-3 p-4">
                        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Current App') }}</p>

                            <div class="mt-3 flex items-center gap-3">
                                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                                    @if ($icon['currentAppAvailable'])
                                        <flux:icon :name="$currentIconName" class="size-5 text-zinc-700 dark:text-zinc-200" />
                                    @else
                                        <span class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-400">{{ __('N/A') }}</span>
                                    @endif
                                </span>

                                <div class="space-y-1">
                                    <p class="font-medium text-zinc-950 dark:text-white">{{ __('Flux preview') }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $icon['currentAppAvailable']
                                            ? __('This is what the app currently renders for the key.')
                                            : __('No current app icon component exists for this key.') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Package Heroicon') }}</p>

                            @if ($icon['packageHeroiconHtml'])
                                <div class="mt-3 flex items-center gap-3">
                                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                                        {!! $icon['packageHeroiconHtml'] !!}
                                    </span>

                                    <div class="space-y-1">
                                        <p class="font-medium text-zinc-950 dark:text-white">{{ __('Heroicons package') }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Vendor Flux icon without app overrides.') }}</p>
                                    </div>
                                </div>
                            @else
                                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No vendor Heroicon file for this key.') }}</p>
                            @endif
                        </div>

                        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('Lucide Target') }}</p>

                            <div class="mt-3 flex items-center gap-3">
                                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                                    @if ($icon['lucide'])
                                        <span data-lucide="{{ $icon['lucide'] }}" class="size-5 text-zinc-700 dark:text-zinc-200"></span>
                                    @else
                                        <span class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-400">{{ __('N/A') }}</span>
                                    @endif
                                </span>

                                <div class="space-y-1">
                                    <p class="font-medium text-zinc-950 dark:text-white">{{ $icon['lucide'] ?? __('Unmapped') }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Mapped Lucide value') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="hidden 2xl:block" data-test="icon-map-desktop-table">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50 dark:bg-zinc-950/60">
                        <tr class="text-left text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">
                            <th class="px-6 py-4">{{ __('Key') }}</th>
                            <th class="px-6 py-4">{{ __('Current App') }}</th>
                            <th class="px-6 py-4">{{ __('Package Heroicon') }}</th>
                            <th class="px-6 py-4">{{ __('Lucide Target') }}</th>
                            <th class="px-6 py-4">{{ __('Status') }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($icons as $icon)
                            @php($currentIconName = $icon['key'])

                            <tr wire:key="icon-map-table-{{ $icon['key'] }}" class="align-top">
                                <td class="px-6 py-5">
                                    <div class="space-y-1">
                                        <p class="font-semibold text-zinc-950 dark:text-white">{{ $icon['key'] }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Config key') }}</p>
                                    </div>
                                </td>

                                <td class="px-6 py-5">
                                    <div class="flex min-w-56 items-center gap-3">
                                        <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                                            @if ($icon['currentAppAvailable'])
                                                <flux:icon :name="$currentIconName" class="size-5 text-zinc-700 dark:text-zinc-200" />
                                            @else
                                                <span class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-400">{{ __('N/A') }}</span>
                                            @endif
                                        </span>

                                        <div class="space-y-1">
                                            <p class="font-medium text-zinc-950 dark:text-white">{{ __('Flux preview') }}</p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ $icon['currentAppAvailable']
                                                    ? __('This is what the app currently renders for the key.')
                                                    : __('No current app icon component exists for this key.') }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-5">
                                    @if ($icon['packageHeroiconHtml'])
                                        <div class="flex min-w-56 items-center gap-3">
                                            <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                                                {!! $icon['packageHeroiconHtml'] !!}
                                            </span>

                                            <div class="space-y-1">
                                                <p class="font-medium text-zinc-950 dark:text-white">{{ __('Heroicons package') }}</p>
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Vendor Flux icon without app overrides.') }}</p>
                                            </div>
                                        </div>
                                    @else
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No vendor Heroicon file for this key.') }}</p>
                                    @endif
                                </td>

                                <td class="px-6 py-5">
                                    <div class="flex min-w-56 items-center gap-3">
                                        <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                                            @if ($icon['lucide'])
                                                <span data-lucide="{{ $icon['lucide'] }}" class="size-5 text-zinc-700 dark:text-zinc-200"></span>
                                            @else
                                                <span class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-400">{{ __('N/A') }}</span>
                                            @endif
                                        </span>

                                        <div class="space-y-1">
                                            <p class="font-medium text-zinc-950 dark:text-white">{{ $icon['lucide'] ?? __('Unmapped') }}</p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Mapped Lucide value') }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-5">
                                    <span @class([
                                        'inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em]',
                                        'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-200' => $icon['renamed'],
                                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200' => ! $icon['renamed'],
                                    ])>
                                        {{ $icon['renamed'] ? __('Renamed') : __('Same') }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    @once
        <script data-navigate-once src="https://unpkg.com/lucide@0.511.0/dist/umd/lucide.min.js"></script>
        <script>
            (() => {
                const renderTailorLucideIcons = () => window.lucide?.createIcons();

                document.addEventListener('livewire:navigated', renderTailorLucideIcons, { once: true });
            })();
        </script>
    @endonce
</div>