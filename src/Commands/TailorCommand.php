<?php

namespace Onelegstudios\Tailor\Commands;

use Illuminate\Console\Command;
use Onelegstudios\Tailor\Kits\UiKit;
use Onelegstudios\Tailor\Tasks\TailorTask;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;

class TailorCommand extends Command
{
    public $signature = 'tailor {--ui-kit= : The UI kit to tailor to (hero, lucide, tall-stack); prompts when omitted}';

    public $description = 'Tailor the livewire starter kit to your needs';

    public function handle(): int
    {
        intro('Welcome to Tailor — let\'s customize your starter kit.');

        $kits = $this->resolve(config('tailor.kits', []));
        $tasks = $this->resolve(config('tailor.tasks', []));

        $uikit = $this->option('ui-kit');

        if ($uikit === null) {
            $uikit = select(
                label: 'What UI kit do you want to use?',
                options: array_map(fn ($kit) => $kit->label(), $kits),
                default: 'hero',
                hint: 'Use the arrow keys to choose, enter to tailor.',
            );
        } elseif (! isset($kits[$uikit])) {
            $this->error("Unknown UI kit [{$uikit}]. Choose one of: ".implode(', ', array_keys($kits)).'.');

            return self::FAILURE;
        }

        $selected = multiselect(
            label: 'What else would you like to tailor?',
            options: array_map(fn ($task) => $task->label(), $tasks),
            hint: 'Use space to select, enter to confirm.',
        );

        $failed = $kits[$uikit]->apply($this->output);

        foreach ($selected as $key) {
            $tasks[$key]->apply($this->output);
        }

        if ($failed !== []) {
            outro('Tailoring finished, but '.count($failed).' icon(s) could not be downloaded.');

            return self::FAILURE;
        }

        outro('All done! Your starter kit has been tailored.');

        return self::SUCCESS;
    }

    /**
     * Instantiate each registered kit/task and key it by its identifier so the
     * prompts and dispatch can look it up by the value the user chose.
     *
     * @param  array<int, class-string>  $classes
     * @return array<string, UiKit|TailorTask>
     */
    private function resolve(array $classes): array
    {
        $resolved = [];

        foreach ($classes as $class) {
            /** @var UiKit|TailorTask $instance */
            $instance = app($class);

            $resolved[$instance->key()] = $instance;
        }

        return $resolved;
    }
}
