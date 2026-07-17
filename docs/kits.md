# Kits

A kit decides the starter kit's icon set. They're mutually exclusive — exactly one runs per `php artisan tailor`, and it runs before any [tasks](tasks.md) you tick.

| Key      | Label                    | What it does                                                            |
| -------- | ------------------------ | ----------------------------------------------------------------------- |
| `as-is`  | Flux with mixed icons    | The default — a no-op that leaves the icon set untouched.               |
| `hero`   | Flux with Heroicons only | Swaps the starter kit's handful of Lucide icons back to Heroicons.      |
| `lucide` | Flux with Lucide only    | Replaces Heroicons with Lucide equivalents and re-aliases Flux's icons. |

Pick one at the prompt, or pass it straight through:

```bash
php artisan tailor --ui-kit=lucide
```

## The icons are yours to choose

Which glyph replaces which is not baked into the package — it's a map in `config/tailor.php` that you're meant to edit. Publish your own copy first:

```bash
php artisan vendor:publish --tag="tailor-config"
```

Then point any icon at whatever Lucide or Heroicon glyph you prefer, and add entries for icons of your own. The defaults are a starting point, and a fair number of them are judgment calls worth disagreeing with — [Swap the icons](extending.md#swap-the-icons) walks through it, including previewing your edits at `/tailor/icons` before you commit to them. **Settle the map before you run**: the swap is one-way.

Without publishing, the kits run on those defaults, which is a perfectly good answer if you have no opinion yet.

The sections below cover each kit in the order it's offered.

## `as-is` — Flux with mixed icons

The default, and a deliberate no-op: your icons are left exactly as the starter kit ships them, which is [Heroicons](https://heroicons.com) for the most part with four [Lucide](https://lucide.dev) icons published into `resources/views/flux/icon/` as local overrides.

It exists so that "leave the icons alone" is something you can choose rather than something you get by quitting. Pick it when you're running Tailor for the [tasks](tasks.md) — moving the auth folder, grouping components — and have no quarrel with the icon set.

## `hero` — Flux with Heroicons only

Takes the mixed set in the other direction from `lucide`: the four Lucide icons the starter kit publishes are swapped back to their Heroicon equivalents, so the kit renders Heroicons throughout.

Nothing is downloaded. Flux bundles the Heroicons it falls back to, so once the references in your views are rewritten the orphaned Lucide blades under `resources/views/flux/icon/` are simply deleted — the replacement resolves from Flux's own set with no blade on disk at all.

The swaps come from `settings.kits.hero.icons` in the published config, a plain map of the Lucide name to the Heroicon that replaces it:

```php
'settings' => [
    'kits' => [
        'hero' => [
            'icons' => [
                'book-open-text' => 'book-open',
                'chevrons-up-down' => 'chevron-up-down',
                'folder-git-2' => 'folder',
                'layout-grid' => 'squares-2x2',
            ],
        ],
    ],
],
```

A few things worth knowing:

- **A blank value is skipped, not guessed.** Only an entry whose target is a real name and differs from the source rewrites anything. An empty or self-mapping entry is filtered out and its icon left exactly where it is — still Lucide — rather than deleted with no replacement to render in its place. Blanking a value is how you keep a Lucide glyph you'd rather not give up.
- There's no one-to-one Heroicon for every Lucide glyph, which is why the values are yours to choose. `layout-grid` → `squares-2x2` is a judgment call, not a lookup.

## `lucide` — Flux with Lucide only

The bigger of the two swaps, and the reason the icon map in the config is as large as it is. Two things have to happen for a Flux app to render Lucide throughout, and this kit does both:

1. **Your views.** Every Heroicon the starter kit references is swapped for its Lucide equivalent — `arrow-path` becomes `refresh-cw`, `magnifying-glass` becomes `search`, and so on.
2. **Flux's own components.** Flux references Heroicons internally, by name, from inside its own blades — the chevron in a select, the spinner on a loading button. Those can't be rewritten, since they live in the package. Instead the kit publishes the Lucide glyph into `resources/views/flux/icon/` **under the Heroicon's name**, so Flux asks for `chevron-up-down` and gets Lucide's `chevrons-up-down`. That's the re-aliasing.

Both halves are driven by `settings.kits.lucide.icons`, grouped by what each set is for — `starter-kit.heroicons` and `starter-kit.lucide` for your views, `flux.normal` and `flux.animated` for the icons Flux reaches for itself. Every value is the Lucide name the kit downloads and renders, so pointing an entry at a glyph you prefer is all it takes to change what you get.

A few things worth knowing:

- **It's all-or-nothing.** Every icon is downloaded up front, and your views and icon directory are only touched once the whole set is on disk. A failed download leaves the app exactly as it was and exits non-zero, rather than leaving you half-tailored with a mix of both sets and no clean way back.
- Icons are fetched from the network, so this kit needs an internet connection. Failures are reported by name at the end of the run.
- Once the new set lands, icon blades in `flux/icon/` that aren't part of it are removed — the directory ends up holding exactly the Lucide set. That includes **an icon blade you put there yourself**, which is one reason to run this on a starter kit you haven't built on yet; add it to the map and it survives as one of the set. Non-blade files in there are left alone.
- The four entries under `starter-kit.lucide` are the icons the kit already ships as Lucide, so they mostly map to themselves and are listed to keep the set complete. `layout-grid` → `layout-dashboard` is the exception: a swap to a glyph that suits the tailored kit better, and an example of the kind of edit these entries are here for.
