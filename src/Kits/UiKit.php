<?php

namespace Onelegstudios\Tailor\Kits;

use Illuminate\Console\OutputStyle;

/**
 * A mutually exclusive UI kit the user can tailor the starter kit to. Each kit
 * is registered in config('tailor.registry.kits') and resolved by its key().
 */
interface UiKit
{
    /**
     * Stable kebab-case identifier — the config key, the --ui-kit value, and
     * the value returned from the prompt.
     */
    public function key(): string;

    /**
     * Human-readable label shown in the UI kit prompt.
     */
    public function label(): string;

    /**
     * Apply the kit to the starter kit.
     *
     * @return array<int, string> items that could not be applied (e.g. icons that failed to download)
     */
    public function apply(?OutputStyle $output = null): array;
}
