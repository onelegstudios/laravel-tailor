# Installation

## Requirements

- PHP 8.3+
- Laravel 13
- A Livewire + [Flux](https://fluxui.dev) starter kit (required for the icon-swapping kits)

## Install the package

Install via Composer:

```bash
composer require onelegstudios/laravel-tailor --dev
```

> Tailor is a development-time scaffolding tool, so `--dev` is recommended.

## Publish the config

Not required — but do it before your first run rather than after:

```bash
php artisan vendor:publish --tag="tailor-config"
```

`config/tailor.php` is the single source of truth for what Tailor offers and every decision it makes: the icon maps, the folders components are sorted into, the kits and tasks you're offered at all. It's where you tell a one-time, partly one-way tool what you actually want before it rewrites your views. See [Publish the config first](usage.md#publish-the-config-first) for why it's worth two minutes, and [Extending Tailor](extending.md) for the full range.

## Removing Tailor when you're done

Tailor is a one-time scaffolding tool — once it has tailored your starter kit there is nothing left for it to do. **We recommend removing the package after you've run the command** so it doesn't linger as a dev dependency.

The `tailor` command offers to do this for you: after tailoring finishes it asks whether you'd like to remove the package, and if you confirm it runs the uninstall for you. You can always remove it manually instead:

```bash
composer remove onelegstudios/laravel-tailor --dev
```
