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
    public $signature = 'tailor {--ui-kit= : The UI kit to tailor to (hero, lucide, tall-stack); prompts when omitted}';

    public $description = 'Tailor the livewire starter kit to your needs';

    public function handle(
        ReplaceHeroicons $replaceHeroicons,
        PublishLucideIcons $publishLucideIcons,
        PublishFluxIcons $publishFluxIcons,
    ): int {
        intro('Welcome to Tailor — let\'s customize your starter kit.');

        $uikits = [
            'hero' => 'Flux with Heroicons',
            'lucide' => 'Flux with Lucide Icons',
            'tall-stack' => 'Tall Stack UI',
        ];

        $uikit = $this->option('ui-kit');

        if ($uikit === null) {
            $uikit = select(
                label: 'What UI kit do you want to use?',
                options: $uikits,
                default: 'hero',
                hint: 'Use the arrow keys to choose, enter to tailor.',
            );
        } elseif (! array_key_exists($uikit, $uikits)) {
            $this->error("Unknown UI kit [{$uikit}]. Choose one of: ".implode(', ', array_keys($uikits)).'.');

            return self::FAILURE;
        }

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

            $failed = $publishLucideIcons->execute($iconPath, $icons, $this->output);

            // Starter-kit glyphs are referenced directly by their Lucide name, so
            // they must survive the Flux aliasing pass even when a Flux icon shares
            // the same replacement.
            $publishFluxIcons->applyAliases($iconPath, $normal, $animated, array_values($map));

            if ($failed !== []) {
                outro('Tailoring finished, but '.count($failed).' icon(s) could not be downloaded.');

                return self::FAILURE;
            }
        }

        outro('All done! Your starter kit has been tailored.');

        return self::SUCCESS;
    }
}
