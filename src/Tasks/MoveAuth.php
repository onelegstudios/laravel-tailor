<?php

namespace Onelegstudios\Tailor\Tasks;

use Illuminate\Console\OutputStyle;
use Onelegstudios\Tailor\Actions\MoveAuthViews;

/**
 * Move the pages starter kit's Fortify auth screens out of the pages/auth
 * namespace folder into the plain views/auth folder, and repoint
 * FortifyServiceProvider::configureViews() at the new view names. The
 * components kit keeps its auth screens in livewire/auth.
 */
class MoveAuth implements TailorTask
{
    public function __construct(
        private readonly MoveAuthViews $moveAuthViews,
    ) {}

    public function key(): string
    {
        return 'move-auth';
    }

    public function label(): string
    {
        return 'Move the auth folder';
    }

    public function apply(?OutputStyle $output = null): array
    {
        $this->moveAuthViews->execute(
            resource_path('views'),
            app_path('Providers/FortifyServiceProvider.php'),
        );

        return [];
    }
}
