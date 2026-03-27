<?php

namespace Onelegstudios\Tailor\Tests\Support;

use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Tailor\Support\WorkbenchRefreshRunner;
use PHPUnit\Framework\TestCase;

class WorkbenchRefreshRunnerTest extends TestCase
{
    public function test_it_skips_refresh_when_the_project_is_not_a_git_checkout(): void
    {
        $projectRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'tailor-runner-no-git-'.bin2hex(random_bytes(8));
        $runner = new class(new Filesystem) extends WorkbenchRefreshRunner
        {
            public bool $refreshed = false;

            public bool $built = false;

            protected function refreshWorkbench(string $projectRoot): void
            {
                $this->refreshed = true;
            }

            protected function buildWorkbench(string $projectRoot): void
            {
                $this->built = true;
            }
        };

        try {
            mkdir($projectRoot, 0777, true);

            self::assertSame(0, $runner->run($projectRoot));
            self::assertFalse($runner->refreshed);
            self::assertFalse($runner->built);
        } finally {
            (new Filesystem)->deleteDirectory($projectRoot);
        }
    }

    public function test_it_refreshes_and_builds_when_the_project_is_a_git_checkout(): void
    {
        $projectRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'tailor-runner-git-'.bin2hex(random_bytes(8));
        $runner = new class(new Filesystem) extends WorkbenchRefreshRunner
        {
            public bool $refreshed = false;

            public bool $built = false;

            protected function refreshWorkbench(string $projectRoot): void
            {
                $this->refreshed = true;
            }

            protected function buildWorkbench(string $projectRoot): void
            {
                $this->built = true;
            }
        };

        try {
            mkdir($projectRoot.DIRECTORY_SEPARATOR.'.git', 0777, true);

            self::assertSame(0, $runner->run($projectRoot));
            self::assertTrue($runner->refreshed);
            self::assertTrue($runner->built);
        } finally {
            (new Filesystem)->deleteDirectory($projectRoot);
        }
    }
}
