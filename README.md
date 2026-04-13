# Laravel Tailor

[![Release](https://img.shields.io/packagist/v/onelegstudios/laravel-tailor.svg?label=release&color=18181B)](https://packagist.org/packages/onelegstudios/laravel-tailor)
[![PHP 8.3+](https://img.shields.io/packagist/php-v/onelegstudios/laravel-tailor?label=PHP&color=777BB4)](https://packagist.org/packages/onelegstudios/laravel-tailor)
[![Laravel 13](https://img.shields.io/badge/Laravel-13.x-FF2D20)](https://laravel.com)
[![Livewire 4](https://img.shields.io/badge/Livewire-4.x-FB70A9)](https://livewire.laravel.com)
[![Support](https://img.shields.io/badge/Support-Livewire%20Starter%20Kit-0F766E)](https://github.com/onelegstudios/laravel-tailor)
[![Tests](https://img.shields.io/github/actions/workflow/status/onelegstudios/laravel-tailor/run-tests.yml?branch=main&label=tests&color=16A34A)](https://github.com/onelegstudios/laravel-tailor/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Style](https://img.shields.io/github/actions/workflow/status/onelegstudios/laravel-tailor/fix-php-code-style-issues.yml?branch=main&label=style&color=F59E0B)](https://github.com/onelegstudios/laravel-tailor/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Downloads](https://img.shields.io/packagist/dt/onelegstudios/laravel-tailor.svg?label=downloads&color=6B7280)](https://packagist.org/packages/onelegstudios/laravel-tailor)

The artisan tool to customize your Laravel starter kits. Seamlessly transform fresh Livewire installations with curated modifications, architectural tweaks, and optional features. Your foundation, tailored to your workflow.

## Installation

You can install the package via composer:

```bash
composer require your-vendor/laravel-tailor --dev
```

## Usage

After requiring this package in your Laravel application and installing a Laravel starter kit, run the tailor command to apply your preferred modifications:

```bash
php artisan tailor:install
```

You will be prompted to select which modifications to apply. Laravel Tailor currently ships with:

- Use Lucide icons

If you only want to run the icon migration on an existing project, you can call the command directly:

```bash
php artisan tailor:use-lucide-icons
```

### Use Lucide Icons

`tailor:use-lucide-icons` migrates supported Flux icon usage to published Lucide icon components.

When you run it, Tailor:

- scans `resources/views` for supported Flux icon references
- publishes the mapped Lucide icon Blade files into `resources/views/flux/icon`
- rewrites supported literal icon usages such as `<flux:icon.plus />`, `<flux:icon name="users" />`, `icon="plus"`, `icon:leading="chevron-down"`, and `icon-trailing="chevron-down"`
- leaves unresolved dynamic icon expressions unchanged and reports each one as a warning instead of guessing
- adds Tailor's package `@source` directive to `resources/css/app.css` when that file exists, so Tailwind can see the package views used by the published icons

Icon names are resolved from Tailor's icon mapping configuration. The default mappings cover common Flux-to-Lucide translations such as `loading` to `loader-circle` and `exclamation-triangle` to `triangle-alert`.

#### Custom icon mappings

If you want to use different Lucide icons than Tailor's defaults, publish the package config first:

```bash
php artisan vendor:publish --tag=tailor-config
```

This creates `config/tailor.php`. From there, update `icons.mappings` to point Flux icon names at the Lucide icons you want to publish.

```php
'icons' => [
	'mappings' => [
		'plus' => 'circle-plus',
		'users' => 'users-round',
		'layout-grid' => 'layout-grid',
	],
],
```

Map a Flux icon name to any Lucide icon name you want Tailor to publish. If you want to keep an icon name unchanged but still make sure its Lucide Blade view is published, map it to itself.

After updating the config, run the migration command again:

```bash
php artisan tailor:use-lucide-icons
```

For example, a view like this:

```blade
<flux:icon.plus />
<flux:button icon="loading" />
<flux:button :icon="$statusIcon" />
```

becomes:

```blade
<flux:icon.plus />
<flux:button icon="loader-circle" />
<flux:button :icon="$statusIcon" />
```

In that example, Tailor publishes both the `plus` and `loader-circle` Lucide icon views. The bound `:icon` expression is left alone unless Tailor can safely resolve every literal icon name in the expression.

## Why Tailor?

Standard starter kits are great, but they often require 30 minutes of "massaging" before you can actually start coding. Tailor automates that process, ensuring every project starts with your specific best practices.

- ⚡️ Fast: Transform your kit in seconds.
- 🛠 Modular: Choose only what you need.
- 🏗 Scalable: Built with Laravel 13 and Livewire in mind.

## Support

Currently supports the Livewire Starter Kit. Support for Breeze and Jetstream is coming soon.

## Package Development

If you are developing or contributing to this package, you can refresh the local Testbench workbench with:

```bash
php bin/refresh-workbench
```

This command is for package development only. It regenerates the local `workbench/` application from the latest Livewire starter kit, rebuilds the Testbench workbench, reinstalls the workbench frontend dependencies, and builds the Vite manifest used by `testbench serve`.

Do not run this when installing Laravel Tailor in your own application. Package consumers only need to install the package and run `php artisan tailor:install` inside their Laravel project.

### Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](https://github.com/onelegstudios/laravel-tailor/security/policy) on how to report security vulnerabilities.

## Credits

- [Oneleggedswede](https://github.com/oneleggedswede)
- [All Contributors](https://github.com/onelegstudios/laravel-tailor/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
