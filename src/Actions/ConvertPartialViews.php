<?php

namespace Onelegstudios\Tailor\Actions;

use Illuminate\Filesystem\Filesystem;

class ConvertPartialViews
{
    public function __construct(
        private readonly Filesystem $files,
    ) {}

    /**
     * Turn the starter kit's partials into Blade components: move each file out of
     * views/partials into views/components, preserving its subpath, and rewrite
     * every include of partials.head in the views and tests to the <x-head /> tag
     * it now resolves as.
     *
     * A partial is only converted when every reference to it is a plain, dataless
     * include directive — the one form whose meaning is preserved by the tag.
     * Anything else (an include carrying a data array, includeWhen, a view() call)
     * is left entirely alone, file and references both, and returned: the include
     * inherits the caller's scope in ways a tag cannot be assumed to reproduce, so
     * it is never guessed at.
     *
     * An include shares the caller's variables, a component does not, so a partial
     * that reads one is listed in $props and gains a props declaration; each caller
     * then passes the variable in explicitly (:title="$title ?? null"). A partial
     * missing from $props converts to a tag with no attributes.
     *
     * A target that already exists is never clobbered; its name is returned
     * instead. A missing partials/ folder and a missing tests/ folder are handled
     * gracefully, and conversion is idempotent: a second run finds no partials/
     * folder, moves nothing, and rewrites nothing.
     *
     * The rewrite pass keys off the partials converted in this same call, so an
     * interruption between moving and rewriting would leave stale includes on a
     * re-run; execute() runs synchronously, so this is not a concern in practice.
     *
     * @param  array<string, array<int, string>>  $props  partial name => the variables it reads
     * @return array<int, string> qualified names (partials.x) that could not be converted
     */
    public function execute(string $viewsPath, string $testsPath, array $props = []): array
    {
        $partialsPath = $viewsPath.'/partials';

        if (! $this->files->isDirectory($partialsPath)) {
            return [];
        }

        $candidates = $this->candidates($partialsPath);

        // Settled before anything moves: a partial may include another, so the
        // scan needs every partial still at the path it is referenced from.
        $unconvertible = $this->unconvertibleNames(
            $this->referencingFiles($viewsPath, $testsPath),
            $candidates,
        );

        $failed = [];
        $converted = [];

        foreach ($candidates as $relative => $name) {
            if (in_array($name, $unconvertible, true)) {
                $failed[] = 'partials.'.$name;

                continue;
            }

            $target = $viewsPath.'/components/'.$relative;

            if ($this->files->exists($target)) {
                $failed[] = 'partials.'.$name;

                continue;
            }

            $this->files->ensureDirectoryExists(dirname($target));
            $this->files->move($partialsPath.'/'.$relative, $target);

            $componentProps = $props[$name] ?? [];

            if ($componentProps !== []) {
                $this->declareProps($target, $componentProps);
            }

            $converted[$name] = $componentProps;
        }

        // The partials have moved, so the paths scanned above are stale — one
        // partial may include another and needs rewriting at its new home.
        $this->rewriteReferences($this->referencingFiles($viewsPath, $testsPath), $converted);

        $this->pruneEmptyDirectories($partialsPath);

        return $failed;
    }

    /**
     * Every file that could reference a partial: the Blade views, and the tests
     * when they exist.
     *
     * @return array<int, string>
     */
    private function referencingFiles(string $viewsPath, string $testsPath): array
    {
        $files = $this->bladeFiles($viewsPath);

        if ($this->files->isDirectory($testsPath)) {
            $files = array_merge($files, $this->phpFiles($testsPath));
        }

        return $files;
    }

    /**
     * The partials to convert, as the path relative to partials/ => the name each
     * one is included and resolves as. Keying by the relative path is what lets the
     * move loop find its source once the scan below has run.
     *
     * @return array<string, string>
     */
    private function candidates(string $partialsPath): array
    {
        $candidates = [];

        foreach ($this->files->allFiles($partialsPath) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relative = ltrim(str_replace($partialsPath, '', $file->getPathname()), '/');

            $candidates[$relative] = $this->dottedName($relative);
        }

        return $candidates;
    }

    /**
     * The partials that are referenced by something other than a plain, dataless
     * include directive — the only form the tag provably preserves.
     *
     * Counting all mentions of a qualified name against the mentions that sit in a
     * dataless include is what makes this fail safe: any other form (a data array,
     * includeWhen, a view() call) leaves a mention the include pattern does not
     * claim, the counts diverge, and the partial is left alone rather than
     * half-converted. A partial nothing references counts zero against zero and
     * converts.
     *
     * @param  array<int, string>  $files
     * @param  array<string, string>  $candidates
     * @return array<int, string>
     */
    private function unconvertibleNames(array $files, array $candidates): array
    {
        $mentions = [];
        $includes = [];

        foreach ($files as $path) {
            $contents = $this->files->get($path);

            foreach ($candidates as $name) {
                $mentions[$name] = ($mentions[$name] ?? 0) + preg_match_all($this->namePattern($name), $contents);
                $includes[$name] = ($includes[$name] ?? 0) + preg_match_all($this->includePattern($name), $contents);
            }
        }

        return array_values(array_filter(
            $candidates,
            fn (string $name): bool => ($mentions[$name] ?? 0) !== ($includes[$name] ?? 0),
        ));
    }

    /**
     * Rewrite every dataless include of a converted partial to the component tag
     * it now resolves as, passing each declared prop through from the caller's
     * scope so the variable the include used to inherit still reaches the
     * component.
     *
     * Matching the directive alone leaves the surrounding indentation untouched,
     * and anchoring on the exact converted names keeps an unconvertible partial
     * (never in $converted) provably unrewritten.
     *
     * @param  array<int, string>  $files
     * @param  array<string, array<int, string>>  $converted  name => the props it declares
     */
    private function rewriteReferences(array $files, array $converted): void
    {
        if ($converted === []) {
            return;
        }

        foreach ($files as $path) {
            $original = $this->files->get($path);
            $updated = $original;

            foreach ($converted as $name => $props) {
                $updated = preg_replace(
                    $this->includePattern($name),
                    $this->tag($name, $props),
                    $updated,
                ) ?? $updated;
            }

            if ($updated !== $original) {
                $this->files->put($path, $updated);
            }
        }
    }

    /**
     * The component tag a partial is rewritten to, forwarding each prop from the
     * caller's scope. The ?? null keeps a caller that never set the variable
     * rendering exactly as it did when the include simply found it unset.
     *
     * @param  array<int, string>  $props
     */
    private function tag(string $name, array $props): string
    {
        $attributes = '';

        foreach ($props as $prop) {
            $attributes .= ' :'.$prop.'="$'.$prop.' ?? null"';
        }

        return '<x-'.$name.$attributes.' />';
    }

    /**
     * Declare the variables the partial reads as props, so it keeps rendering them
     * now that it no longer inherits the caller's scope. Each defaults to null,
     * matching what the variable resolved to when a caller left it unset.
     *
     * @param  array<int, string>  $props
     */
    private function declareProps(string $path, array $props): void
    {
        $declarations = array_map(fn (string $prop): string => "'".$prop."' => null", $props);

        $this->files->put(
            $path,
            '@props(['.implode(', ', $declarations).'])'.PHP_EOL.PHP_EOL.$this->files->get($path),
        );
    }

    /**
     * Derive the name a partial is included and resolves as from its path relative
     * to partials/: drop the .blade.php extension and turn directory separators
     * into dots, so nested/head.blade.php is included as partials.nested.head and
     * renders as <x-nested.head>.
     */
    private function dottedName(string $relative): string
    {
        return str_replace('/', '.', substr($relative, 0, -strlen('.blade.php')));
    }

    /**
     * Matches every mention of the partial's qualified name. The lookarounds keep a
     * shorter name from matching inside a longer sibling — partials.head must not
     * match partials.head-scripts or partials.head.meta.
     */
    private function namePattern(string $name): string
    {
        return '/(?<![\w.])partials\.'.preg_quote($name, '/').'(?![\w.\-])/';
    }

    /**
     * Matches a dataless include directive for the partial in either quote style, tolerating
     * the whitespace a formatter may leave inside the parentheses. A trailing comma
     * falls outside the pattern, which is what makes an include carrying data read
     * as a mention this does not claim.
     */
    private function includePattern(string $name): string
    {
        return '/@include\(\s*[\'"]partials\.'.preg_quote($name, '/').'[\'"]\s*\)/';
    }

    /**
     * Remove the partials folder once its files have moved out, bottom-up. Unlike
     * pages/, partials/ is a plain views folder with no namespace registered
     * against it, so nothing is left behind by deleting it outright; a folder still
     * holding an unconvertible partial is kept.
     */
    private function pruneEmptyDirectories(string $partialsPath): void
    {
        $directories = $this->files->allDirectories($partialsPath);

        usort($directories, fn (string $a, string $b): int => substr_count($b, '/') <=> substr_count($a, '/'));

        foreach ([...$directories, $partialsPath] as $directory) {
            if ($this->files->files($directory) === [] && $this->files->directories($directory) === []) {
                $this->files->deleteDirectory($directory);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function bladeFiles(string $path): array
    {
        return $this->filesMatching($path, '.blade.php');
    }

    /**
     * @return array<int, string>
     */
    private function phpFiles(string $path): array
    {
        return $this->filesMatching($path, '.php');
    }

    /**
     * @return array<int, string>
     */
    private function filesMatching(string $path, string $suffix): array
    {
        $matches = [];

        foreach ($this->files->allFiles($path) as $file) {
            if (str_ends_with($file->getFilename(), $suffix)) {
                $matches[] = $file->getPathname();
            }
        }

        return $matches;
    }
}
