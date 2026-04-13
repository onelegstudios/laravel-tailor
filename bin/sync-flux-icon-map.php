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
     *     config: array{mappings: array<mixed>, removed: array<mixed>},
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
        $existingConfig = $loadedConfig['config'];
        $previousMappings = $existingConfig['mappings'];

        $scan = self::scanViews($viewsRoot, $filesystem);
        $detected = self::mergeRequiredIcons($scan['icons']);

        $mappings = $previousMappings;

        foreach ($detected as $icon) {
            if (! array_key_exists($icon, $mappings)) {
                $mappings[$icon] = null;
            }
        }

        $mappings = self::sortMappings($mappings);

        $new = collect($detected)
            ->reject(fn (string $icon): bool => array_key_exists($icon, $previousMappings))
            ->values()
            ->all();

        $removed = collect($mappings)
            ->reject(fn (mixed $value, mixed $key): bool => in_array((string) $key, $detected, true))
            ->sortKeys()
            ->all();

        $config = [
            'mappings' => $mappings,
            'removed' => $removed,
        ];

        $filesystem->ensureDirectoryExists(dirname($configPath));
        $filesystem->replace($configPath, self::formatConfigFile(
            self::mergeRootConfig($loadedConfig['rootConfig'], $loadedConfig['iconRootConfig'], $config, $loadedConfig['wasWrapped'])
        ));

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
        $cwd = getcwd() ?: '.';

        return rtrim($cwd, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'tailor.php';
    }

    private static function defaultViewsRoot(): string
    {
        $cwd = getcwd() ?: '.';

        return rtrim($cwd, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views';
    }

    /**
     * @return array{
     *     config: array{mappings: array<mixed>, new: array<mixed>, removed: array<mixed>},
     *     iconRootConfig: array<mixed>,
     *     rootConfig: array<mixed>,
     *     wasWrapped: bool
     * }
     */
    private static function loadConfig(string $configPath, Filesystem $filesystem): array
    {
        if (! $filesystem->exists($configPath)) {
            return [
                'config' => self::normalizeConfig([]),
                'iconRootConfig' => [],
                'rootConfig' => [],
                'wasWrapped' => false,
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
                'config' => self::normalizeConfig($config),
                'iconRootConfig' => [],
                'rootConfig' => $config,
                'wasWrapped' => false,
            ];
        }

        if (! is_array($config[self::CONFIG_KEY])) {
            throw new RuntimeException("Config file [{$configPath}] section [".self::CONFIG_KEY.'] must be an array.');
        }

        $wrappedConfig = $config[self::CONFIG_KEY];

        self::assertConfigSections($wrappedConfig, $configPath, self::CONFIG_KEY.'.');

        return [
            'config' => self::normalizeConfig($wrappedConfig),
            'iconRootConfig' => $wrappedConfig,
            'rootConfig' => $config,
            'wasWrapped' => true,
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
     * @return array{mappings: array<mixed>, new: array<mixed>, removed: array<mixed>}
     */
    private static function normalizeConfig(array $config): array
    {
        return [
            'mappings' => self::sortMappings($config['mappings'] ?? []),
            'new' => collect($config['new'] ?? [])->sort()->values()->all(),
            'removed' => self::sortMappings($config['removed'] ?? []),
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
        return collect([...$icons, ...self::REQUIRED_ICONS])
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
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
     * @param  array{mappings: array<mixed>, removed: array<mixed>}  $config
     * @return array<mixed>
     */
    private static function mergeRootConfig(array $rootConfig, array $iconRootConfig, array $config, bool $wasWrapped): array
    {
        $mergedIconConfig = self::mergeIconConfig($wasWrapped ? $iconRootConfig : [], $config);

        if ($wasWrapped) {
            $rootConfig[self::CONFIG_KEY] = $mergedIconConfig;

            return $rootConfig;
        }

        $rootConfig = array_diff_key($rootConfig, array_flip(self::CONFIG_SECTIONS));
        $rootConfig[self::CONFIG_KEY] = $mergedIconConfig;

        return $rootConfig;
    }

    /**
     * @param  array<mixed>  $iconRootConfig
     * @param  array{mappings: array<mixed>, removed: array<mixed>}  $config
     * @return array<mixed>
     */
    private static function mergeIconConfig(array $iconRootConfig, array $config): array
    {
        $iconRootConfig = array_diff_key($iconRootConfig, array_flip(self::CONFIG_SECTIONS));
        $iconRootConfig['mappings'] = $config['mappings'];

        if ($config['removed'] !== []) {
            $iconRootConfig['removed'] = $config['removed'];
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
