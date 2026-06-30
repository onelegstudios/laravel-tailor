<?php

use Onelegstudios\Tailor\Kits\HeroKit;
use Onelegstudios\Tailor\Kits\LucideKit;
use Onelegstudios\Tailor\Kits\TallStackKit;
use Onelegstudios\Tailor\Tasks\MoveAuth;

// config for Onelegstudios/Tailor
return [
    // Mutually exclusive UI kits, in the order they are offered. Add a kit by
    // implementing Onelegstudios\Tailor\Kits\UiKit and listing it here.
    'kits' => [
        HeroKit::class,
        LucideKit::class,
        TallStackKit::class,
    ],

    // Independent tailoring tasks offered alongside the UI kit. Add a task by
    // implementing Onelegstudios\Tailor\Tasks\TailorTask and listing it here.
    'tasks' => [
        MoveAuth::class,
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
