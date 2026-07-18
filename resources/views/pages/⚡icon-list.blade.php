<?php

use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('tailor::layouts.icons')]
class extends Component
{
    public string $search = '';

    /**
     * The configured icon sections, filtered by the current search term.
     *
     * @return Collection<int, array{kit: string, kitLabel: string, group: string, set: string, originalIcon: string, replacementIcon: string, replacementLabel: string, icons: array<int, array{original: string, replacement: string}>}>
     */
    #[Computed]
    public function sections(): Collection
    {
        $search = strtolower(trim($this->search));

        return $this->allSections()
            ->map(function (array $section) use ($search): array {
                if ($search !== '') {
                    $section['icons'] = collect($section['icons'])
                        ->filter(fn (array $icon): bool => str_contains($icon['original'], $search)
                            || str_contains($icon['replacement'], $search))
                        ->values()
                        ->all();
                }

                return $section;
            })
            ->filter(fn (array $section): bool => count($section['icons']) > 0)
            ->values();
    }

    #[Computed]
    public function total(): int
    {
        return $this->sections->sum(fn (array $section): int => count($section['icons']));
    }

    /**
     * Every kit's sections, chained so both kits render on the page in the order
     * they are offered (Hero before Lucide).
     *
     * @return Collection<int, array{kit: string, kitLabel: string, group: string, set: string, originalIcon: string, replacementIcon: string, replacementLabel: string, icons: array<int, array{original: string, replacement: string}>}>
     */
    protected function allSections(): Collection
    {
        return $this->heroSections()->concat($this->lucideSections());
    }

    /**
     * HeroKit's swaps: a flat lucide-name => heroicon-name map, so the original
     * glyph is a Lucide icon and the replacement is a Heroicon.
     *
     * @return Collection<int, array{kit: string, kitLabel: string, group: string, set: string, originalIcon: string, replacementIcon: string, replacementLabel: string, icons: array<int, array{original: string, replacement: string}>}>
     */
    protected function heroSections(): Collection
    {
        $icons = config('tailor.settings.kits.hero.icons', []);

        if ($icons === []) {
            return collect();
        }

        return collect([[
            'kit' => 'hero',
            'kitLabel' => 'Hero kit',
            'group' => 'starter-kit',
            'set' => 'lucide',
            'originalIcon' => 'lucide',
            'replacementIcon' => 'heroicon',
            'replacementLabel' => 'Heroicon replacement',
            'icons' => $this->normalizeIcons($icons),
        ]]);
    }

    /**
     * LucideKit's swaps: a nested group => set => (original => lucide-name) map,
     * so the replacement is always a Lucide icon and the original glyph depends
     * on the set (Flux's icons are Heroicons, except its animated "loading"
     * pseudo-icon, a spinner).
     *
     * @return Collection<int, array{kit: string, kitLabel: string, group: string, set: string, originalIcon: string, replacementIcon: string, replacementLabel: string, icons: array<int, array{original: string, replacement: string}>}>
     */
    protected function lucideSections(): Collection
    {
        return collect(config('tailor.settings.kits.lucide.icons', []))
            ->flatMap(fn (array $sets, string $group): array => collect($sets)
                ->map(fn (array $icons, string $set): array => [
                    'kit' => 'lucide',
                    'kitLabel' => 'Lucide kit',
                    'group' => $group,
                    'set' => $set,
                    'originalIcon' => match ($set) {
                        'lucide' => 'lucide',
                        'animated' => 'spinner',
                        default => 'heroicon',
                    },
                    'replacementIcon' => 'lucide',
                    'replacementLabel' => 'Lucide replacement',
                    'icons' => $this->normalizeIcons($icons),
                ])
                ->values()
                ->all());
    }

    /**
     * Flatten a flat original => replacement map into displayable icon rows.
     *
     * @param  array<string, string>  $icons
     * @return array<int, array{original: string, replacement: string}>
     */
    protected function normalizeIcons(array $icons): array
    {
        return collect($icons)
            ->map(fn (string $replacement, string $original): array => [
                'original' => $original,
                'replacement' => $replacement,
            ])
            ->values()
            ->all();
    }
};
?>

<div class="tailor">
    <header class="tailor-header">
        <div>
            <h1>Tailor icons</h1>
            <p class="tailor-subtitle">
                Every icon swap defined in <code>config/tailor.php</code>, grouped by kit, with each original name and its replacement.
            </p>
        </div>
        <span class="tailor-count">{{ $this->total }} {{ \Illuminate\Support\Str::plural('icon', $this->total) }}</span>
    </header>

    <div class="tailor-search">
        <i data-lucide="search" wire:ignore></i>
        <input
            type="search"
            wire:model.live.debounce.200ms="search"
            placeholder="Filter by original or replacement name…"
            autofocus
        >
    </div>

    @php($currentKit = null)
    @forelse ($this->sections as $section)
        @if ($section['kit'] !== $currentKit)
            @php($currentKit = $section['kit'])
            <div class="tailor-kit" wire:key="kit-{{ $section['kit'] }}">
                <span class="tailor-kit-badge">Kit</span>
                <span class="tailor-kit-name">{{ $section['kitLabel'] }}</span>
                <span class="tailor-kit-desc">
                    {{ $section['kit'] === 'hero'
                        ? 'Reverts the starter kit’s Lucide overrides back to Heroicons'
                        : 'Swaps the starter kit’s Heroicons for their Lucide equivalents' }}
                </span>
            </div>
        @endif

        <section class="tailor-section" wire:key="section-{{ $section['kit'] }}-{{ $section['group'] }}-{{ $section['set'] }}">
            <h2>
                <span class="tailor-group">{{ $section['group'] }}</span>
                <span class="tailor-set">{{ $section['set'] }}</span>
                <span class="tailor-count tailor-count--sm">{{ count($section['icons']) }}</span>
            </h2>

            <table>
                <thead>
                    <tr>
                        <th>Original</th>
                        <th>{{ $section['replacementLabel'] }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($section['icons'] as $icon)
                        <tr wire:key="icon-{{ $section['kit'] }}-{{ $section['group'] }}-{{ $section['set'] }}-{{ $icon['original'] }}">
                            <td>
                                <span class="tailor-icon-cell">
                                    @if ($section['originalIcon'] === 'heroicon')
                                        <span class="tailor-glyph" wire:ignore>
                                            <span class="tailor-heroicon" data-hero="{{ $icon['original'] }}"></span>
                                        </span>
                                    @elseif ($section['originalIcon'] === 'spinner')
                                        <span class="tailor-glyph">
                                            <span class="tailor-spinner"></span>
                                        </span>
                                    @elseif ($section['originalIcon'] === 'lucide')
                                        <span class="tailor-glyph" wire:ignore>
                                            <i data-lucide="{{ $icon['original'] }}"></i>
                                        </span>
                                    @else
                                        <span class="tailor-glyph tailor-glyph--empty">—</span>
                                    @endif
                                    <code>{{ $icon['original'] }}</code>
                                </span>
                            </td>
                            <td>
                                @if ($icon['replacement'] === '')
                                    <span class="tailor-muted">no mapping</span>
                                @else
                                    <span class="tailor-icon-cell">
                                        <span @class(['tailor-glyph', 'tailor-glyph--spin' => $section['originalIcon'] === 'spinner']) wire:ignore>
                                            @if ($section['replacementIcon'] === 'heroicon')
                                                <span class="tailor-heroicon" data-hero="{{ $icon['replacement'] }}"></span>
                                            @else
                                                <i data-lucide="{{ $icon['replacement'] }}"></i>
                                            @endif
                                        </span>
                                        <code>{{ $icon['replacement'] }}</code>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @empty
        <p class="tailor-empty">No icons match “{{ $search }}”.</p>
    @endforelse
</div>
