# Laravel Tailor

[![Latest Version on Packagist](https://img.shields.io/packagist/v/oneleggedswede/laravel-tailor.svg?style=flat-square)](https://packagist.org/packages/oneleggedswede/laravel-tailor)
[![GitHub Tests Action Status](https://github.com/oneleggedswede/laravel-tailor/actions/workflows/run-tests.yml/badge.svg)](https://github.com/oneleggedswede/laravel-tailor/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://github.com/oneleggedswede/laravel-tailor/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/oneleggedswede/laravel-tailor/actions?query=workflow%3A%22Fix+PHP+code+style+issues%22+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/oneleggedswede/laravel-tailor.svg?style=flat-square)](https://packagist.org/packages/oneleggedswede/laravel-tailor)

Tailor the [Laravel Livewire starter kit](https://laravel.com/docs/starter-kits) to your taste. Run a single interactive command to swap the kit's icon set, apply optional tweaks, and keep everything consistent — no hand-editing Blade views or hunting down icon names.

Out of the box Tailor can unify the starter kit's icon set in either direction — swap its [Heroicons](https://heroicons.com) for their [Lucide](https://lucide.dev) equivalents (re-aliasing the icons Flux uses internally so nothing breaks), or swap the handful of Lucide icons it ships back to Heroicons — and it's built to be extended with your own **kits** and **tasks**.

```bash
php artisan tailor
```

```
┌ Welcome to Tailor — let's customize your starter kit.
│
◇ What UI kit do you want to use?
│ › Leave the starter kit as-is
│   Flux with Heroicons
│   Flux with Lucide Icons
│   Tall Stack UI
│
◇ What else would you like to tailor?
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
composer require oneleggedswede/laravel-tailor --dev
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
php artisan tailor --ui-kit=lucide   # as-is | hero | lucide | tall-stack
```

### Removing Tailor when you're done

Tailor is a one-time scaffolding tool — once it has tailored your starter kit there is nothing left for it to do. **We recommend removing the package after you've run the command** so it doesn't linger as a dev dependency.

The `tailor` command offers to do this for you: after tailoring finishes it asks whether you'd like to remove the package, and if you confirm it runs the uninstall for you. You can always remove it manually instead:

```bash
composer remove oneleggedswede/laravel-tailor --dev
```

### Built-in kits

| Key          | Label                       | What it does                                                            |
| ------------ | --------------------------- | ----------------------------------------------------------------------- |
| `as-is`      | Leave the starter kit as-is | The default — a no-op that leaves the icon set untouched.               |
| `hero`       | Flux with Heroicons         | Swaps the starter kit's handful of Lucide icons back to Heroicons.      |
| `lucide`     | Flux with Lucide Icons      | Replaces Heroicons with Lucide equivalents and re-aliases Flux's icons. |
| `tall-stack` | Tall Stack UI               | Placeholder — not yet implemented.                                      |

### Built-in tasks

| Key         | Label                | What it does                       |
| ----------- | -------------------- | ---------------------------------- |
| `move-auth` | Move the auth folder | Placeholder — not yet implemented. |

The Lucide kit downloads every icon it needs before touching your app. If any download fails, your views and icon directory are left untouched rather than half-tailored, and the command exits with a non-zero status.

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

Then register it in `config/tailor.php`:

```php
'kits' => [
    HeroKit::class,
    LucideKit::class,
    \App\Tailor\Kits\BootstrapKit::class,
],
```

A kit implements `Onelegstudios\Tailor\Kits\UiKit`; a task implements `Onelegstudios\Tailor\Tasks\TailorTask`. Both expose a `key()`, a `label()`, and an `apply()` method that returns an array of items it couldn't apply (an empty array means success).

### Override a built-in

Drop a class with the same short name into the override namespace and it runs instead of the package's version — no change to the config lists:

```php
'overrides' => [
    'kits'  => 'App\\Tailor\\Kits',   // App\Tailor\Kits\LucideKit overrides the package's LucideKit
    'tasks' => 'App\\Tailor\\Tasks',
],
```

## Testing

```bash
composer test          # Pint + PHPStan + Pest
composer test-coverage # Pest with coverage
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Henrik Persson](https://github.com/oneleggedswede)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
