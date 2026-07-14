<?php

namespace Onelegstudios\Tailor\Tasks;

use Illuminate\Console\OutputStyle;
use Onelegstudios\Tailor\Actions\MoveAuthViews;

/**
 * Move the starter kit's Fortify auth screens out of their Livewire namespace
 * folder (pages/auth or livewire/auth) into the plain views/auth folder, and
 * repoint FortifyServiceProvider::configureViews() at the new view names.
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
