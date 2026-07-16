<?php

namespace Onelegstudios\Tailor\Tasks;

use Illuminate\Console\OutputStyle;
use Onelegstudios\Tailor\Actions\GroupComponentViews;

/**
 * Sort the flat components the starter kit leaves at the root of views/components
 * into a subfolder per concern — branding, auth, layout, and so on — and rewrite
 * every <x-name> reference in the views and tests to the dotted name the component
 * now resolves as. The folders and their members come from
 * settings.tasks.group-components.groups, so a kit with its own components is
 * tailored by editing config rather than this class. Anything not listed there
 * stays at the root and is reported back.
 */
class GroupComponents implements TailorTask
{
    public function __construct(
        private readonly GroupComponentViews $groupComponentViews,
    ) {}

    public function key(): string
    {
        return 'group-components';
    }

    public function label(): string
    {
        return 'Group components into subfolders';
    }

    public function apply(?OutputStyle $output = null): array
    {
        /** @var array<string, array<int, string>> $groups */
        $groups = config("tailor.settings.tasks.{$this->key()}.groups") ?? [];

        return $this->groupComponentViews->execute(
            resource_path('views'),
            base_path('tests'),
            $groups,
        );
    }
}
