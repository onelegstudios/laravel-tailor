<?php

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

it('resolves package factory names from model basenames', function (): void {
    expect(Factory::resolveFactoryName(ExampleModel::class))
        ->toBe('Onelegstudios\\Tailor\\Database\\Factories\\ExampleModelFactory');
});

class ExampleModel extends Model {}
