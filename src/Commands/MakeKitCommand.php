<?php

namespace Onelegstudios\Tailor\Commands;

class MakeKitCommand extends MakeTailorClassCommand
{
    protected $name = 'make:tailor-kit';

    protected $description = 'Create a new Tailor UI kit class';

    protected $type = 'Kit';

    protected function getStub(): string
    {
        return __DIR__.'/../../stubs/kit.stub';
    }

    /**
     * @param  string  $rootNamespace
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Tailor\\Kits';
    }
}
