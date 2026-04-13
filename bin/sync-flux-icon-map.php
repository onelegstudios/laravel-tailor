#!/usr/bin/env php
<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Support\FluxBladeIconProcessor;

foreach ([
    __DIR__.'/../vendor/autoload.php',
    __DIR__.'/../../../autoload.php',
] as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;

        break;
    }
}

final class FluxIconMapSync
{
    private const CONFIG_KEY = 'icons';

    private const ICON_NAME_PATTERN = '/^[a-z0-9-]+$/';

    private const CONFIG_SECTIONS = [
        'mappings',
        'new',
        'removed',
    ];

    private const REQUIRED_ICONS = [
        'exclamation-triangle',
        'loading',
    ];

    public static function main(): int
    {
        try {
            $summary = self::sync();

            fwrite(STDOUT, sprintf(
                "Synced Flux icon map.\nDetected: %d\nNew: %d\nRemoved: %d\nConfig: %s\n",
                count($summary['detected']),
                count($summary['new']),
                count($summary['removed']),
                $summary['configPath'],
            ));

            if ($summary['warnings'] !== []) {
                fwrite(STDOUT, "\nWarnings:\n");

                foreach ($summary['warnings'] as $warning) {
                    fwrite(STDOUT, "- {$warning}\n");
                }
            }

            return 0;
        } catch (Throwable $throwable) {
            fwrite(STDERR, $throwable->getMessage().PHP_EOL);

            return 1;
        }
    }

    /**
     * @return array{
     *     config: array<mixed>,
     *     configPath: string,
     *     detected: list<string>,
     *     new: list<string>,
     *     removed: array<mixed>,
     *     warnings: list<string>
     * }
     */
    public static function sync(?string $viewsRoot = null, ?string $configPath = null, ?Filesystem $filesystem = null): array
    {
        $filesystem ??= new Filesystem;
        $viewsRoot ??= self::defaultViewsRoot();
        $configPath ??= self::defaultConfigPath();

        if (! $filesystem->isDirectory($viewsRoot)) {
            throw new RuntimeException("Views root [{$viewsRoot}] does not exist.");
        }

        $loadedConfig = self::loadConfig($configPath, $filesystem);
        $existingIconConfig = $loadedConfig['iconConfig'];
        $previousMappings = $existingIconConfig['mappings'];

        $scan = self::scanViews($viewsRoot, $filesystem);
        $detected = self::mergeRequiredIcons($scan['icons']);

        $mappings = $previousMappings;

        foreach ($detected as $icon) {
            if (! array_key_exists($icon, $mappings)) {
                $mappings[$icon] = null;
            }
        }

        $mappings = self::sortMappings($mappings);

        $new = array_values(collect($detected)
            ->reject(fn (string $icon): bool => array_key_exists($icon, $previousMappings))
            ->all());

        $removed = collect($mappings)
            ->reject(fn (mixed $value, mixed $key): bool => in_array((string) $key, $detected, true))
            ->sortKeys()
            ->all();

        $iconConfig = [
            'mappings' => $mappings,
            'removed' => $removed,
        ];

        $config = self::mergeRootConfig(
            $loadedConfig['rootConfig'],
            $loadedConfig['iconRootConfig'],
            $iconConfig,
        );

        $filesystem->ensureDirectoryExists(dirname($configPath));
        $filesystem->replace($configPath, self::formatConfigFile($config));

        return [
            'config' => $config,
            'configPath' => $configPath,
            'detected' => $detected,
            'new' => $new,
            'removed' => $removed,
            'warnings' => $scan['warnings'],
        ];
    }

    /**
     * @return array{icons: list<string>, warnings: list<string>}
     */
    public static function extractIconsFromBlade(string $blade, string $source = '[inline]'): array
    {
        return self::bladeIconProcessor()->extractIconsFromBlade($blade, $source);
    }

    private static function defaultConfigPath(): string
    {
        return self::defaultProjectRoot()
            .DIRECTORY_SEPARATOR.'config'
            .DIRECTORY_SEPARATOR.'tailor.php';
    }

    private static function defaultViewsRoot(): string
    {
        return self::defaultProjectRoot()
            .DIRECTORY_SEPARATOR.'workbench'
            .DIRECTORY_SEPARATOR.'resources'
            .DIRECTORY_SEPARATOR.'views';
    }

    private static function defaultProjectRoot(): string
    {
        return dirname(__DIR__);
    }

    /**
     * @return array{
     *     iconConfig: array{mappings: array<mixed>, new: list<string>, removed: array<mixed>},
     *     iconRootConfig: array<mixed>,
     *     rootConfig: array<mixed>
     * }
     */
    private static function loadConfig(string $configPath, Filesystem $filesystem): array
    {
        if (! $filesystem->exists($configPath)) {
            return [
                'iconConfig' => self::normalizeConfig([]),
                'iconRootConfig' => [],
                'rootConfig' => [],
            ];
        }

        $config = (static function (string $path): mixed {
            return require $path;
        })($configPath);

        if (! is_array($config)) {
            throw new RuntimeException("Config file [{$configPath}] must return an array.");
        }

        if (! array_key_exists(self::CONFIG_KEY, $config)) {
            self::assertConfigSections($config, $configPath);

            return [
                'iconConfig' => self::normalizeConfig($config),
                'iconRootConfig' => [],
                'rootConfig' => $config,
            ];
        }

        if (! is_array($config[self::CONFIG_KEY])) {
            throw new RuntimeException("Config file [{$configPath}] section [".self::CONFIG_KEY.'] must be an array.');
        }

        $wrappedConfig = $config[self::CONFIG_KEY];

        self::assertConfigSections($wrappedConfig, $configPath, self::CONFIG_KEY.'.');

        return [
            'iconConfig' => self::normalizeConfig($wrappedConfig),
            'iconRootConfig' => $wrappedConfig,
            'rootConfig' => $config,
        ];
    }

    /**
     * @param  array<mixed>  $config
     */
    private static function assertConfigSections(array $config, string $configPath, string $prefix = ''): void
    {
        foreach (self::CONFIG_SECTIONS as $section) {
            if (array_key_exists($section, $config) && ! is_array($config[$section])) {
                throw new RuntimeException("Config file [{$configPath}] section [{$prefix}{$section}] must be an array.");
            }
        }
    }

    private static function bladeIconProcessor(?Filesystem $filesystem = null): FluxBladeIconProcessor
    {
        return new FluxBladeIconProcessor($filesystem ?? new Filesystem);
    }

    /**
     * @param  array<mixed>  $config
     * @return array{mappings: array<mixed>, new: list<string>, removed: array<mixed>}
     */
    private static function normalizeConfig(array $config): array
    {
        return [
            'mappings' => self::normalizeMappings($config['mappings'] ?? []),
            'new' => self::normalizeStringList($config['new'] ?? []),
            'removed' => self::normalizeMappings($config['removed'] ?? []),
        ];
    }

    /**
     * @return array{icons: list<string>, warnings: list<string>}
     */
    private static function scanViews(string $viewsRoot, Filesystem $filesystem): array
    {
        $scan = self::bladeIconProcessor($filesystem)->scanViews($viewsRoot);

        return [
            'icons' => $scan['icons'],
            'warnings' => $scan['warnings'],
        ];
    }

    /**
     * @param  list<string>  $icons
     * @return list<string>
     */
    private static function mergeRequiredIcons(array $icons): array
    {
        return self::normalizeStringList([...$icons, ...self::REQUIRED_ICONS]);
    }

    /**
     * @return list<string>
     */
    private static function normalizeStringList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        $normalized = [];

        foreach ($values as $value) {
            $value = self::normalizeIconName($value);

            if ($value === null) {
                continue;
            }

            $normalized[] = $value;
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized;
    }

    /**
     * @return array<string, string|null>
     */
    private static function normalizeMappings(mixed $mappings): array
    {
        if (! is_array($mappings)) {
            return [];
        }

        $normalized = [];

        foreach ($mappings as $icon => $target) {
            $normalizedIcon = self::normalizeIconName($icon);

            if ($normalizedIcon === null) {
                continue;
            }

            $normalizedTarget = $target === null ? null : self::normalizeIconName($target);

            if ($target !== null && $normalizedTarget === null) {
                continue;
            }

            if (! array_key_exists($normalizedIcon, $normalized) || ($normalized[$normalizedIcon] === null && $normalizedTarget !== null)) {
                $normalized[$normalizedIcon] = $normalizedTarget;
            }
        }

        return self::sortMappings($normalized);
    }

    private static function normalizeIconName(mixed $value): ?string
    {
        if (! is_scalar($value) && $value !== null) {
            return null;
        }

        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim((string) $value));
        $normalized = rtrim($normalized, '/');

        if ($normalized === '') {
            return null;
        }

        if (preg_match(self::ICON_NAME_PATTERN, $normalized) !== 1) {
            return null;
        }

        return $normalized;
    }

    /**
     * @param  array<mixed>  $mappings
     * @return array<mixed>
     */
    private static function sortMappings(array $mappings): array
    {
        return collect($mappings)
            ->sortKeys()
            ->all();
    }

    /**
     * @param  array<mixed>  $rootConfig
     * @param  array<mixed>  $iconRootConfig
     * @param  array{mappings: array<mixed>, removed: array<mixed>}  $iconConfig
     * @return array<mixed>
     */
    private static function mergeRootConfig(array $rootConfig, array $iconRootConfig, array $iconConfig): array
    {
        $rootConfig = array_diff_key($rootConfig, array_flip(self::CONFIG_SECTIONS));
        $rootConfig[self::CONFIG_KEY] = self::mergeIconConfig($iconRootConfig, $iconConfig);

        return $rootConfig;
    }

    /**
     * @param  array<mixed>  $iconRootConfig
     * @param  array{mappings: array<mixed>, removed: array<mixed>}  $iconConfig
     * @return array<mixed>
     */
    private static function mergeIconConfig(array $iconRootConfig, array $iconConfig): array
    {
        $iconRootConfig = array_diff_key($iconRootConfig, array_flip(self::CONFIG_SECTIONS));
        $iconRootConfig['mappings'] = $iconConfig['mappings'];

        if ($iconConfig['removed'] !== []) {
            $iconRootConfig['removed'] = $iconConfig['removed'];
        }

        return $iconRootConfig;
    }

    /**
     * @param  array<mixed>  $config
     */
    private static function formatConfigFile(array $config): string
    {
        return "<?php\n\nreturn ".self::exportPhpValue($config).";\n";
    }

    private static function exportPhpValue(mixed $value, int $indent = 0): string
    {
        if (! is_array($value)) {
            return $value === null ? 'null' : var_export($value, true);
        }

        if ($value === []) {
            return '[]';
        }

        $indentation = str_repeat('    ', $indent + 1);
        $closingIndentation = str_repeat('    ', $indent);
        $isList = array_is_list($value);
        $lines = [];

        foreach ($value as $key => $item) {
            $exportedItem = self::exportPhpValue($item, $indent + 1);

            $lines[] = $isList
                ? "{$indentation}{$exportedItem},"
                : "{$indentation}".var_export($key, true)." => {$exportedItem},";
        }

        return "[\n".implode("\n", $lines)."\n{$closingIndentation}]";
    }
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    exit(FluxIconMapSync::main());
}

return FluxIconMapSync::class;
