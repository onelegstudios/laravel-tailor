<?php

namespace Onelegstudios\Tailor\Tasks;

use Illuminate\Console\OutputStyle;
use Onelegstudios\Tailor\Actions\RemoveFluxViews;

/**
 * Delete the Flux components the starter kit publishes into views/flux, handing
 * each one back to Flux's own version — the kit's navlist/group restyles the
 * group heading and drops Flux's RTL handling, and removing it renders the
 * component as Flux ships it. The views come from
 * settings.tasks.remove-flux-overrides.views, so a kit that publishes overrides
 * of its own is tailored by editing config rather than this class.
 *
 * Removing a view leaves the compiled views stale, and discarding them is
 * TailorCommand's to do once the run is over — see its clearCompiledViews().
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

        $this->removeFluxViews->execute(resource_path('views/flux'), $views);

        return [];
    }
}
