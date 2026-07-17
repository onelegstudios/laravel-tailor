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
│ ◻ Remove published Flux overrides
│
└ All done! Your starter kit has been tailored.
```

## Quick start

Requires PHP 8.3+, Laravel 13, and a Livewire + [Flux](https://fluxui.dev) starter kit.

```bash
composer require onelegstudios/laravel-tailor --dev
php artisan vendor:publish --tag="tailor-config"   # optional — but worth it before you run
php artisan tailor
```

The config is where you say which glyph replaces which, what your components end up called, and what you're offered in the first place. It's worth a skim before a one-time tool rewrites your views — see [Publish the config first](docs/usage.md#publish-the-config-first).

> [!IMPORTANT]
> **Run Tailor on a starter kit you've just installed**, before you've built on it. It rewrites views in place and can't tell your work from the kit's — some steps delete files, all of them are one-way, and none are undone by running it again. On an app you've already built, commit first. See [Run it on a fresh starter kit](docs/usage.md#run-it-on-a-fresh-starter-kit).

Tailor is a one-time scaffolding tool, so remove it once you're done — the command offers to do that for you when it finishes.

## Documentation

| Guide                                | What's in it                                                          |
| ------------------------------------ | --------------------------------------------------------------------- |
| [Installation](docs/installation.md) | Requirements, installing, publishing the config, and removing Tailor.  |
| [Usage](docs/usage.md)               | Running the command, its options, and the icon reference page.         |
| [Kits](docs/kits.md)                 | The built-in icon sets and what each one does to your views.           |
| [Tasks](docs/tasks.md)               | The optional tweaks, what they rewrite, and how to configure them.     |
| [Extending Tailor](docs/extending.md) | Adding your own kits and tasks, and overriding the built-in ones.     |
| [Development](docs/development.md)   | Running the test suite and the package's maintenance scripts.          |

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
