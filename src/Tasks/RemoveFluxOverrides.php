<?php

namespace Onelegstudios\Tailor\Tasks;

use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Artisan;
use Onelegstudios\Tailor\Actions\RemoveFluxViews;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Delete the Flux components the starter kit publishes into views/flux, handing
 * each one back to Flux's own version — the kit's navlist/group restyles the
 * group heading and drops Flux's RTL handling, and removing it renders the
 * component as Flux ships it. The views come from
 * settings.tasks.remove-flux-overrides.views, so a kit that publishes overrides
 * of its own is tailored by editing config rather than this class.
 */
class RemoveFluxOverrides implements TailorTask
{
    public function __construct(
        private readonly RemoveFluxViews $removeFluxViews,
    ) {}

    public function key(): string
    {
        return 'remove-flux-overrides';
    }

    public function label(): string
    {
        return 'Remove published Flux overrides';
    }

    public function apply(?OutputStyle $output = null): array
    {
        /** @var array<int, string> $views */
        $views = config("tailor.settings.tasks.{$this->key()}.views") ?? [];

        $removed = $this->removeFluxViews->execute(resource_path('views/flux'), $views);

        if ($removed !== []) {
            $this->clearCompiledViews();
        }

        return [];
    }

    /**
     * Discard the compiled views, or the removal doesn't take effect until every
     * view that renders one of these components is edited.
     *
     * Blade only recompiles a view when its own file is newer than its compiled
     * copy, and this task is the one tailoring step that rewrites nothing: the
     * <flux:navlist.group> tags stay exactly as they are, so no caller's mtime
     * moves and every compiled parent survives the run. With Blaze installed the
     * stale copy is worse than merely outdated, as it has the override folded
     * into it by path and renders nothing at all once that path is gone — the
     * component silently vanishes from the page rather than falling back to
     * Flux's.
     */
    private function clearCompiledViews(): void
    {
        // Kept quiet: the command announces this task itself, and TailorCommand
        // takes the prompt output back once every task has run, so the re-pointing
        // Artisan::call() does here needs no undoing.
        Artisan::call('view:clear', [], new NullOutput);
    }
}
