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
    public $signature = 'tailor {--ui-kit= : The UI kit to tailor to (hero, lucide, tall-stack); prompts when omitted}';

    public $description = 'Tailor the livewire starter kit to your needs';

    public function handle(Registry $registry, RemoveTailorPackage $remover): int
    {
        intro('Welcome to Tailor — let\'s customize your starter kit.');

        $kits = $registry->resolve(config('tailor.kits', []), config('tailor.overrides.kits', ''), UiKit::class);
        $tasks = $registry->resolve(config('tailor.tasks', []), config('tailor.overrides.tasks', ''), TailorTask::class);

        if ($kits === [] && $tasks === []) {
            warning('There is nothing to tailor — no UI kits or tasks are configured.');

            return self::SUCCESS;
        }

        $uikit = null;

        if ($kits !== []) {
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

        foreach ($selected as $key) {
            $tasks[$key]->apply($this->output);
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
