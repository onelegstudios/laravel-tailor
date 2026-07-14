<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Isolate app_path() so the generated classes never land in the Testbench
    // skeleton that other parallel workers share.
    $this->appBase = $this->isolateApplicationPaths();
});

afterEach(function () {
    File::deleteDirectory($this->appBase);
});

it('scaffolds a UI kit class into the app', function () {
    $this->artisan('make:tailor-kit', ['name' => 'FooKit'])->assertSuccessful();

    $path = app_path('Tailor/Kits/FooKit.php');

    expect(File::exists($path))->toBeTrue()
        ->and(File::get($path))
        ->toContain('Tailor\\Kits;')
        ->toContain('use Onelegstudios\\Tailor\\Kits\\UiKit;')
        ->toContain('class FooKit implements UiKit')
        ->toContain("return 'foo';");
});

it('scaffolds a task class into the app', function () {
    $this->artisan('make:tailor-task', ['name' => 'MoveAuth'])->assertSuccessful();

    $path = app_path('Tailor/Tasks/MoveAuth.php');

    expect(File::exists($path))->toBeTrue()
        ->and(File::get($path))
        ->toContain('Tailor\\Tasks;')
        ->toContain('use Onelegstudios\\Tailor\\Tasks\\TailorTask;')
        ->toContain('class MoveAuth implements TailorTask')
        ->toContain("return 'move-auth';");
});

it('derives a kebab-case key from a multi-word kit name', function () {
    $this->artisan('make:tailor-kit', ['name' => 'TallStackKit'])->assertSuccessful();

    expect(File::get(app_path('Tailor/Kits/TallStackKit.php')))
        ->toContain("return 'tall-stack';");
});
