<?php

namespace Onelegstudios\Tailor\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Laravel\Prompts\Prompt;
use Onelegstudios\Tailor\Actions\RemoveTailorPackage;
use Onelegstudios\Tailor\Kits\UiKit;
use Onelegstudios\Tailor\Registry;
use Onelegstudios\Tailor\Tasks\TailorTask;
use Symfony\Component\Console\Output\NullOutput;

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
            $options = array_map(fn ($task) => $task->label(), $tasks);

            // Offered alphabetically — the list is for scanning, and the order
            // tasks run in is the registry's, applied below.
            asort($options);

            $selected = multiselect(
                label: $kits === [] ? 'What would you like to tailor?' : 'What else would you like to tailor?',
                options: $options,
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

        $this->clearCompiledViews();

        $this->takeBackPromptOutput();

        if ($failed !== []) {
            outro('Tailoring finished, but '.count($failed).' icon(s) could not be downloaded.');

            return self::FAILURE;
        }

        $this->offerToRemovePackage($remover);

        outro('All done! Your starter kit has been tailored.');

        return self::SUCCESS;
    }

    /**
     * Discard the compiled views once the run is over, or what a kit or task just
     * did to views/ doesn't take effect until every view that renders one of them
     * is edited.
     *
     * Blade only recompiles a view when its own file is newer than its compiled
     * copy, so a step is only safe on its own if it moves the mtime of every file
     * that resolves the view it touched. Most steps do, incidentally: they rewrite
     * their callers, so the callers recompile. The ones that don't are the steps
     * whose callers they never rewrite — remove-flux-overrides deletes a view and
     * leaves the <flux:navlist.group> tags exactly as they are, and the lucide kit
     * aliases icons that only Flux's own blades in vendor/ reference. With Blaze
     * installed a stale copy is worse than merely outdated, as it has the resolved
     * component folded into it by path: the removed override renders as nothing at
     * all rather than falling back to Flux's, and the published alias never renders
     * at all.
     *
     * Doing it here rather than asking each step to work out whether it needs to is
     * what keeps that a non-issue. The registry takes kits and tasks from config, so
     * a step this package never sees is free to touch views/ however it likes, and
     * clearing once at the end costs a one-time scaffolding command nothing.
     *
     * Kept quiet: the run has already said what it did, and where the Artisan::call()
     * leaves Prompts pointed is takeBackPromptOutput()'s to put right, immediately
     * below. Going through the facade rather than callSilent() is what keeps this off
     * the command's Symfony application, which a command constructed directly — as
     * the tests do — hasn't got.
     */
    private function clearCompiledViews(): void
    {
        Artisan::call('view:clear', [], new NullOutput);
    }

    /**
     * Point Laravel Prompts back at this command's output, now the kits and tasks
     * have had their turn and everything left to do is a prompt.
     *
     * Running a command through Artisan::call() re-points Prompts at the output
     * that call was given — a NullOutput, when the caller wanted the command kept
     * quiet — and never puts it back. Left alone, the confirmation and the outro
     * below would render into nothing while still reading keystrokes, so the
     * command would look hung.
     *
     * Taking the output back here rather than asking every kit and task to clean up
     * after itself is what keeps that a non-issue: this is the one place that
     * prompts, so it is the one place that has to be sure where prompts render, and
     * a task is free to shell out to whatever it likes.
     */
    private function takeBackPromptOutput(): void
    {
        Prompt::setOutput($this->output);
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
