# Extending Tailor

Tailor is config-driven. `config/tailor.php` is the single source of truth for which kits, tasks, and icon mappings are available. Publish it first so you have a local copy to edit:

```bash
php artisan vendor:publish --tag="tailor-config"
```

Everything below is a change to that file. Start with the icons — it's the one that needs no PHP at all.

## Swap the icons

**Please do.** The icon map that ships with Tailor is one person's answer to "what's the closest Lucide glyph to this Heroicon?", and a good half of it is a judgment call rather than a lookup. `magnifying-glass` → `search` is obvious. `finger-print` → `fingerprint-pattern` is a preference. Nothing about the defaults is authoritative, and disagreeing with them is the expected use of this config, not a fork of it.

The map for the [`lucide` kit](kits.md#lucide--flux-with-lucide-only) is a plain Heroicon-to-Lucide lookup. Point any entry at a glyph you like better:

```php
'settings' => [
    'kits' => [
        'lucide' => [
            'icons' => [
                'starter-kit' => [
                    'heroicons' => [
                        'trash' => 'trash-2',      // ships as trash-2; 'trash' is right there too
                        'cog' => 'settings',       // or 'cog', or 'settings-2'
                        'home' => 'house',
                    ],
                ],
            ],
        ],
    ],
],
```

The value is the name the kit downloads and renders, so any Lucide glyph is fair game — nothing has to already be in the file. **The same goes for icons of your own.** If your app's views use a Heroicon the starter kit doesn't, add a key for it and the kit swaps and downloads it along with the rest:

```php
'academic-cap' => 'graduation-cap',
```

The [`hero` kit](kits.md#hero--flux-with-heroicons-only) works the same way in reverse, mapping Lucide to Heroicon. Its values are the ones most worth a second opinion, since Heroicons is the smaller set and the near-misses are more common.

### See it before you run it

You don't have to guess from names. In your `local` environment Tailor serves a page rendering both glyphs of every mapping side by side:

```
/tailor/icons
```

It reads the same config you just edited, so the loop is: change a value, refresh, decide. See [Icon reference page](usage.md#icon-reference-page).

### Edit before you run, not after

Worth knowing, because the tooling can't warn you: **the swap is one-way**. The kit finds icons in your views by their *original* name, so once it has run, those names are gone — your views hold the Lucide ones. Changing a mapping afterwards and re-running finds nothing left to match, and the `hero` kit is not an inverse of `lucide` — it only covers the handful of icons the starter kit ships as Lucide, so it won't take you back either.

None of that is a problem if you settle the map first, which is what `/tailor/icons` is for. If you've already run it, git is the way back.

## Add a kit or task

Generate a class with the provided make commands:

```bash
php artisan make:tailor-kit BootstrapKit    # -> app/Tailor/Kits/BootstrapKit.php
php artisan make:tailor-task RenameBrand    # -> app/Tailor/Tasks/RenameBrand.php
```

Then register it in `config/tailor.php` under `registry`:

```php
'registry' => [
    'kits' => [
        AsIsKit::class,
        HeroKit::class,
        LucideKit::class,
        \App\Tailor\Kits\BootstrapKit::class,
    ],
    // 'tasks' => [ MoveAuth::class, \App\Tailor\Tasks\RenameBrand::class ],
],
```

A kit implements `Onelegstudios\Tailor\Kits\UiKit`; a task implements `Onelegstudios\Tailor\Tasks\TailorTask`. Both expose a `key()`, a `label()`, and an `apply()` method that returns an array of items it couldn't apply (an empty array means success).

## Settings

If your kit or task needs options of its own, put them under `settings.kits.{key}` or `settings.tasks.{key}` and read them with `config("tailor.settings.tasks.{$this->key()}.your-option")` — keying on `key()` means the config location is derivable from the class rather than memorized. The built-in [`lucide`](kits.md#lucide--flux-with-lucide-only) and [`group-components`](tasks.md#group-components--group-components-into-subfolders) are the worked examples.

## Override a built-in

Drop a class with the same short name into the override namespace and it runs instead of the package's version — no change to the config lists:

```php
'registry' => [
    'overrides' => [
        'kits'  => 'App\\Tailor\\Kits',   // App\Tailor\Kits\LucideKit overrides the package's LucideKit
        'tasks' => 'App\\Tailor\\Tasks',
    ],
],
```
