# Laravel Tailor

[![Latest Version on Packagist](https://img.shields.io/packagist/v/onelegstudios/laravel-tailor.svg)](https://packagist.org/packages/onelegstudios/laravel-tailor)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/onelegstudios/laravel-tailor/run-tests.yml?branch=main&label=tests)](https://github.com/onelegstudios/laravel-tailor/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/onelegstudios/laravel-tailor/fix-php-code-style-issues.yml?branch=main&label=code%20style)](https://github.com/onelegstudios/laravel-tailor/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/onelegstudios/laravel-tailor.svg)](https://packagist.org/packages/onelegstudios/laravel-tailor)

The artisan tool to customize your Laravel starter kits. Seamlessly transform fresh Livewire installations with curated modifications, architectural tweaks, and optional features. Your foundation, tailored to your workflow.

## Installation

You can install the package via composer:

```bash
composer require your-vendor/laravel-tailor --dev
```

## Usage

After requiring this package in your Laravel application and installing a Laravel starter kit, run the tailor command to apply your preferred modifications:

```php
php artisan tailor:stitch
```

You will be prompted to select which modifications to apply, such as:

- Add features here

## Package Development

If you are developing or contributing to this package, you can refresh the local Testbench workbench with:

```bash
php bin/refresh-workbench
```

This command is for package development only. It regenerates the local `workbench/` application from the latest Livewire starter kit and rebuilds the Testbench workbench used to develop and test Laravel Tailor.

Do not run this when installing Laravel Tailor in your own application. Package consumers only need to install the package and run `php artisan tailor:stitch` inside their Laravel project.

## Why Tailor?

Standard starter kits are great, but they often require 30 minutes of "massaging" before you can actually start coding. Tailor automates that process, ensuring every project starts with your specific best practices.

- ⚡️ Fast: Transform your kit in seconds.
- 🛠 Modular: Choose only what you need.
- 🏗 Scalable: Built with Laravel 13 and Livewire in mind.

## Support

Currently supports the Livewire Starter Kit. Support for Breeze and Jetstream is coming soon.

## Testing

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
