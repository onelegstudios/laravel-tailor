<?php

namespace Onelegstudios\Tailor\Tests\Support;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Onelegstudios\Tailor\Tests\TestCase;

class FactoryNameResolverTest extends TestCase
{
    public function test_it_resolves_package_factory_names_from_model_basenames(): void
    {
        $this->assertSame(
            'Onelegstudios\\Tailor\\Database\\Factories\\ExampleModelFactory',
            Factory::resolveFactoryName(ExampleModel::class),
        );
    }
}

class ExampleModel extends Model {}
