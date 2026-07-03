<?php

namespace Onelegstudios\Tailor\Commands;

class MakeTaskCommand extends MakeTailorClassCommand
{
    protected $name = 'make:tailor-task';

    protected $description = 'Create a new Tailor task class';

    protected $type = 'Task';

    protected function getStub(): string
    {
        return __DIR__.'/../../stubs/task.stub';
    }

    /**
     * @param  string  $rootNamespace
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Tailor\\Tasks';
    }
}
