<?php

namespace Onelegstudios\Tailor\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

/**
 * Shared behaviour for the tailor generator commands: scaffolds a class into
 * the host app's app/Tailor folder and fills in a sensible key().
 */
abstract class MakeTailorClassCommand extends GeneratorCommand
{
    public function handle()
    {
        $result = parent::handle();

        if ($result !== false) {
            $this->components->info("Register your {$this->type} in config/tailor.php to enable it.");
        }

        return $result;
    }

    /**
     * Fill the {{ key }} placeholder with a kebab-case identifier derived from
     * the class name (dropping the {$this->type} suffix), e.g. FooKit -> foo.
     *
     * @param  string  $name
     */
    protected function buildClass($name): string
    {
        $class = str_replace($this->getNamespace($name).'\\', '', $name);

        return str_replace(
            '{{ key }}',
            Str::kebab(Str::beforeLast($class, $this->type)),
            parent::buildClass($name),
        );
    }
}
