<?php

use Laravel\Prompts\Prompt;
use Onelegstudios\Tailor\Tests\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

uses(TestCase::class)->in(__DIR__);

/**
 * The output Laravel Prompts currently renders to. Prompt::output() is protected
 * with no public counterpart, so bind into the class to read it.
 */
function promptOutput(): OutputInterface
{
    return Closure::bind(static fn (): OutputInterface => Prompt::output(), null, Prompt::class)();
}
