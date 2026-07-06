<?php

use Onelegstudios\Tailor\Kits\AsIsKit;
use Onelegstudios\Tailor\Kits\HeroKit;
use Onelegstudios\Tailor\Kits\LucideKit;
use Onelegstudios\Tailor\Kits\TallStackKit;
use Onelegstudios\Tailor\Tasks\MoveAuth;

// config for Onelegstudios/Tailor
return [
    // Mutually exclusive UI kits, in the order they are offered. Add a kit by
    // implementing Onelegstudios\Tailor\Kits\UiKit and listing it here.
    'kits' => [
        AsIsKit::class,
        HeroKit::class,
        LucideKit::class,
        TallStackKit::class,
    ],

    // Independent tailoring tasks offered alongside the UI kit. Add a task by
    // implementing Onelegstudios\Tailor\Tasks\TailorTask and listing it here.
    'tasks' => [
        MoveAuth::class,
    ],

    // App namespaces checked for overrides. A class here with the same short
    // name as a registered kit/task (e.g. app/Tailor/Kits/LucideKit.php) runs
    // instead of the package's, with no change to the lists above.
    'overrides' => [
        'kits' => 'App\\Tailor\\Kits',
        'tasks' => 'App\\Tailor\\Tasks',
    ],

    // The Heroicons kit reverses the starter kit's handful of Lucide overrides
    // (see icons.starter-kit.lucide), swapping each back to the Heroicon that
    // replaces it (lucide-name => heroicon-name) so the kit renders Heroicons
    // throughout. Kept outside the `icons` key below, which bin/scan-icons
    // regenerates wholesale. Edit these targets to taste.
    'heroicons-kit' => [
        'book-open-text' => 'book-open',
        'chevrons-up-down' => 'chevron-up-down',
        'folder-git-2' => 'folder',
        'layout-grid' => 'squares-2x2',
    ],

    'icons' => [
        'starter-kit' => [
            'heroicons' => [
                'arrow-path' => 'refresh-cw',
                'arrow-right-start-on-rectangle' => 'log-out',
                'bars-2' => 'menu',
                'check' => 'check',
                'chevron-down' => 'chevron-down',
                'chevron-right' => 'chevron-right',
                'cog' => 'settings',
                'computer-desktop' => 'monitor',
                'document-duplicate' => 'copy',
                'envelope' => 'mail',
                'eye' => 'eye',
                'eye-slash' => 'eye-off',
                'finger-print' => 'fingerprint-pattern',
                'home' => 'house',
                'information-circle' => 'info',
                'key' => 'key-round',
                'lock-closed' => 'lock',
                'magnifying-glass' => 'search',
                'moon' => 'moon',
                'plus' => 'plus',
                'qr-code' => 'qr-code',
                'sun' => 'sun',
                'trash' => 'trash-2',
                'user-plus' => 'user-plus',
                'users' => 'users',
                'x-circle' => 'circle-x',
                'x-mark' => 'x',
            ],
            'lucide' => [
                'book-open-text' => 'book-open-text',
                'chevrons-up-down' => 'chevrons-up-down',
                'folder-git-2' => 'folder-git-2',
                'layout-grid' => 'layout-dashboard',
            ],
        ],
        'flux' => [
            'normal' => [
                'calendar' => 'calendar',
                'chevron-left' => 'chevron-left',
                'chevron-up' => 'chevron-up',
                'chevron-up-down' => 'chevrons-up-down',
                'clipboard-document' => 'clipboard',
                'clipboard-document-check' => 'clipboard-check',
                'clock' => 'clock',
                'cloud-arrow-up' => 'cloud-upload',
                'document' => 'file',
                'exclamation-triangle' => 'triangle-alert',
                'eye-dropper' => 'pipette',
                'minus' => 'minus',
                'slash' => 'slash',
            ],
            'animated' => [
                'loading' => 'loader-circle',
            ],
        ],
    ],
];
