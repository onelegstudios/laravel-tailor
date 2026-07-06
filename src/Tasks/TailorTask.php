<?php

namespace Onelegstudios\Tailor\Tasks;

use Illuminate\Console\OutputStyle;

/**
 * An independent tailoring task the user can opt into alongside a UI kit. Each
 * task is registered in config('tailor.registry.tasks') and resolved by its key().
 */
interface TailorTask
{
    /**
     * Stable kebab-case identifier — the config key and the value returned from
     * the prompt.
     */
    public function key(): string;

    /**
     * Human-readable label shown in the "what else" prompt.
     */
    public function label(): string;

    /**
     * Run the task.
     *
     * @return array<int, string> items that could not be applied
     */
    public function apply(?OutputStyle $output = null): array;
}
