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
│ ◻ Move the auth folder
│ ◻ Move non-routed pages components
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

| Key               | Label                          | What it does                                                                                                                                                                                                                                                     |
| ----------------- | ------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `move-auth`       | Move the auth folder           | Moves the Fortify auth screens out of the `pages/auth` folder into `views/auth` and repoints `FortifyServiceProvider::configureViews()` at the new view names. The components kit's `livewire/auth` folder stays where it is.                                                                      |
| `move-components` | Move non-routed pages components | Moves the Livewire page components under `resources/views/pages/` that aren't directly routed (plus the anonymous `settings/layout`) into `resources/views/components/`, preserving subpaths, and rewrites every `pages::` reference in your views and tests to the bare name. The `auth/` folder is left to the `move-auth` task. |

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
