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
     * @return Collection<int, array{group: string, set: string, originalIcon: string|null, icons: array<int, array{original: string, replacement: string}>}>
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
     * Flatten the nested icon config into displayable sections.
     *
     * @return Collection<int, array{group: string, set: string, originalIcon: string|null, icons: array<int, array{original: string, replacement: string}>}>
     */
    protected function allSections(): Collection
    {
        return collect(config('tailor.settings.kits.lucide.icons', []))
            ->flatMap(fn (array $sets, string $group): array => collect($sets)
                ->map(fn (array $icons, string $set): array => [
                    'group' => $group,
                    'set' => $set,
                    // Which icon set the original names belong to, so the page
                    // can render the original glyph. Flux's icons are Heroicons,
                    // except its animated "loading" pseudo-icon (a spinner).
                    'originalIcon' => match ($set) {
                        'lucide' => 'lucide',
                        'animated' => 'spinner',
                        default => 'heroicon',
                    },
                    'icons' => collect($icons)
                        ->map(fn (string $replacement, string $original): array => [
                            'original' => $original,
                            'replacement' => $replacement,
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all());
    }
};
?>

<div class="tailor">
    <header class="tailor-header">
        <div>
            <h1>Tailor icons</h1>
            <p class="tailor-subtitle">
                Every icon mapped in <code>config/tailor.php</code>, with its original name and Lucide replacement.
            </p>
        </div>
        <span class="tailor-count">{{ $this->total }} {{ \Illuminate\Support\Str::plural('icon', $this->total) }}</span>
    </header>

    <div class="tailor-search">
        <i data-lucide="search" wire:ignore></i>
        <input
            type="search"
            wire:model.live.debounce.200ms="search"
            placeholder="Filter by original or Lucide name…"
            autofocus
        >
    </div>

    @forelse ($this->sections as $section)
        <section class="tailor-section" wire:key="section-{{ $section['group'] }}-{{ $section['set'] }}">
            <h2>
                <span class="tailor-group">{{ $section['group'] }}</span>
                <span class="tailor-set">{{ $section['set'] }}</span>
                <span class="tailor-count tailor-count--sm">{{ count($section['icons']) }}</span>
            </h2>

            <table>
                <thead>
                    <tr>
                        <th>Original</th>
                        <th>Lucide replacement</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($section['icons'] as $icon)
                        <tr wire:key="icon-{{ $section['group'] }}-{{ $section['set'] }}-{{ $icon['original'] }}">
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
                                            <i data-lucide="{{ $icon['replacement'] }}"></i>
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
