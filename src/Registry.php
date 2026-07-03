<?php

namespace Onelegstudios\Tailor;

use Illuminate\Contracts\Container\Container;
use Onelegstudios\Tailor\Kits\UiKit;
use Onelegstudios\Tailor\Tasks\TailorTask;

/**
 * Resolves the registered kits/tasks from config, the single source of truth.
 * For each listed class it prefers an app class of the same short name (dropped
 * into the app/Tailor folder), falls back to the listed package class, and
 * silently ignores the entry when it exists in neither place.
 */
class Registry
{
    public function __construct(
        private readonly Container $container,
    ) {}

    /**
     * Instantiate each registered class — preferring an app override in
     * $namespace — and key the result by each instance's identifier. Entries
     * that resolve to no existing class are left out.
     *
     * @param  array<int, class-string>  $classes
     * @param  class-string  $contract
     * @return array<string, UiKit|TailorTask>
     */
    public function resolve(array $classes, string $namespace, string $contract): array
    {
        $resolved = [];

        foreach ($classes as $class) {
            $selected = $this->select($class, $namespace, $contract);

            // Exists in neither the app nor the package — leave it out entirely.
            if ($selected === null) {
                continue;
            }

            /** @var UiKit|TailorTask $instance */
            $instance = $this->container->make($selected);

            $resolved[$instance->key()] = $instance;
        }

        return $resolved;
    }

    /**
     * Resolve a config entry to the class that should run: the app override of
     * the same short name when present, otherwise the listed package class.
     * Only classes that exist and implement $contract qualify; returns null
     * when neither does so the caller can skip the entry.
     *
     * @param  class-string  $class
     * @param  class-string  $contract
     */
    private function select(string $class, string $namespace, string $contract): ?string
    {
        foreach ([$namespace.'\\'.class_basename($class), $class] as $candidate) {
            if (class_exists($candidate) && is_a($candidate, $contract, true)) {
                return $candidate;
            }
        }

        return null;
    }
}
