# Tasks

Tasks are the optional tweaks you tick in the prompt. Any number can run in a single pass, and they run after the [kit](kits.md).

The prompt lists them alphabetically, but they always run in the order below whichever way you tick the boxes — `move-components` and `convert-partials` both leave components at the root of `components/`, and `group-components` is what sorts them, so it has to come after both.

| Key               | Label                          | What it does                                                                                                                                                                                                                                                     |
| ----------------- | ------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `move-auth`       | Move the auth folder           | Moves the Fortify auth screens out of the `pages/auth` folder into `views/auth` and repoints `FortifyServiceProvider::configureViews()` at the new view names. The components kit's `livewire/auth` folder stays where it is.                                                                      |
| `move-components` | Move non-routed pages components | Moves the Livewire page components under `resources/views/pages/` that aren't directly routed (plus the anonymous `settings/layout`) into `resources/views/components/`, preserving subpaths, and rewrites every `pages::` reference in your views and tests to the bare name. The `auth/` folder is left to the `move-auth` task. |
| `convert-partials` | Convert partials into components | Moves everything under `resources/views/partials/` into `resources/views/components/`, preserving subpaths, and rewrites every `@include('partials.head')` in your views and tests to the `<x-head />` tag it now resolves as. A partial that reads a variable from the view including it gains an `@props` declaration and every caller passes the variable in. See [Converting partials](#convert-partials--convert-partials-into-components) for those variables and what's left alone. |
| `group-components` | Group components into subfolders | Sorts the flat components at the root of `resources/views/components/` into a subfolder per concern and rewrites every `<x-name>` reference in your views and tests to the dotted name it now resolves as — `app-logo.blade.php` becomes `branding/app-logo.blade.php`, and `<x-app-logo>` becomes `<x-branding.app-logo>`. Runs after `move-components`, which is what fills that folder. See [Grouping components](#group-components--group-components-into-subfolders) to change the folders. |
| `remove-flux-overrides` | Remove published Flux overrides | Deletes the Flux components the starter kit publishes into `resources/views/flux/`, so each one renders as Flux ships it rather than as the kit restyled it. Out of the box that's `navlist/group`, which gives the sidebar group heading its Flux styling and RTL handling back. The icons in `flux/icon/` are left alone. See [Removing Flux overrides](#remove-flux-overrides--remove-published-flux-overrides) to change the list. |

## Most tasks are configurable

Every task below that has anything to decide — which folders components are sorted into, which props a partial declares, which Flux overrides go — reads that decision from `config/tailor.php` rather than hard-coding it. Publish your own copy to change any of it:

```bash
php artisan vendor:publish --tag="tailor-config"
```

Each section says which key its task reads, and the folder and prop names in there are yours: rename a group and the components follow it. Without publishing, tasks run on the defaults, which are aimed at a stock starter kit and cover all three of its variants.

The sections below cover each task in that run order.

## `move-auth` — Move the auth folder

The pages starter kit puts its Fortify auth screens in `resources/views/pages/auth/`, which means they're addressed through the `pages` view namespace as `pages::auth.login` and friends. They're not really pages in the routed sense — Fortify resolves them by name — so the namespace buys them nothing. This task moves the folder to `resources/views/auth/` and repoints `FortifyServiceProvider::configureViews()` at the bare `auth.*` names.

Only the pages variant moves. The components starter kit keeps its auth screens in `resources/views/livewire/auth/`, which is already a plain folder, and is left alone.

A few things worth knowing:

- The provider rewrite swaps the `pages::auth.` prefix, which is unique to the auth view names, so nothing else in the file is touched. A missing provider is skipped rather than treated as an error.
- This task owns the `auth/` folder outright: [`move-components`](#move-components--move-non-routed-pages-components) explicitly skips it, so the two never fight over the same files whichever way you tick them.
- Re-running is a no-op — an already-moved target is left alone rather than clobbered.

## `move-components` — Move non-routed pages components

Not everything under `resources/views/pages/` is a page. The starter kit keeps things like `settings/delete-user-form` there, which is a Livewire component that a real page renders — it has no route of its own, and the `pages::` namespace on it is noise. This task moves those into `resources/views/components/`, preserving their subpath, and rewrites every `pages::` reference in your views and tests to the bare name they now resolve as.

**What counts as routed is read from your routes, not assumed.** Any quoted `pages::x.y` literal in a `routes/*.php` file marks that component as routed, and routed components stay where they are. That's agnostic to how you route — `Route::livewire`, a `Route::get`, a redirect — and it fails safe: a literal Tailor doesn't recognize leaves the component in place rather than moving it and breaking the route.

A few things worth knowing:

- The `auth/` folder is skipped entirely — it belongs to [`move-auth`](#move-auth--move-the-auth-folder).
- Anonymous Blade views under `pages/` move too, which is how `settings/layout` ends up in `components/`.
- One rewrite covers every reference form — `<livewire:pages::x.y>`, `<x-pages::x.y>`, `Livewire::test('pages::x.y')` — because each one embeds the same literal string. The pattern is anchored to the exact names moved in that run, so routed pages and the auth views are provably untouched.
- Emptied subfolders under `pages/` are pruned, but `pages/` itself stays: the namespace is still registered, still holds your routed pages, and is where the next one lands.
- A target that already exists is never clobbered. The component stays put and its qualified name is reported at the end of the run.
- Re-running is a no-op — a second run finds nothing to move and rewrites nothing.

## `convert-partials` — Convert partials into components

An `@include` shares the variables of the view that includes it; a component does not. So `convert-partials` reads the variables each partial needs from `settings.tasks.convert-partials.props` in the published config — a map of partial name to the props it declares:

```php
'settings' => [
    'tasks' => [
        'convert-partials' => [
            'props' => [
                'head' => ['title'],
            ],
        ],
    ],
],
```

That's what turns `partials/head.blade.php`, which renders `$title` it inherited from the layout, into a component that declares it:

```blade
@props(['title' => null])

<title>{{ filled($title ?? null) ? ... }}</title>
```

and every caller into one that passes it:

```blade
<x-head :title="$title ?? null" />
```

A few things worth knowing:

- A partial missing from `props` converts to a bare tag — right for one that reads nothing (`settings-heading`), silently wrong for one that reads something, so list your own partials here.
- Only a plain, dataless `@include` is converted. A partial referenced any other way — `@include('partials.head', ['title' => 'Dashboard'])`, `@includeWhen`, a `view('partials.head')` call — is left **entirely** alone, file and references both, and reported at the end of the run: those forms pass or withhold scope in ways a tag can't be assumed to reproduce, so they're never guessed at. Convert them by hand, or make the include dataless and re-run.
- Partials land at the root of `components/`, so opting into [`group-components`](#group-components--group-components-into-subfolders) as well sorts them like any other component: `head` into `layout/` and `settings-heading` into `settings/` by default, rendering as `<x-layout.head />` and `<x-settings.settings-heading />`. A partial of your own needs a folder there too, or it stays at the root and is reported as ungrouped.
- Subpaths are preserved: `partials/nested/meta.blade.php` becomes `components/nested/meta.blade.php` and renders as `<x-nested.meta>`.
- The `partials/` folder is removed once it's empty, and kept if an unconvertible partial is still in it.
- Re-running is a no-op, and an existing target is never clobbered.

## `group-components` — Group components into subfolders

`group-components` reads its folders from `settings.tasks.group-components.groups` in the published config — a map of folder name to the components it holds:

```php
'settings' => [
    'tasks' => [
        'group-components' => [
            'groups' => [
                'branding' => ['app-logo', 'app-logo-icon'],
                'auth' => ['auth-header', 'auth-session-status', 'passkey-registration', 'passkey-verify'],
                'layout' => ['head'],
                'navigation' => ['desktop-user-menu'],
                'settings' => ['settings-heading'],
                'teams' => ['create-team-modal', 'team-invitation-alert', 'team-switcher'],
                'ui' => ['placeholder-pattern'],
            ],
        ],
    ],
],
```

The folder name **is** the dotted prefix the component picks up, so renaming `branding` to `brand` here is all it takes to have the components render as `<x-brand.app-logo>` — the rewrite follows.

A few things worth knowing:

- Only the root of `components/` is sorted. A component already in a subfolder — like the `settings/layout` that [`move-components`](#move-components--move-non-routed-pages-components) puts there — is already grouped and is left alone.
- A root component that isn't listed under any folder **stays where it is** and is reported at the end of the run, so your own components are never guessed at. Add them to a folder above to have them sorted.
- Listing a component your kit doesn't ship is harmless: it simply never matches. The defaults cover all three starter-kit variants, `teams` included.
- `head` and `settings-heading` only reach the root of `components/` when [`convert-partials`](#convert-partials--convert-partials-into-components) runs. They're listed for the run that opts into both tasks, and never match otherwise.
- References are matched on the `<x-` / `</x-` tag prefix, which keeps the rewrite off Alpine's `x-` attributes and off any Flux component sharing a name. A component referenced dynamically (`<x-dynamic-component :component="$name" />`, `view('components.app-logo')`) can't be matched and will need a hand-edit.
- Re-running is a no-op, and an existing target is never clobbered.

## `remove-flux-overrides` — Remove published Flux overrides

The starter kit publishes a handful of Flux's own components into `resources/views/flux/` and restyles them — the published `navlist/group` gives the sidebar group heading a smaller, lighter heading than Flux's and drops the `rtl:rotate-180` on its chevron. A published blade wins over the package's, so those edits are what you see until the file is gone. `remove-flux-overrides` reads which ones to delete from `settings.tasks.remove-flux-overrides.views` in the published config, named the way Flux addresses them:

```php
'settings' => [
    'tasks' => [
        'remove-flux-overrides' => [
            'views' => [
                'navlist/group',
            ],
        ],
    ],
],
```

A few things worth knowing:

- `navlist/group` is `resources/views/flux/navlist/group.blade.php`. Add any other override your kit publishes; listing one it doesn't is harmless, as a view that isn't on disk is skipped.
- Only what's listed is removed. The icons the [`lucide` kit](kits.md#lucide--flux-with-lucide-only) publishes into `flux/icon/` live in the same folder and are never touched.
- A folder left empty by the last view removed from it goes too, but `flux/` itself stays — it's where the next publish lands.
- Nothing references these blades by name, so there's no rewrite pass: Flux resolves the component from the package again the moment the file is gone.
- The compiled views are cleared for you at the end of the task, and they have to be. This is the one task that rewrites nothing, so no view's mtime moves and Blade won't recompile the parents on its own — and with [Blaze](https://github.com/livewire/blaze) installed a stale parent has the override *folded into it by path*, so once that path is gone the component renders as **nothing at all** rather than falling back to Flux's. A silently vanishing sidebar group is the symptom.
- This is a one-way trade of the kit's styling for Flux's — the deleted blade is the only copy of the kit's edits, so reach for git if you want it back. `php artisan flux:publish navlist.group` re-publishes Flux's own copy, which is a starting point for your own edits, not the kit's.
- Re-running is a no-op.

---

Writing a task of your own is covered in [Extending Tailor](extending.md).
