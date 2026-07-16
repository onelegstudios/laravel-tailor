<?php

namespace Onelegstudios\Tailor\Commands;

use Illuminate\Console\Command;
use Onelegstudios\Tailor\Actions\RemoveTailorPackage;
use Onelegstudios\Tailor\Kits\UiKit;
use Onelegstudios\Tailor\Registry;
use Onelegstudios\Tailor\Tasks\TailorTask;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\warning;

class TailorCommand extends Command
{
    public $signature = 'tailor {--ui-kit= : The UI kit to tailor to (as-is, hero, lucide, tall-stack); prompts when omitted}';

    public $description = 'Tailor the livewire starter kit to your needs';

    public function handle(Registry $registry, RemoveTailorPackage $remover): int
    {
        intro('Welcome to Tailor — let\'s customize your starter kit.');

        $kits = $registry->resolve(config('tailor.registry.kits', []), config('tailor.registry.overrides.kits', ''), UiKit::class);
        $tasks = $registry->resolve(config('tailor.registry.tasks', []), config('tailor.registry.overrides.tasks', ''), TailorTask::class);

        if ($kits === [] && $tasks === []) {
            warning('There is nothing to tailor — no UI kits or tasks are configured.');

            return self::SUCCESS;
        }

        $uikit = null;

        if ($kits !== []) {
            $uikit = $this->option('ui-kit');

            if ($uikit === null) {
                $uikit = select(
                    label: 'Which icon set do you want?',
                    options: array_map(fn ($kit) => $kit->label(), $kits),
                    default: 'as-is',
                    hint: 'Use the arrow keys to choose, enter to tailor.',
                );
            } elseif (! isset($kits[$uikit])) {
                $this->error("Unknown UI kit [{$uikit}]. Choose one of: ".implode(', ', array_keys($kits)).'.');

                return self::FAILURE;
            }
        }

        $selected = [];

        if ($tasks !== []) {
            $selected = multiselect(
                label: $kits === [] ? 'What would you like to tailor?' : 'What else would you like to tailor?',
                options: array_map(fn ($task) => $task->label(), $tasks),
                hint: 'Use space to select, enter to confirm.',
            );
        }

        $failed = [];

        if ($uikit !== null) {
            $failed = $kits[$uikit]->apply($this->output);
        }

        // Driven by $tasks, not $selected: tasks depend on each other (grouping
        // sorts what moving and converting leave at the root of components/), and
        // Prompts returns the keys in the order the user toggled them. Iterating
        // the registry is what makes the run order the configured one whichever
        // way the boxes were ticked.
        foreach ($tasks as $key => $task) {
            if (! in_array($key, $selected, true)) {
                continue;
            }

            // Tasks do their work silently, so announce each one — otherwise a
            // slow task looks like the command has hung.
            $this->output->newLine();
            $this->output->writeln('<info>'.$task->label().'...</info>');

            $task->apply($this->output);

            $this->output->writeln('<info>✓ '.$task->label().'</info>');
        }

        if ($failed !== []) {
            outro('Tailoring finished, but '.count($failed).' icon(s) could not be downloaded.');

            return self::FAILURE;
        }

        $this->offerToRemovePackage($remover);

        outro('All done! Your starter kit has been tailored.');

        return self::SUCCESS;
    }

    /**
     * Tailor is a one-time scaffolding tool, so once it has run there is nothing
     * left for it to do. Offer to uninstall it so it does not linger as a dev
     * dependency.
     */
    private function offerToRemovePackage(RemoveTailorPackage $remover): void
    {
        $remove = confirm(
            label: 'Tailoring is done — remove the Tailor package now?',
            default: false,
            hint: 'Tailor is a one-time tool; we recommend removing it once you\'ve tailored your starter kit.',
        );

        if ($remove) {
            $remover->execute($this->output);
        }
    }
}
