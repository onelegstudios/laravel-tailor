# Contributing

Thanks for taking the time to contribute! Bug reports, docs fixes, and pull requests are all welcome.

## Before you start

- **Found a bug?** Open an [issue](https://github.com/onelegstudios/laravel-tailor/issues/new/choose) using the bug template, with the steps to reproduce it.
- **Have a question or an idea?** Start a [discussion](https://github.com/onelegstudios/laravel-tailor/discussions) instead of an issue.
- **Found a security vulnerability?** Do **not** open a public issue — please follow the [security policy](https://github.com/onelegstudios/laravel-tailor/security/policy).

For anything larger than a bug fix, open an issue or discussion first so we can agree on the approach before you write the code.

## Getting set up

Tailor's test suite runs against [Flux Pro](https://fluxui.dev), which is a paid package pulled from a private Composer repository. **You need a Flux Pro license to install the dev dependencies**, so authenticate first:

```bash
composer config http-basic.composer.fluxui.dev your-email@example.com your-license-key
```

Then fork the repo, clone it, and install:

```bash
git clone git@github.com:your-username/laravel-tailor.git
cd laravel-tailor
composer install
```

The suite needs PHP 8.3 or newer. CI runs it on 8.3, 8.4, and 8.5 against Laravel 13, at both `prefer-lowest` and `prefer-stable`, so a dependency you rely on has to exist at the low end of its constraint too.

## Making a change

Work on a branch off `main` and open a pull request against `main`. A good pull request:

- **Does one thing.** Separate concerns are easier to review and to revert.
- **Comes with tests.** Every behavior change needs a test that fails without it.
- **Explains the why.** The what is in the diff; the reasoning belongs in the description.
- **Leaves `CHANGELOG.md` alone.** It's generated from GitHub release notes on tag, so hand edits get overwritten.

## Tests and checks

Run the full suite before you push — it's the same set of checks CI runs:

```bash
composer test
```

That's three steps, which you can also run on their own:

```bash
composer lint:check    # Pint, style check only
composer types:check   # PHPStan (level 5, over src/ config/ database/)
vendor/bin/pest --parallel
```

To fix style rather than just check it:

```bash
composer lint          # composer format is an alias
```

Please don't add new entries to `phpstan-baseline.neon` — it's empty, and the goal is to keep it that way. Fix the error instead.

## Writing tests

Tests are [Pest](https://pestphp.com), and `tests/` mirrors `src/`: a task in `src/Tasks/` is tested in `tests/Tasks/`, an action in `src/Actions/` in `tests/Actions/`. Match the sibling files for structure and naming.

```bash
vendor/bin/pest --filter=GroupComponents   # run one test file while you work
composer test-coverage                     # run with coverage
```

Two directories are not like the others:

- `tests/Fixtures/starter-kits` holds real downloaded copies of the three Livewire starter-kit variants (`main`, `components`, `teams`), which is what the tasks and kits run against. They're regenerated with `bin/download-fixtures`, not edited by hand.
- `tests/Integrity` guards the things that drift out of sync rather than break: that each fixture variant is present, and that the icon map in `config/tailor.php` matches what the fixtures and Flux actually use. If a change of yours touches icons, run `bin/scan-icons` and commit the config it writes.

Both scripts are documented in [docs/development.md](docs/development.md). An arch test also enforces that no `dd`, `dump`, or `ray` call makes it into the package, so clear out your debugging before you push.

## Code style

Pint handles formatting, so run `composer lint` and don't argue with it. Beyond that, follow the conventions already in the file you're editing — the repo has an `.editorconfig`, uses typed properties and return types throughout, and favors small single-purpose action classes under `src/Actions/`.

## Adding a kit or task

That's what Tailor is built for, and it's documented for package users in [docs/extending.md](docs/extending.md) — the same interfaces apply to a built-in one. In short: a kit implements `Onelegstudios\Tailor\Kits\UiKit`, a task implements `Onelegstudios\Tailor\Tasks\TailorTask`, both live under `src/`, and both need registering in `config/tailor.php` under `registry`.

For a built-in, please also:

- Add tests under `tests/Kits/` or `tests/Tasks/`, covering a run against the fixtures, a re-run (which must be a no-op), and whatever the task refuses to touch.
- Put any options under `settings.kits.{key}` or `settings.tasks.{key}` in `config/tailor.php`, keyed on `key()`.
- Document it in [docs/kits.md](docs/kits.md) or [docs/tasks.md](docs/tasks.md), including anything a user has to know that the code can't tell them.

Tailor rewrites people's application code, so a task that can't do the whole job should do none of it and report what it skipped, rather than guess and leave a half-tailored app behind. The existing tasks all follow that rule.
