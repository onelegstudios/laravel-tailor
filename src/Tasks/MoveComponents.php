<?php

namespace Onelegstudios\Tailor\Tasks;

use Illuminate\Console\OutputStyle;
use Onelegstudios\Tailor\Actions\MoveComponentViews;

/**
 * Move the starter kit's non-routed Livewire page components out of the pages
 * namespace folder (views/pages) into views/components, preserving their
 * subpath, and rewrite every pages:: reference in the views and tests to the
 * bare name they now resolve as. Directly routed pages and the auth folder
 * (owned by the move-auth task) are left in place.
 */
class MoveComponents implements TailorTask
{
    public function __construct(
        private readonly MoveComponentViews $moveComponentViews,
    ) {}

    public function key(): string
    {
        return 'move-components';
    }

    public function label(): string
    {
        return 'Move non-routed pages components';
    }

    public function apply(?OutputStyle $output = null): array
    {
        return $this->moveComponentViews->execute(
            resource_path('views'),
            base_path('routes'),
            base_path('tests'),
        );
    }
}
