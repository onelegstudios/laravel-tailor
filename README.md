# Laravel Tailor

[![Release](https://img.shields.io/packagist/v/onelegstudios/laravel-tailor.svg?label=release&color=18181B)](https://packagist.org/packages/onelegstudios/laravel-tailor)
[![PHP 8.3+](https://img.shields.io/packagist/php-v/onelegstudios/laravel-tailor?label=PHP&color=777BB4)](https://packagist.org/packages/onelegstudios/laravel-tailor)
[![Tests](https://img.shields.io/github/actions/workflow/status/onelegstudios/laravel-tailor/run-tests.yml?branch=main&label=tests&color=16A34A)](https://github.com/onelegstudios/laravel-tailor/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Style](https://img.shields.io/github/actions/workflow/status/onelegstudios/laravel-tailor/fix-php-code-style-issues.yml?branch=main&label=style&color=F59E0B)](https://github.com/onelegstudios/laravel-tailor/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Downloads](https://img.shields.io/packagist/dt/onelegstudios/laravel-tailor.svg?label=downloads&color=6B7280)](https://packagist.org/packages/onelegstudios/laravel-tailor)

[![Laravel 13](https://img.shields.io/badge/Laravel-13.x-FF2D20)](https://laravel.com)
[![Livewire 4](https://img.shields.io/badge/Livewire-4.x-FB70A9)](https://livewire.laravel.com)
[![Support](https://img.shields.io/badge/Support-Livewire%20Starter%20Kit-0F766E)](https://github.com/onelegstudios/laravel-tailor)

Standard starter kits are great, but they often require 30 minutes of "massaging" before you can actually start coding. Tailor automates that process, ensuring every project starts with your specific best practices.

- ⚡️ Fast: Transform your kit in seconds.
- 🛠 Modular: Choose only what you need.
- 🏗 Scalable: Built with Laravel 13 and Livewire in mind.

## Installation

You can install the package via composer:

```bash
composer require onelegstudios/laravel-tailor --dev
```

## Usage

After requiring this package in your Laravel application and installing a Laravel starter kit, run the tailor command to apply your preferred modifications:

```bash
php artisan tailor:install
```

You will be prompted to select which modifications to apply. Laravel Tailor currently ships with:

- Use Lucide icons

### Use Lucide Icons

`tailor:use-lucide-icons` migrates supported Flux icon usage to published Lucide icon components.

When you run it, Tailor:

- scans `resources/views` for supported Flux icon references
- publishes the mapped Lucide icon Blade files into `resources/views/flux/icon`
- rewrites supported literal icon usages.
- leaves unresolved dynamic icon expressions unchanged and reports each one as a warning instead of guessing
- adds Tailor's package `@source` directive to `resources/css/app.css` when that file exists, so Tailwind can see the package views used by the published icons

Icon names are resolved from Tailor's icon mapping configuration. The default mappings cover common Flux-to-Lucide translations such as `loading` to `loader-circle` and `exclamation-triangle` to `triangle-alert`.

If you only want to run the icon migration you can call the command directly:

```bash
php artisan tailor:use-lucide-icons
```

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

## Package Development

Do not run this when installing Laravel Tailor in your own application. Package consumers only need to install the package and run `php artisan tailor:install` inside their Laravel project.

### Refreshing the Testbench workbench with the latest starter-kit version

If you are developing or contributing to this package, you can refresh the local Testbench workbench with:

```bash
php bin/refresh-workbench.php
```

This command is for package development only. It regenerates the local `workbench/` application from the latest Livewire starter kit, rebuilds the Testbench workbench, reinstalls the workbench frontend dependencies, and builds the Vite manifest used by `testbench serve`.

### Syncing Flux icons to config file

When `bin/refresh-workbench.php` changes Flux icon usage in the workbench views, refresh the package icon map with:

```bash
php bin/sync-flux-icon-map.php
```

This script scans `workbench/resources/views` for Flux icon references and updates the package `config/tailor.php` file under `icons.mappings`. It preserves unrelated Tailor config, keeps any explicit icon mappings you have already defined, adds newly detected icons with `null` targets, always includes Flux internal icons such as `loading` and `exclamation-triangle`, and writes `icons.removed` when older mappings are no longer detected.

Run it after changing package Blade views or Flux icon migration behavior so the default package icon configuration stays aligned with the current workbench templates.

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
