# Laravel Tailor

[![Latest Version on Packagist](https://img.shields.io/packagist/v/onelegstudios/laravel-tailor.svg?style=flat-square)](https://packagist.org/packages/onelegstudios/laravel-tailor)
[![GitHub Tests Action Status](https://github.com/onelegstudios/laravel-tailor/actions/workflows/run-tests.yml/badge.svg)](https://github.com/onelegstudios/laravel-tailor/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://github.com/onelegstudios/laravel-tailor/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/onelegstudios/laravel-tailor/actions?query=workflow%3A%22Fix+PHP+code+style+issues%22+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/onelegstudios/laravel-tailor.svg?style=flat-square)](https://packagist.org/packages/onelegstudios/laravel-tailor)

Tailor the [Laravel Livewire starter kit](https://laravel.com/docs/starter-kits) to your taste. Run a single interactive command to swap the kit's icon set, apply optional tweaks, and keep everything consistent — no hand-editing Blade views or hunting down icon names.

Out of the box Tailor can unify the starter kit's icon set in either direction — swap its [Heroicons](https://heroicons.com) for their [Lucide](https://lucide.dev) equivalents (re-aliasing the icons Flux uses internally so nothing breaks), or swap the handful of Lucide icons it ships back to Heroicons — and it's built to be extended with your own **kits** and **tasks**.

```bash
php artisan tailor
```

```
┌ Welcome to Tailor — let's customize your starter kit.
│
◇ Which icon set do you want?
│ › Flux with mixed icons
│   Flux with Heroicons only
│   Flux with Lucide only
│
◇ What else would you like to tailor?
│ ◻ Convert partials into components
│ ◻ Group components into subfolders
│ ◻ Move non-routed pages components
│ ◻ Move the auth folder
│
└ All done! Your starter kit has been tailored.
```

## Requirements

- PHP 8.3+
- Laravel 13
- A Livewire + [Flux](https://fluxui.dev) starter kit (required for the icon-swapping kits)

## Installation

Install the package via Composer:

```bash
composer require onelegstudios/laravel-tailor --dev
```

> Tailor is a development-time scaffolding tool, so `--dev` is recommended.

Optionally publish the config file to customize the available kits, tasks, and icon mappings:

```bash
php artisan vendor:publish --tag="tailor-config"
```

## Usage

Run the interactive command and follow the prompts:

```bash
php artisan tailor
```

You'll be asked to pick a **UI kit** (mutually exclusive) and any number of additional **tasks**. To skip the kit prompt, pass it directly:

```bash
php artisan tailor --ui-kit=lucide   # as-is | hero | lucide
```

### Removing Tailor when you're done

Tailor is a one-time scaffolding tool — once it has tailored your starter kit there is nothing left for it to do. **We recommend removing the package after you've run the command** so it doesn't linger as a dev dependency.

The `tailor` command offers to do this for you: after tailoring finishes it asks whether you'd like to remove the package, and if you confirm it runs the uninstall for you. You can always remove it manually instead:

```bash
composer remove onelegstudios/laravel-tailor --dev
```

### Built-in kits

| Key      | Label                    | What it does                                                            |
| -------- | ------------------------ | ----------------------------------------------------------------------- |
| `as-is`  | Flux with mixed icons    | The default — a no-op that leaves the icon set untouched.               |
| `hero`   | Flux with Heroicons only | Swaps the starter kit's handful of Lucide icons back to Heroicons.      |
| `lucide` | Flux with Lucide only    | Replaces Heroicons with Lucide equivalents and re-aliases Flux's icons. |

The Lucide kit downloads every icon it needs before touching your app. If any download fails, your views and icon directory are left untouched rather than half-tailored, and the command exits with a non-zero status.

### Built-in tasks

The prompt lists these alphabetically, but they run in the order below whichever way you tick the boxes — `group-components` sorts what `move-components` and `convert-partials` leave at the root of `components/`, so it has to run last.

| Key               | Label                          | What it does                                                                                                                                                                                                                                                     |
| ----------------- | ------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `move-auth`       | Move the auth folder           | Moves the Fortify auth screens out of the `pages/auth` folder into `views/auth` and repoints `FortifyServiceProvider::configureViews()` at the new view names. The components kit's `livewire/auth` folder stays where it is.                                                                      |
| `move-components` | Move non-routed pages components | Moves the Livewire page components under `resources/views/pages/` that aren't directly routed (plus the anonymous `settings/layout`) into `resources/views/components/`, preserving subpaths, and rewrites every `pages::` reference in your views and tests to the bare name. The `auth/` folder is left to the `move-auth` task. |
| `convert-partials` | Convert partials into components | Moves everything under `resources/views/partials/` into `resources/views/components/`, preserving subpaths, and rewrites every `@include('partials.head')` in your views and tests to the `<x-head />` tag it now resolves as. A partial that reads a variable from the view including it gains an `@props` declaration and every caller passes the variable in. See [Converting partials](#converting-partials) for those variables and what's left alone. |
| `group-components` | Group components into subfolders | Sorts the flat components at the root of `resources/views/components/` into a subfolder per concern and rewrites every `<x-name>` reference in your views and tests to the dotted name it now resolves as — `app-logo.blade.php` becomes `branding/app-logo.blade.php`, and `<x-app-logo>` becomes `<x-branding.app-logo>`. Runs after `move-components`, which is what fills that folder. See [Grouping components](#grouping-components) to change the folders. |

#### Converting partials

An `@include` shares the variables of the view that includes it; a component does not. So `convert-partials` reads the variables each partial needs from `settings.tasks.convert-partials.props` in the published config — a map of partial name to the props it declares:

```php
'settings' => [
    'tasks' => [
        'convert-partials' => [
            'props' => [
                'head' => ['title'],
            ],
        ],
    ],
],
```

That's what turns `partials/head.blade.php`, which renders `$title` it inherited from the layout, into a component that declares it:

```blade
@props(['title' => null])

<title>{{ filled($title ?? null) ? ... }}</title>
```

and every caller into one that passes it:

```blade
<x-head :title="$title ?? null" />
```

A few things worth knowing:

- A partial missing from `props` converts to a bare tag — right for one that reads nothing (`settings-heading`), silently wrong for one that reads something, so list your own partials here.
- Only a plain, dataless `@include` is converted. A partial referenced any other way — `@include('partials.head', ['title' => 'Dashboard'])`, `@includeWhen`, a `view('partials.head')` call — is left **entirely** alone, file and references both, and reported at the end of the run: those forms pass or withhold scope in ways a tag can't be assumed to reproduce, so they're never guessed at. Convert them by hand, or make the include dataless and re-run.
- Partials land at the root of `components/`, so opting into `group-components` as well sorts them like any other component: `head` into `layout/` and `settings-heading` into `settings/` by default, rendering as `<x-layout.head />` and `<x-settings.settings-heading />`. A partial of your own needs a folder there too, or it stays at the root and is reported as ungrouped.
- Subpaths are preserved: `partials/nested/meta.blade.php` becomes `components/nested/meta.blade.php` and renders as `<x-nested.meta>`.
- The `partials/` folder is removed once it's empty, and kept if an unconvertible partial is still in it.
- Re-running is a no-op, and an existing target is never clobbered.

#### Grouping components

`group-components` reads its folders from `settings.tasks.group-components.groups` in the published config — a map of folder name to the components it holds:

```php
'settings' => [
    'tasks' => [
        'group-components' => [
            'groups' => [
                'branding' => ['app-logo', 'app-logo-icon'],
                'auth' => ['auth-header', 'auth-session-status', 'passkey-registration', 'passkey-verify'],
                'layout' => ['head'],
                'navigation' => ['desktop-user-menu'],
                'settings' => ['settings-heading'],
                'teams' => ['create-team-modal', 'team-invitation-alert', 'team-switcher'],
                'ui' => ['placeholder-pattern'],
            ],
        ],
    ],
],
```

The folder name **is** the dotted prefix the component picks up, so renaming `branding` to `brand` here is all it takes to have the components render as `<x-brand.app-logo>` — the rewrite follows.

A few things worth knowing:

- Only the root of `components/` is sorted. A component already in a subfolder — like the `settings/layout` that `move-components` puts there — is already grouped and is left alone.
- A root component that isn't listed under any folder **stays where it is** and is reported at the end of the run, so your own components are never guessed at. Add them to a folder above to have them sorted.
- Listing a component your kit doesn't ship is harmless: it simply never matches. The defaults cover all three starter-kit variants, `teams` included.
- `head` and `settings-heading` only reach the root of `components/` when [`convert-partials`](#converting-partials) runs. They're listed for the run that opts into both tasks, and never match otherwise.
- References are matched on the `<x-` / `</x-` tag prefix, which keeps the rewrite off Alpine's `x-` attributes and off any Flux component sharing a name. A component referenced dynamically (`<x-dynamic-component :component="$name" />`, `view('components.app-logo')`) can't be matched and will need a hand-edit.
- Re-running is a no-op, and an existing target is never clobbered.

### Icon reference page

In the `local` environment Tailor registers a Livewire page listing the icons it manages, handy for eyeballing the Heroicon → Lucide mapping:

```
/tailor/icons
```

This route is **never** registered outside `local`.

## Extending Tailor

Tailor is config-driven. `config/tailor.php` is the single source of truth for which kits, tasks, and icon mappings are available. Publish it first so you have a local copy to edit:

```bash
php artisan vendor:publish --tag="tailor-config"
```

### Add a kit or task

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

If your kit or task needs options of its own, put them under `settings.kits.{key}` or `settings.tasks.{key}` and read them with `config("tailor.settings.tasks.{$this->key()}.your-option")` — keying on `key()` means the config location is derivable from the class rather than memorized. The built-in `lucide` and `group-components` are the worked examples.

### Override a built-in

Drop a class with the same short name into the override namespace and it runs instead of the package's version — no change to the config lists:

```php
'registry' => [
    'overrides' => [
        'kits'  => 'App\\Tailor\\Kits',   // App\Tailor\Kits\LucideKit overrides the package's LucideKit
        'tasks' => 'App\\Tailor\\Tasks',
    ],
],
```

## Testing

```bash
composer test          # Pint (lint check) + PHPStan + Pest
composer test-coverage # Pest with coverage
```

The individual steps can also be run on their own:

```bash
composer lint          # Fix code style with Pint (composer format is an alias)
composer lint:check    # Check code style without changing files
composer analyse       # Static analysis with PHPStan (composer types:check runs it with a raised memory limit)
```

### Maintenance scripts (`bin/`)

Two dev-only helpers live in `bin/`. They are not part of the published package — they keep the test fixtures and icon config in sync during development, and should be run from the package root.

| Script                  | What it does                                                                                                                                                                                                                                                                                                                                 |
| ----------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `bin/download-fixtures` | Rebuilds `tests/Fixtures/starter-kits` from scratch by downloading the Livewire starter-kit variants (`main`, `components`, `teams`) used as test fixtures. Each variant's own top-level `tests/` dir is skipped, and the fixtures are only swapped into place once every download succeeds. Review with `git status` and commit afterwards. |
| `bin/scan-icons`        | Scans the starter-kit fixtures and Flux's components for the icon names the app uses and records them in `config/tailor.php` (under `settings.kits.lucide.icons`, keeping `settings.kits.hero.icons` keys in sync). Append-only by default.                                                                                                  |

`bin/scan-icons` accepts a few flags:

```bash
bin/scan-icons            # add newly found icons; warn about stale ones
bin/scan-icons --prune    # also remove config entries for icons no longer found
bin/scan-icons --check    # report missing icons and exit 1 without writing (for CI)
bin/scan-icons --help     # show full usage
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Henrik Persson](https://github.com/onelegstudios)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
