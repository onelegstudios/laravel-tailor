# Development

Notes for working on the package itself. None of this applies to using Tailor in an app.

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

## Maintenance scripts (`bin/`)

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

**A newly scanned icon needs a Heroicon chosen by hand.** The scan keeps the `hero` kit's keys in sync with the Lucide overrides it finds, but never fills in their values — there's no one-to-one Heroicon for every Lucide glyph, so the choice isn't the script's to make. A new key therefore arrives empty, and an empty value is skipped at run time rather than guessed at, which means the icon quietly stays Lucide under a kit whose whole job is to leave no Lucide behind. Fill the value in before committing what the scan wrote.

`tests/Integrity/ScanIconsTest.php` is what keeps the shape of the map honest afterwards; it won't tell you a value is a good likeness.
