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

```php
php artisan tailor:install
```

You will be prompted to select which modifications to apply, such as:

- Add features here

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
