<?php

namespace Onelegstudios\Tailor\Tasks;

use Illuminate\Console\OutputStyle;

/**
 * Move the auth folder. Not yet implemented — selecting it currently does
 * nothing.
 */
class MoveAuth implements TailorTask
{
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
        return [];
    }
}
