# Usage

Run the interactive command and follow the prompts:

```bash
php artisan tailor
```

```
┌ Welcome to Tailor — let's customize your starter kit.
│
◇ Which icon set do you want?
│ › Flux with mixed icons
│   Flux with Heroicons only
│   Flux with Lucide only
│
◇ What else would you like to tailor?
│ ◻ Convert partials into components
│ ◻ Group components into subfolders
│ ◻ Move non-routed pages components
│ ◻ Move the auth folder
│ ◻ Remove published Flux overrides
│
└ All done! Your starter kit has been tailored.
```

You'll be asked to pick a **UI kit** (mutually exclusive) and any number of additional **tasks**.

## Run it on a fresh starter kit

**Tailor is meant for a starter kit you've just installed, before you've built anything on it.** It rewrites views in place, and it can't tell your work from the kit's — the more of your own is in there, the more there is for it to walk into.

It isn't reckless about it. A step that can't do its whole job does none of it and reports what it skipped, an existing file is never clobbered, and nothing is guessed at. But some of what it does is destructive by design:

- The [`lucide` kit](kits.md#lucide--flux-with-lucide-only) leaves `resources/views/flux/icon/` holding exactly the set it downloaded. **An icon blade you added there yourself is deleted**, because it isn't part of that set.
- [`remove-flux-overrides`](tasks.md#remove-flux-overrides--remove-published-flux-overrides) deletes the published Flux components it's told to. If you've restyled one, that file is the only copy of your edits.
- [`move-components`](tasks.md#move-components--move-non-routed-pages-components) moves anything under `pages/` that no `routes/*.php` file names as a quoted `pages::` literal. A page of yours routed some other way reads as non-routed, and moving it breaks the route.
- The icon kits rewrite icon names in **every** blade under `resources/views/`, yours included.

And none of it is undone by running it again: there's no inverse kit, and the tasks are one-way.

So the happy path is the first five minutes of a project — install the starter kit, run Tailor, then start building on the result. **On an app you've already built, commit first.** A clean tree makes the whole run one reviewable diff, and `git checkout .` the way back. Then read [Kits](kits.md) and [Tasks](tasks.md) for what each step touches before you tick it, and be picky at the prompt: every kit and task is optional, and `as-is` with nothing selected does nothing at all.

## Publish the config first

That run above uses Tailor's defaults, which is a fine answer if you have no opinions yet. But there's one command worth running before it:

```bash
php artisan vendor:publish --tag="tailor-config"
```

`config/tailor.php` isn't a few toggles bolted onto the side — **it's the whole product**. Every decision Tailor is about to make about your app is a line in that file, put there to be edited:

- **Which glyph replaces which.** The icon maps are a lookup you can point anywhere, for icons of your own as much as the starter kit's. See [Swap the icons](extending.md#swap-the-icons).
- **What your components are called.** `group-components` sorts them into folders you name, and the folder name is the prefix they render as — `<x-branding.app-logo>` is `branding` in the config, and nothing more.
- **What a converted partial declares**, which Flux overrides get deleted, and so on: each [task](tasks.md) reads its own decisions from its own key.
- **What you're even offered.** The kit and task lists drive the prompts. Drop the ones you'll never want and they stop being asked about; add your own and they're offered alongside the built-ins.

Three reasons to spend the two minutes now rather than later:

1. **Tailor edits your app, not its own.** What comes out is your code, and you'll be reading it long after this command is gone.
2. **Some of it is one-way.** The icon swap can't be re-run against already-swapped views, and a deleted Flux override is deleted. Re-running with a changed config won't walk those back — git will.
3. **Then you delete the tool.** Tailor is a one-time thing you remove when it's done, so this is the moment it takes direction.

It's a single, heavily commented file. Skim it, change what you disagree with, then run the command.

## Options

To skip the kit prompt, pass it directly:

```bash
php artisan tailor --ui-kit=lucide   # as-is | hero | lucide
```

## Icon reference page

In the `local` environment Tailor registers a Livewire page listing the icons it manages, handy for eyeballing the Heroicon → Lucide mapping:

```
/tailor/icons
```

This route is **never** registered outside `local`.

## Next

- [Kits](kits.md) — what each icon set does to your views.
- [Tasks](tasks.md) — the optional tweaks, and how to configure them.
