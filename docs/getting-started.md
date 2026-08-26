# Getting started

Install the package, add three lines of CSS, and every component works.

> **This page is the Blade package.** If you are building in React or Vue, you
> want `@shipbytes/react` / `@shipbytes/vue` from npm instead — see
> [which package do I need?](../README.md#which-package-do-i-need). A Blade
> project needs nothing from npm at all.

## 1. Install

```bash
composer require shipbytes/blade-ui
```

Laravel discovers the service provider automatically. There is nothing to
register.

**Requires** PHP 8.2+, Laravel 11/12/13, and **Tailwind CSS v4**.

## 2. Add the CSS

In `resources/css/app.css`:

```css
@import 'tailwindcss';
@import '../../vendor/shipbytes/blade-ui/dist/tokens.css';
@import '../../vendor/shipbytes/blade-ui/dist/theme.css';
@source '../../vendor/shipbytes/blade-ui/resources/views';
```

Three imports and one `@source`. There is **no npm install** — the composer
package ships the compiled CSS.

### The `@source` line is not optional

This is the single most likely thing to go wrong, and it fails **silently**.

Tailwind does not scan `vendor/`. Without that line, every class the components
emit is discarded as unused, and the whole system renders as unstyled HTML.
Nothing errors, because "unused" is not a failure — you just get a page of plain
black text on white and no clue why.

If your components render but look like nothing, this line is missing.

> `theme.css` brings its own `@custom-variant dark`, so you do not need to
> declare dark mode yourself. Three imports, not four.

## 3. Use a component

```blade
<x-ds::button>Save changes</x-ds::button>
<x-ds::badge tone="success">Active</x-ds::badge>
<x-ds::input name="email" label="Email address" />
```

Every component lives under the `ds` namespace, so it can never collide with
your own components.

## Dark mode

Put a `dark` class on `<html>`. Nothing else changes — no second set of
classes, no per-component prop.

```html
<html class="dark">
```

How you decide to put it there is yours: a user setting, a cookie, or
`prefers-color-scheme` read in a small inline script.

## Alpine

**You do not need Livewire.** This package depends on `illuminate/support`,
`illuminate/view` and `blade-heroicons`, and nothing else. Every component works
in a plain Blade view.

Six need [Alpine](https://alpinejs.dev) — a single 15 kB script, not a
framework: **modal, drawer, sheet, dropdown, select, tooltip**.

Four more use it for **one optional prop** each, and work without it otherwise:
`checkbox`'s `indeterminate`, `nav-item`'s `collapsedWhen`, and `toast`'s
`dismiss`. Skip those props and you need no JavaScript at all.

```html
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
```

Add this to your CSS so overlays do not flash on first paint:

```css
[x-cloak] { display: none !important; }
```

**No plugins are needed.** The focus traps are written into the components
rather than delegated to `@alpinejs/focus`, because Alpine silently ignores a
directive it has no handler for — a missing plugin would leave you with a modal
that looks correct and traps nothing.

### The components hold no state

You declare the state; the component reads it. That is what keeps the same
markup usable from plain Blade, a Livewire component, or a Volt page.

```blade
<div x-data="{ confirmOpen: false }">
    <x-ds::button @click="confirmOpen = true">Delete</x-ds::button>

    <x-ds::modal open="confirmOpen" title="Delete report?" size="sm">
        This cannot be undone.
        <x-slot:footer>
            <x-ds::button variant="ghost" @click="confirmOpen = false">Cancel</x-ds::button>
            <x-ds::button variant="danger">Delete</x-ds::button>
        </x-slot:footer>
    </x-ds::modal>
</div>
```

`dropdown` and `tooltip` are the exceptions: leave `open` off and they scope
their own state, so a menu on every row of a table needs no wrapper.

### Livewire, if you happen to use it

Nothing changes. The components hold no state, so they work the same inside a
Livewire component as in a plain view — `wire:model` on a field, `wire:click` on
a button, `@entangle` for a modal's open state. Two small courtesies are built
in: `panel` passes `wire:navigate` through to its header link, and `stat-tile`
re-runs its count-up after a Livewire page swap.

Neither costs anything when Livewire is not installed.

## Pagination

Point Laravel's paginator at the two views, once, in a service provider:

```php
use Illuminate\Pagination\Paginator;

public function boot(): void
{
    Paginator::defaultView('ds::pagination');
    Paginator::defaultSimpleView('ds::simple-pagination');
}
```

## Passing your own classes

Every component merges the attributes you give it, so layout stays yours:

```blade
<x-ds::button class="w-full">Save</x-ds::button>
<x-ds::panel class="mt-6">…</x-ds::panel>
```

Use this for **layout** — width, margin, grid placement. Reach for a prop for
anything about what the component *is*: `tone="danger"`, not
`class="bg-red-500"`. A class that changes meaning is an escape hatch that
becomes the norm.

## Upgrading

Tags are what `^1.0` resolves against:

```bash
composer update shipbytes/blade-ui
```

## Troubleshooting

| What you see | Almost certainly |
|---|---|
| Everything renders unstyled | The `@source` line is missing. See above. |
| An overlay flashes on page load | The `[x-cloak]` CSS rule is missing. |
| A modal or dropdown does nothing | Alpine is not loaded, or there is no `x-data` scope above it. |
| An icon renders enormous | You passed a `size` outside the generated range — see [icon.md](components/icon.md). |
| Dark mode does nothing | The `dark` class is on `<body>` rather than `<html>`, or `theme.css` is imported before `tailwindcss`. |
| `rounded-lg` changed size in your app | Not us — this system deliberately never redefines a Tailwind default. Check your own theme. |
