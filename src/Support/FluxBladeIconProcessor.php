<?php

namespace Onelegstudios\Tailor\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;
use SplFileInfo;

class FluxBladeIconProcessor
{
    private const ICON_ATTRIBUTES = [
        'icon',
        'icon:leading',
        'icon:trailing',
        'icon-leading',
        'icon-trailing',
    ];

    public function __construct(
        private readonly Filesystem $filesystem,
    ) {}

    /**
     * @return array{icons: list<string>, warnings: list<string>}
     */
    public function extractIconsFromBlade(string $blade, string $source = '[inline]'): array
    {
        $icons = [];
        $warnings = [];

        foreach ($this->locateFluxOpeningTags($blade, $source) as $tag) {
            $result = $this->extractIconsFromTag($tag['content'], $source);

            $icons = [...$icons, ...$result['icons']];
            $warnings = [...$warnings, ...$result['warnings']];
        }

        return [
            'icons' => array_values(collect($icons)
                ->filter()
                ->unique()
                ->sort()
                ->all()),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * @param  list<string>  $excludedRoots
     * @return array{files: list<string>, icons: list<string>, warnings: list<string>}
     */
    public function scanViews(string $viewsRoot, array $excludedRoots = []): array
    {
        $icons = [];
        $warnings = [];
        $normalizedExcludedRoots = array_values(collect($excludedRoots)
            ->map(fn (string $path): string => $this->normalizePath($path))
            ->filter()
            ->all());

        $bladeFiles = array_values(collect($this->filesystem->allFiles($viewsRoot))
            ->filter(fn (SplFileInfo $file): bool => Str::endsWith($file->getFilename(), '.blade.php'))
            ->map(fn (SplFileInfo $file): string => $file->getPathname())
            ->reject(fn (string $path): bool => $this->pathIsExcluded($path, $normalizedExcludedRoots))
            ->sort()
            ->all());

        foreach ($bladeFiles as $path) {
            $result = $this->extractIconsFromBlade($this->filesystem->get($path), $path);

            $icons = [...$icons, ...$result['icons']];
            $warnings = [...$warnings, ...$result['warnings']];
        }

        return [
            'files' => $bladeFiles,
            'icons' => array_values(collect($icons)
                ->filter()
                ->unique()
                ->sort()
                ->all()),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * @param  array<mixed>  $mappings
     * @return array{blade: string, changed: bool, warnings: list<string>}
     */
    public function rewriteBladeIcons(string $blade, array $mappings, string $source = '[inline]'): array
    {
        $rewrittenBlade = $blade;
        $warnings = [];
        $normalizedMappings = $this->normalizeMappings($mappings);

        foreach (array_reverse($this->locateFluxOpeningTags($blade, $source)) as $tag) {
            $result = $this->rewriteTag($tag['content'], $normalizedMappings, $source);
            $warnings = [...$warnings, ...$result['warnings']];

            if (! $result['changed']) {
                continue;
            }

            $rewrittenBlade = substr($rewrittenBlade, 0, $tag['start'])
                .$result['tag']
                .substr($rewrittenBlade, $tag['end'] + 1);
        }

        return [
            'blade' => $rewrittenBlade,
            'changed' => $rewrittenBlade !== $blade,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * @return list<array{start: int, end: int}>
     */
    private function commentRanges(string $blade): array
    {
        preg_match_all('/\{\{--.*?--\}\}|<!--.*?-->/s', $blade, $matches, PREG_OFFSET_CAPTURE);

        return array_values(collect($matches[0])
            ->map(fn (array $match): array => [
                'start' => $match[1],
                'end' => $match[1] + strlen($match[0]) - 1,
            ])
            ->all());
    }

    /**
     * @return array{icons: list<string>, warnings: list<string>}
     */
    private function extractIconsFromTag(string $tag, string $source): array
    {
        $parsedTag = $this->parseOpeningTag($tag);
        $tagName = $parsedTag['name'];
        $icons = [];
        $warnings = [];

        if (Str::startsWith($tagName, 'flux:icon.')) {
            $icon = $this->normalizeIconName(Str::after($tagName, 'flux:icon.'));

            return [
                'icons' => $icon === null ? [] : [$icon],
                'warnings' => [],
            ];
        }

        if (! Str::startsWith($tagName, 'flux:')) {
            return [
                'icons' => [],
                'warnings' => [],
            ];
        }

        $allowedAttributes = $tagName === 'flux:icon'
            ? ['name', 'icon']
            : self::ICON_ATTRIBUTES;

        foreach ($parsedTag['attributes'] as $attribute) {
            $normalizedAttributeName = ltrim($attribute['name'], ':');

            if (! in_array($normalizedAttributeName, $allowedAttributes, true)) {
                continue;
            }

            if (Str::startsWith($attribute['name'], ':')) {
                $boundIcons = $attribute['value'] === null
                    ? []
                    : $this->extractIconsFromBoundExpression($attribute['value']);

                if ($boundIcons === []) {
                    $warnings[] = sprintf(
                        '%s: skipped unresolved %s on <%s>',
                        $source,
                        $attribute['name'],
                        $tagName,
                    );

                    continue;
                }

                $icons = [...$icons, ...$boundIcons];

                continue;
            }

            $icon = $this->normalizeIconName($attribute['value']);

            if ($icon !== null) {
                $icons[] = $icon;
            }
        }

        return [
            'icons' => array_values(collect($icons)
                ->filter()
                ->unique()
                ->sort()
                ->all()),
            'warnings' => $warnings,
        ];
    }

    /**
     * @return list<string>
     */
    private function extractIconsFromBoundExpression(string $expression): array
    {
        $literal = $this->locateStringLiteralSegment($expression, 0, strlen($expression) - 1);

        if ($literal !== null) {
            $icon = $this->normalizeIconName($literal['value']);

            return $icon === null ? [] : [$icon];
        }

        $ternary = $this->locateSimpleTernaryBranches($expression);

        if ($ternary === null) {
            return [];
        }

        $icons = [];

        foreach (['truthy', 'falsey'] as $branch) {
            $branchLiteral = $this->locateStringLiteralSegment(
                $expression,
                $ternary[$branch]['start'],
                $ternary[$branch]['end'],
            );

            if ($branchLiteral === null) {
                return [];
            }

            $icon = $this->normalizeIconName($branchLiteral['value']);

            if ($icon === null) {
                return [];
            }

            $icons[] = $icon;
        }

        return array_values(collect($icons)
            ->unique()
            ->sort()
            ->all());
    }

    private function findTagEnd(string $blade, int $start): ?int
    {
        $quote = null;
        $length = strlen($blade);

        for ($index = $start + 1; $index < $length; $index++) {
            $character = $blade[$index];

            if ($quote !== null) {
                if ($character === '\\') {
                    $index++;

                    continue;
                }

                if ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '\'' || $character === '"') {
                $quote = $character;

                continue;
            }

            if ($character === '>') {
                return $index;
            }
        }

        return null;
    }

    private function findTopLevelCharacter(string $expression, string $target, int $offset = 0): ?int
    {
        $quote = null;
        $depth = 0;
        $length = strlen($expression);

        for ($index = $offset; $index < $length; $index++) {
            $character = $expression[$index];

            if ($quote !== null) {
                if ($character === '\\') {
                    $index++;

                    continue;
                }

                if ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '\'' || $character === '"') {
                $quote = $character;

                continue;
            }

            if ($character === '(' || $character === '[' || $character === '{') {
                $depth++;

                continue;
            }

            if ($character === ')' || $character === ']' || $character === '}') {
                $depth = max(0, $depth - 1);

                continue;
            }

            if ($depth !== 0 || $character !== $target) {
                continue;
            }

            if ($target === '?' && ($expression[$index + 1] ?? null) === '?') {
                continue;
            }

            if ($target === '?' && ($expression[$index + 1] ?? null) === '-') {
                continue;
            }

            return $index;
        }

        return null;
    }

    /**
     * @return list<array{start: int, end: int, content: string}>
     */
    private function locateFluxOpeningTags(string $blade, string $source): array
    {
        $commentRanges = $this->commentRanges($blade);
        $tags = [];
        $offset = 0;

        while (($start = strpos($blade, '<flux:', $offset)) !== false) {
            $commentRange = $this->findRangeContainingOffset($commentRanges, $start);

            if ($commentRange !== null) {
                $offset = $commentRange['end'] + 1;

                continue;
            }

            $end = $this->findTagEnd($blade, $start);

            if ($end === null) {
                throw new RuntimeException("Unable to parse a Flux tag in [{$source}].");
            }

            $tags[] = [
                'start' => $start,
                'end' => $end,
                'content' => substr($blade, $start, ($end - $start) + 1),
            ];

            $offset = $end + 1;
        }

        return $tags;
    }

    /**
     * @param  list<array{start: int, end: int}>  $ranges
     * @return array{start: int, end: int}|null
     */
    private function findRangeContainingOffset(array $ranges, int $offset): ?array
    {
        foreach ($ranges as $range) {
            if ($offset < $range['start']) {
                return null;
            }

            if ($offset <= $range['end']) {
                return $range;
            }
        }

        return null;
    }

    /**
     * @return array{truthy: array{start: int, end: int}, falsey: array{start: int, end: int}}|null
     */
    private function locateSimpleTernaryBranches(string $expression): ?array
    {
        $questionMark = $this->findTopLevelCharacter($expression, '?');

        if ($questionMark === null) {
            return null;
        }

        $colon = $this->findTopLevelCharacter($expression, ':', $questionMark + 1);

        if ($colon === null) {
            return null;
        }

        $truthyBounds = $this->trimSegmentBounds($expression, $questionMark + 1, $colon - 1);
        $falseyBounds = $this->trimSegmentBounds($expression, $colon + 1, strlen($expression) - 1);

        if ($truthyBounds === null || $falseyBounds === null) {
            return null;
        }

        return [
            'truthy' => [
                'start' => $truthyBounds[0],
                'end' => $truthyBounds[1],
            ],
            'falsey' => [
                'start' => $falseyBounds[0],
                'end' => $falseyBounds[1],
            ],
        ];
    }

    /**
     * @return array{start: int, end: int, quote: string, value: string}|null
     */
    private function locateStringLiteralSegment(string $expression, int $start, int $end): ?array
    {
        $bounds = $this->trimSegmentBounds($expression, $start, $end);

        if ($bounds === null) {
            return null;
        }

        [$segmentStart, $segmentEnd] = $bounds;
        $quote = $expression[$segmentStart] ?? null;

        if (($quote !== '\'' && $quote !== '"') || ($expression[$segmentEnd] ?? null) !== $quote) {
            return null;
        }

        return [
            'start' => $segmentStart,
            'end' => $segmentEnd,
            'quote' => $quote,
            'value' => substr($expression, $segmentStart + 1, $segmentEnd - $segmentStart - 1),
        ];
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

        if ($this->isWrappedInMatchingQuotes($icon)) {
            $icon = trim(substr($icon, 1, -1));
        }

        return $icon === '' ? null : Str::lower($icon);
    }

    /**
     * @param  array<mixed>  $mappings
     * @return array<string, string>
     */
    private function normalizeMappings(array $mappings): array
    {
        $normalizedMappings = [];

        foreach ($mappings as $icon => $target) {
            $normalizedIcon = $this->normalizeIconName($icon);
            $normalizedTarget = $this->normalizeIconName($target);

            if ($normalizedIcon === null || $normalizedTarget === null) {
                continue;
            }

            $normalizedMappings[$normalizedIcon] = $normalizedTarget;
        }

        ksort($normalizedMappings);

        return $normalizedMappings;
    }

    private function normalizePath(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }

    /**
     * @return array{
     *     name: string,
     *     nameStart: int,
     *     nameEnd: int,
     *     attributes: list<array{
     *         name: string,
     *         value: string|null,
     *         valueStart: int|null,
     *         valueEnd: int|null
     *     }>
     * }
     */
    private function parseOpeningTag(string $tag): array
    {
        $length = strlen($tag);
        $index = 1;

        while ($index < $length && ctype_space($tag[$index])) {
            $index++;
        }

        $nameStart = $index;

        while (
            $index < $length
            && ! ctype_space($tag[$index])
            && $tag[$index] !== '>'
            && ! $this->isSelfClosingTagTerminator($tag, $index)
        ) {
            $index++;
        }

        $nameEnd = $index - 1;
        $attributes = [];

        while ($index < $length) {
            while ($index < $length && ctype_space($tag[$index])) {
                $index++;
            }

            if ($index >= $length || $tag[$index] === '>') {
                break;
            }

            if ($tag[$index] === '/' && ($tag[$index + 1] ?? null) === '>') {
                break;
            }

            $attributeNameStart = $index;

            while (
                $index < $length
                && ! ctype_space($tag[$index])
                && $tag[$index] !== '='
                && $tag[$index] !== '>'
            ) {
                $index++;
            }

            $attributeName = substr($tag, $attributeNameStart, $index - $attributeNameStart);

            while ($index < $length && ctype_space($tag[$index])) {
                $index++;
            }

            if ($attributeName === '') {
                continue;
            }

            if ($index >= $length || $tag[$index] !== '=') {
                $attributes[] = [
                    'name' => $attributeName,
                    'value' => null,
                    'valueStart' => null,
                    'valueEnd' => null,
                ];

                continue;
            }

            $index++;

            while ($index < $length && ctype_space($tag[$index])) {
                $index++;
            }

            if ($index >= $length) {
                $attributes[] = [
                    'name' => $attributeName,
                    'value' => '',
                    'valueStart' => $index,
                    'valueEnd' => $index,
                ];

                break;
            }

            $quote = $tag[$index];

            if ($quote === '\'' || $quote === '"') {
                $valueStart = $index + 1;
                $index++;

                while ($index < $length) {
                    if ($tag[$index] === '\\') {
                        $index += 2;

                        continue;
                    }

                    if ($tag[$index] === $quote) {
                        break;
                    }

                    $index++;
                }

                $valueEnd = $index - 1;
                $attributes[] = [
                    'name' => $attributeName,
                    'value' => substr($tag, $valueStart, max(0, $valueEnd - $valueStart + 1)),
                    'valueStart' => $valueStart,
                    'valueEnd' => $valueEnd,
                ];

                if ($index < $length && $tag[$index] === $quote) {
                    $index++;
                }

                continue;
            }

            $valueStart = $index;

            while (
                $index < $length
                && ! ctype_space($tag[$index])
                && $tag[$index] !== '>'
                && ! $this->isSelfClosingTagTerminator($tag, $index)
            ) {
                $index++;
            }

            $valueEnd = $index - 1;
            $attributes[] = [
                'name' => $attributeName,
                'value' => substr($tag, $valueStart, $valueEnd - $valueStart + 1),
                'valueStart' => $valueStart,
                'valueEnd' => $valueEnd,
            ];
        }

        return [
            'name' => substr($tag, $nameStart, max(0, $nameEnd - $nameStart + 1)),
            'nameStart' => $nameStart,
            'nameEnd' => $nameEnd,
            'attributes' => $attributes,
        ];
    }

    private function isSelfClosingTagTerminator(string $tag, int $index): bool
    {
        return $tag[$index] === '/' && ($tag[$index + 1] ?? null) === '>';
    }

    /**
     * @param  list<string>  $excludedRoots
     */
    private function pathIsExcluded(string $path, array $excludedRoots): bool
    {
        $normalizedPath = $this->normalizePath($path);

        foreach ($excludedRoots as $excludedRoot) {
            if ($normalizedPath === $excludedRoot || Str::startsWith($normalizedPath, $excludedRoot.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, string>  $mappings
     * @param  list<array{start: int, end: int, quote: string, value: string}>  $literals
     */
    private function replaceExpressionLiterals(string $expression, array $mappings, array $literals): ?string
    {
        $rewrittenExpression = $expression;

        foreach (array_reverse($literals) as $literal) {
            $icon = $this->normalizeIconName($literal['value']);

            if ($icon === null) {
                return null;
            }

            if (! array_key_exists($icon, $mappings)) {
                return null;
            }

            $replacement = $mappings[$icon];
            $literalValue = $literal['quote'].$replacement.$literal['quote'];

            $rewrittenExpression = substr($rewrittenExpression, 0, $literal['start'])
                .$literalValue
                .substr($rewrittenExpression, $literal['end'] + 1);
        }

        return $rewrittenExpression;
    }

    /**
     * @param  array<string, string>  $mappings
     * @return array{tag: string, changed: bool, warnings: list<string>}
     */
    private function rewriteTag(string $tag, array $mappings, string $source): array
    {
        $parsedTag = $this->parseOpeningTag($tag);
        $tagName = $parsedTag['name'];
        $replacements = [];
        $warnings = [];

        if (Str::startsWith($tagName, 'flux:icon.')) {
            $icon = $this->normalizeIconName(Str::after($tagName, 'flux:icon.'));
            $replacement = $icon === null ? null : ($mappings[$icon] ?? null);

            if ($replacement !== null) {
                $replacements[] = [
                    'start' => $parsedTag['nameStart'],
                    'end' => $parsedTag['nameEnd'],
                    'value' => 'flux:icon.'.$replacement,
                ];
            }
        }

        if (! Str::startsWith($tagName, 'flux:')) {
            return [
                'tag' => $tag,
                'changed' => false,
                'warnings' => [],
            ];
        }

        $allowedAttributes = $tagName === 'flux:icon'
            ? ['name', 'icon']
            : self::ICON_ATTRIBUTES;

        foreach ($parsedTag['attributes'] as $attribute) {
            $normalizedAttributeName = ltrim($attribute['name'], ':');

            if (
                ! in_array($normalizedAttributeName, $allowedAttributes, true)
                || $attribute['value'] === null
                || $attribute['valueStart'] === null
                || $attribute['valueEnd'] === null
            ) {
                continue;
            }

            if (Str::startsWith($attribute['name'], ':')) {
                $rewrittenExpression = $this->rewriteBoundExpression($attribute['value'], $mappings);

                if ($rewrittenExpression === null) {
                    $warnings[] = sprintf(
                        '%s: skipped unresolved %s on <%s>',
                        $source,
                        $attribute['name'],
                        $tagName,
                    );

                    continue;
                }

                if ($rewrittenExpression !== $attribute['value']) {
                    $replacements[] = [
                        'start' => $attribute['valueStart'],
                        'end' => $attribute['valueEnd'],
                        'value' => $rewrittenExpression,
                    ];
                }

                continue;
            }

            $icon = $this->normalizeIconName($attribute['value']);
            $replacement = $icon === null ? null : ($mappings[$icon] ?? null);

            if ($replacement === null) {
                continue;
            }

            $replacements[] = [
                'start' => $attribute['valueStart'],
                'end' => $attribute['valueEnd'],
                'value' => $replacement,
            ];
        }

        if ($replacements === []) {
            return [
                'tag' => $tag,
                'changed' => false,
                'warnings' => $warnings,
            ];
        }

        $rewrittenTag = $tag;

        foreach (array_reverse($replacements) as $replacement) {
            $rewrittenTag = substr($rewrittenTag, 0, $replacement['start'])
                .$replacement['value']
                .substr($rewrittenTag, $replacement['end'] + 1);
        }

        return [
            'tag' => $rewrittenTag,
            'changed' => $rewrittenTag !== $tag,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, string>  $mappings
     */
    private function rewriteBoundExpression(string $expression, array $mappings): ?string
    {
        $literal = $this->locateStringLiteralSegment($expression, 0, strlen($expression) - 1);

        if ($literal !== null) {
            return $this->replaceExpressionLiterals($expression, $mappings, [$literal]);
        }

        $ternary = $this->locateSimpleTernaryBranches($expression);

        if ($ternary === null) {
            return null;
        }

        $literals = [];

        foreach (['truthy', 'falsey'] as $branch) {
            $branchLiteral = $this->locateStringLiteralSegment(
                $expression,
                $ternary[$branch]['start'],
                $ternary[$branch]['end'],
            );

            if ($branchLiteral === null) {
                return null;
            }

            $literals[] = $branchLiteral;
        }

        return $this->replaceExpressionLiterals($expression, $mappings, $literals);
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private function trimSegmentBounds(string $expression, int $start, int $end): ?array
    {
        if ($end < $start) {
            return null;
        }

        while ($start <= $end && ctype_space($expression[$start])) {
            $start++;
        }

        while ($end >= $start && ctype_space($expression[$end])) {
            $end--;
        }

        return $start > $end ? null : [$start, $end];
    }

    private function isWrappedInMatchingQuotes(string $value): bool
    {
        if (strlen($value) < 2) {
            return false;
        }

        $firstCharacter = $value[0];
        $lastCharacter = $value[strlen($value) - 1];

        return ($firstCharacter === '\'' && $lastCharacter === '\'')
            || ($firstCharacter === '"' && $lastCharacter === '"');
    }
}
