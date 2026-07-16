<?php

namespace Onelegstudios\Tailor\Tasks;

use Illuminate\Console\OutputStyle;
use Onelegstudios\Tailor\Actions\ConvertPartialViews;

/**
 * Turn the starter kit's partials into Blade components: move each file out of
 * views/partials into views/components and rewrite every include of
 * partials.head in the views and tests to the <x-head /> tag it now resolves
 * as. A partial reads the caller's variables where a component does not, so the
 * variables each one needs come from settings.tasks.convert-partials.props — a
 * kit with its own partials is tailored by editing config rather than this
 * class. A partial referenced by anything other than a plain, dataless include
 * directive is left alone and reported.
 */
class ConvertPartials implements TailorTask
{
    public function __construct(
        private readonly ConvertPartialViews $convertPartialViews,
    ) {}

    public function key(): string
    {
        return 'convert-partials';
    }

    public function label(): string
    {
        return 'Convert partials into components';
    }

    public function apply(?OutputStyle $output = null): array
    {
        /** @var array<string, array<int, string>> $props */
        $props = config("tailor.settings.tasks.{$this->key()}.props") ?? [];

        return $this->convertPartialViews->execute(
            resource_path('views'),
            base_path('tests'),
            $props,
        );
    }
}
