<?php

namespace Onelegstudios\Tailor\Commands;

use Illuminate\Console\Command;
use Onelegstudios\Tailor\Actions\PublishFluxIcons;
use Onelegstudios\Tailor\Actions\PublishLucideIcons;
use Onelegstudios\Tailor\Actions\ReplaceHeroicons;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;

class TailorCommand extends Command
{
    public $signature = 'tailor';

    public $description = 'Tailor the livewire starter kit to your needs';

    public function handle(
        ReplaceHeroicons $replaceHeroicons,
        PublishLucideIcons $publishLucideIcons,
        PublishFluxIcons $publishFluxIcons,
    ): int {
        intro('Welcome to Tailor — let\'s customize your starter kit.');

        $uikit = select(
            label: 'What UI kit do you want to use?',
            options: [
                'hero' => 'Flux with Heroicons',
                'lucide' => 'Flux with Lucide Icons',
                'tall-stack' => 'Tall Stack UI',
            ],
            default: 'hero',
            hint: 'Use the arrow keys to choose, enter to tailor.',
        );

        $options = multiselect(
            label: 'What else would you like to tailor?',
            options: [
                'move_auth' => 'Move the auth folder',
            ],
            hint: 'Use space to select, enter to confirm.',
        );

        if ($uikit === 'lucide') {
            $iconPath = resource_path('views/flux/icon');

            $starterKit = config('tailor.icons.starter-kit', []);
            $map = array_merge(...array_values($starterKit));

            $replaceHeroicons->execute(resource_path('views'), $map);

            $flux = config('tailor.icons.flux', []);
            $normal = $flux['normal'] ?? [];
            $animated = $flux['animated'] ?? [];

            // Gather every icon name up front — the starter-kit replacements plus
            // the Lucide glyphs Flux's own components need — so the whole set is
            // downloaded in a single pass.
            $icons = [
                ...array_values($map),
                ...$publishFluxIcons->replacements($normal, $animated),
            ];

            $publishLucideIcons->execute($iconPath, $icons, $this->output);

            // Starter-kit glyphs are referenced directly by their Lucide name, so
            // they must survive the Flux aliasing pass even when a Flux icon shares
            // the same replacement.
            $publishFluxIcons->applyAliases($iconPath, $normal, $animated, array_values($map));
        }

        outro('All done! Your starter kit has been tailored.');

        return self::SUCCESS;
    }
}
