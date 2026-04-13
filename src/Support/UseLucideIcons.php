<?php

namespace Onelegstudios\Tailor\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;

class UseLucideIcons
{
    private const LEGACY_ICONS = [
        'exclamation-triangle',
        'loading',
    ];

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly FluxBladeIconProcessor $fluxBladeIconProcessor,
    ) {}

    /**
     * @param  array<mixed>  $mappings
     * @param  callable(list<string>): int|bool|null  $publisher
     * @return array{filesUpdated: list<string>, iconsPublished: list<string>, warnings: list<string>}
     */
    public function handle(string $viewsRoot, string $iconRoot, array $mappings, callable $publisher): array
    {
        if (! $this->filesystem->isDirectory($viewsRoot)) {
            throw new RuntimeException("Views root [{$viewsRoot}] does not exist.");
        }

        $resolvedMappings = $this->resolveMappings($mappings);
        $scan = $this->fluxBladeIconProcessor->scanViews($viewsRoot, [$iconRoot]);
        $iconsToPublish = $this->resolveIconsToPublish($scan['icons'], $resolvedMappings);

        $this->publishIcons($iconsToPublish, $publisher);
        $this->ensurePublishedIconsExist($iconRoot, $iconsToPublish);
        $this->prepareLegacyIconAliases($iconRoot, $resolvedMappings);

        $updatedFiles = [];
        $warnings = [];

        foreach ($scan['files'] as $path) {
            $result = $this->fluxBladeIconProcessor->rewriteBladeIcons(
                $this->filesystem->get($path),
                $resolvedMappings,
                $path,
            );

            $warnings = [...$warnings, ...$result['warnings']];

            if (! $result['changed']) {
                continue;
            }

            $this->filesystem->replace($path, $result['blade']);
            $updatedFiles[] = $path;
        }

        return [
            'filesUpdated' => $updatedFiles,
            'iconsPublished' => $iconsToPublish,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * @param  array<string, string>  $mappings
     */
    private function duplicateLegacyIconAlias(string $iconRoot, array $mappings, string $legacyIcon): void
    {
        if (! array_key_exists($legacyIcon, $mappings)) {
            return;
        }

        $sourcePath = $this->iconBladePath($iconRoot, $mappings[$legacyIcon]);
        $legacyPath = $this->iconBladePath($iconRoot, $legacyIcon);

        $this->filesystem->ensureDirectoryExists(dirname($legacyPath));
        $this->filesystem->replace($legacyPath, $this->filesystem->get($sourcePath));
    }

    /**
     * @param  list<string>  $icons
     */
    private function ensurePublishedIconsExist(string $iconRoot, array $icons): void
    {
        $missingIcons = collect($icons)
            ->reject(fn (string $icon): bool => $this->filesystem->exists($this->iconBladePath($iconRoot, $icon)))
            ->values()
            ->all();

        if ($missingIcons === []) {
            return;
        }

        throw new RuntimeException('Published Lucide icon files are missing: '.implode(', ', $missingIcons));
    }

    private function ensureLoadingIconSpins(string $path): void
    {
        $contents = $this->filesystem->get($path);

        if (Str::contains($contents, 'animate-spin')) {
            return;
        }

        $updatedContents = str_replace(
            "Flux::classes('shrink-0')",
            "Flux::classes('shrink-0 animate-spin')",
            $contents,
            $replaceCount,
        );

        if ($replaceCount === 0) {
            throw new RuntimeException("Unable to add animate-spin to [{$path}].");
        }

        $this->filesystem->replace($path, $updatedContents);
    }

    private function iconBladePath(string $iconRoot, string $icon): string
    {
        return rtrim($iconRoot, '/').'/'.$icon.'.blade.php';
    }

    /**
     * @param  list<string>  $icons
     * @param  callable(list<string>): int|bool|null  $publisher
     */
    private function publishIcons(array $icons, callable $publisher): void
    {
        if ($icons === []) {
            return;
        }

        $result = $publisher($icons);

        if (($result === false) || (is_int($result) && $result !== 0)) {
            throw new RuntimeException('Publishing Lucide icons failed.');
        }
    }

    /**
     * @param  array<string, string>  $mappings
     */
    private function prepareLegacyIconAliases(string $iconRoot, array $mappings): void
    {
        if (array_key_exists('loading', $mappings)) {
            $this->ensureLoadingIconSpins($this->iconBladePath($iconRoot, $mappings['loading']));
        }

        $this->duplicateLegacyIconAlias($iconRoot, $mappings, 'exclamation-triangle');
        $this->duplicateLegacyIconAlias($iconRoot, $mappings, 'loading');

        if (! array_key_exists('loading', $mappings)) {
            return;
        }

        $this->ensureLoadingIconSpins($this->iconBladePath($iconRoot, 'loading'));
    }

    /**
     * @param  list<string>  $detectedIcons
     * @param  array<string, string>  $mappings
     * @return list<string>
     */
    private function resolveIconsToPublish(array $detectedIcons, array $mappings): array
    {
        $iconsToPublish = [];

        foreach ($detectedIcons as $icon) {
            if (array_key_exists($icon, $mappings)) {
                $iconsToPublish[] = $mappings[$icon];
            }
        }

        foreach (self::LEGACY_ICONS as $legacyIcon) {
            if (array_key_exists($legacyIcon, $mappings)) {
                $iconsToPublish[] = $mappings[$legacyIcon];
            }
        }

        return collect($iconsToPublish)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  array<mixed>  $mappings
     * @return array<string, string>
     */
    private function resolveMappings(array $mappings): array
    {
        if (isset($mappings['icons']) && is_array($mappings['icons']) && isset($mappings['icons']['mappings']) && is_array($mappings['icons']['mappings'])) {
            $mappings = $mappings['icons']['mappings'];
        } elseif (isset($mappings['mappings']) && is_array($mappings['mappings'])) {
            $mappings = $mappings['mappings'];
        }

        $resolvedMappings = [];

        foreach ($mappings as $icon => $target) {
            $normalizedIcon = $this->normalizeIconName($icon);
            $normalizedTarget = $this->normalizeIconName($target);

            if ($normalizedIcon === null || $normalizedTarget === null) {
                continue;
            }

            $resolvedMappings[$normalizedIcon] = $normalizedTarget;
        }

        ksort($resolvedMappings);

        return $resolvedMappings;
    }

    private function normalizeIconName(mixed $icon): ?string
    {
        if (! is_scalar($icon) && $icon !== null) {
            return null;
        }

        if ($icon === null) {
            return null;
        }

        $icon = trim((string) $icon);

        if ($icon === '') {
            return null;
        }

        return Str::lower($icon);
    }
}
