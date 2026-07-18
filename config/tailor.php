<?php

use Onelegstudios\Tailor\Kits\AsIsKit;
use Onelegstudios\Tailor\Kits\HeroKit;
use Onelegstudios\Tailor\Kits\LucideKit;
use Onelegstudios\Tailor\Tasks\ConvertPartials;
use Onelegstudios\Tailor\Tasks\GroupComponents;
use Onelegstudios\Tailor\Tasks\MoveAuth;
use Onelegstudios\Tailor\Tasks\MoveComponents;
use Onelegstudios\Tailor\Tasks\RemoveFluxOverrides;

// config for Onelegstudios/Tailor
return [
    // Wiring for the package itself: which kits and tasks exist, in what order
    // they are offered, and where app-level overrides are looked up. This block
    // is about registration, not per-kit data — that lives under `settings`.
    'registry' => [
        // Mutually exclusive UI kits, in the order they are offered. Add a kit by
        // implementing Onelegstudios\Tailor\Kits\UiKit and listing it here.
        'kits' => [
            AsIsKit::class,
            HeroKit::class,
            LucideKit::class,
        ],

        // Independent tailoring tasks offered alongside the UI kit, in the order
        // they run — the prompt offers them alphabetically, but a selection is
        // always applied in this order, so a task that feeds another (grouping
        // sorts what moving and converting leave at the root of components/) goes
        // after it here. Add a task by implementing
        // Onelegstudios\Tailor\Tasks\TailorTask and listing it here.
        'tasks' => [
            MoveAuth::class,
            MoveComponents::class,
            ConvertPartials::class,
            GroupComponents::class,
            RemoveFluxOverrides::class,
        ],

        // App namespaces checked for overrides. A class here with the same short
        // name as a registered kit/task (e.g. app/Tailor/Kits/LucideKit.php) runs
        // instead of the package's, with no change to the lists above.
        'overrides' => [
            'kits' => 'App\\Tailor\\Kits',
            'tasks' => 'App\\Tailor\\Tasks',
        ],
    ],

    // Per-kit and per-task settings, keyed by each kit/task's key(). A kit reads
    // its own slice with config("tailor.settings.kits.{$this->key()}...."), so the
    // config location is derivable from the kit rather than memorized.
    'settings' => [
        'kits' => [
            // HeroKit (key: 'hero') — reverses the starter kit's handful of Lucide
            // overrides (see settings.kits.lucide.icons.starter-kit.lucide),
            // swapping each back to the Heroicon that replaces it (lucide-name =>
            // heroicon-name) so the kit renders Heroicons throughout. bin/scan-icons
            // keeps this key set in sync with those overrides but never fills in the
            // Heroicon values — new keys arrive empty, so edit them to taste.
            'hero' => [
                'icons' => [
                    'book-open-text' => 'book-open',
                    'chevrons-up-down' => 'chevron-up-down',
                    'folder-git-2' => 'folder',
                    'layout-grid' => 'squares-2x2',
                ],
            ],

            // LucideKit (key: 'lucide') — swaps the starter kit's Heroicons for
            // their Lucide equivalents and re-aliases the icons Flux references
            // internally. bin/scan-icons regenerates the `icons` map wholesale.
            //
            // The `flux` group is split by the package that ships the component
            // doing the referencing: `free` for livewire/flux, `pro` for icons only
            // livewire/flux-pro's components use (an icon both reference counts as
            // free). LucideKit downloads `pro` only when flux-pro is installed, so
            // a free-only app doesn't fetch glyphs it has no component to render.
            // `animated` is referenced by both and always downloaded.
            'lucide' => [
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
                        'free' => [
                            'chevron-left' => 'chevron-left',
                            'chevron-up' => 'chevron-up',
                            'clipboard-document' => 'clipboard',
                            'clipboard-document-check' => 'clipboard-check',
                            'exclamation-triangle' => 'triangle-alert',
                            'minus' => 'minus',
                            'slash' => 'slash',
                        ],
                        'pro' => [
                            'calendar' => 'calendar',
                            'chevron-up-down' => 'chevrons-up-down',
                            'clock' => 'clock',
                            'cloud-arrow-up' => 'cloud-upload',
                            'document' => 'file',
                            'eye-dropper' => 'pipette',
                        ],
                        'animated' => [
                            'loading' => 'loader-circle',
                        ],
                    ],
                ],
            ],
        ],

        // Per-task settings, keyed by each task's key(). A task reads its own slice
        // with config("tailor.settings.tasks.{$this->key()}...."), so the config
        // location is derivable from the task rather than memorized.
        'tasks' => [
            // ConvertPartials (key: 'convert-partials') — the variables each partial
            // reads from the view that includes it, as partial name => its props.
            // An @include shares the caller's scope and a component does not, so a
            // partial listed here gains an @props declaration and every caller
            // passes the variable in explicitly: head reading $title makes
            // @include('partials.head') become <x-head :title="$title ?? null" />.
            //
            // A partial missing from this list converts to a tag with no attributes,
            // which is right for one that reads nothing (settings-heading) and
            // silently wrong for one that reads something — so a partial your kit
            // adds is yours to list here. Listing a partial your kit doesn't ship is
            // harmless; it simply never matches.
            'convert-partials' => [
                'props' => [
                    'head' => [
                        'title',
                    ],
                ],
            ],

            // GroupComponents (key: 'group-components') — the subfolder each flat
            // component at the root of views/components is sorted into, as folder =>
            // the component names it holds. The folder name is the dotted prefix the
            // component picks up, so `branding` makes app-logo render as
            // <x-branding.app-logo>; rename a folder here and the rewrite follows.
            //
            // Only the root of components/ is sorted — a component already in a
            // subfolder is already grouped. A root component missing from every list
            // stays where it is and is reported at the end of the run, so listing a
            // component your kit doesn't ship is harmless (it simply never matches),
            // but a component you add is yours to place here.
            //
            // head and settings-heading only reach the root when convert-partials
            // runs, so they are listed for the run that opts into both tasks and
            // simply never match otherwise.
            'group-components' => [
                'groups' => [
                    'branding' => [
                        'app-logo',
                        'app-logo-icon',
                    ],
                    'auth' => [
                        'auth-header',
                        'auth-session-status',
                        'passkey-registration',
                        'passkey-verify',
                    ],
                    'layout' => [
                        'head',
                    ],
                    'navigation' => [
                        'desktop-user-menu',
                    ],
                    'settings' => [
                        'settings-heading',
                    ],
                    'teams' => [
                        'create-team-modal',
                        'team-invitation-alert',
                        'team-switcher',
                    ],
                    'ui' => [
                        'placeholder-pattern',
                    ],
                ],
            ],

            // RemoveFluxOverrides (key: 'remove-flux-overrides') — the Flux
            // components the starter kit publishes into resources/views/flux, named
            // the way Flux addresses them: navlist/group is
            // flux/navlist/group.blade.php. Each one listed here is deleted, so the
            // component renders as Flux ships it rather than as the kit restyled it.
            //
            // Only what is listed is removed — the icons the lucide kit publishes
            // into flux/icon live in the same folder and are not this task's to
            // touch. A view missing from disk is skipped, so listing an override
            // your kit doesn't publish is harmless; one it does publish is yours to
            // add here.
            'remove-flux-overrides' => [
                'views' => [
                    'navlist/group',
                ],
            ],
        ],
    ],
];
