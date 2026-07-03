<?php

namespace Onelegstudios\Tailor\Tests\Stubs;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Stands in for Flux's flux:icon command in tests so the suite never makes the
 * real HTTP call to Lucide. It records the icon names it was asked for and,
 * when a target directory is set, writes a minimal icon blade for each name —
 * mirroring what flux:icon produces — so aliasing logic can be exercised.
 */
class RecordingFluxIconCommand extends Command
{
    protected $signature = 'flux:icon {icons?*}';

    protected $description = 'Recording stub for flux:icon.';

    /** @var array<int, string> */
    public static array $received = [];

    public static int $calls = 0;

    /**
     * When set, each requested icon is written here as a blade file, the way
     * the real flux:icon writes into resources/views/flux/icon.
     */
    public static ?string $targetDir = null;

    /**
     * Icon names that should be left un-written to simulate a failed download.
     *
     * @var array<int, string>
     */
    public static array $fail = [];

    public static function reset(): void
    {
        self::$received = [];
        self::$calls = 0;
        self::$targetDir = null;
        self::$fail = [];
    }

    public function handle(): int
    {
        self::$calls++;
        /** @var array<int, string> $icons */
        $icons = $this->argument('icons');
        self::$received = array_merge(self::$received, $icons);

        if (self::$targetDir !== null) {
            $files = new Filesystem;
            $files->ensureDirectoryExists(self::$targetDir);

            foreach ($icons as $icon) {
                if (in_array($icon, self::$fail, true)) {
                    continue;
                }

                $files->put(self::$targetDir.'/'.$icon.'.blade.php', $this->stubBlade($icon));
            }
        }

        return self::SUCCESS;
    }

    private function stubBlade(string $icon): string
    {
        return <<<BLADE
            {{-- Credit: Lucide (https://lucide.dev) --}}
            @props(['variant' => 'outline'])
            @php
                \$classes = Flux::classes('shrink-0')->add('size-6');
            @endphp
            <svg {{ \$attributes->class(\$classes) }} data-flux-icon data-icon="{$icon}"></svg>
            BLADE;
    }
}
