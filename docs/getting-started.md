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

**You do not need to add an `[x-cloak]` rule.** Nine components hide themselves
with `x-cloak` until Alpine boots, and Alpine does not ship the CSS rule that
makes the attribute mean anything — every project is normally expected to write
it by hand. Since it is *our* components that break without it, `theme.css`
carries it. If you already have your own copy, it is harmless.

Without the rule, every overlay on the page renders in full from first paint
until the script runs: on a page with several modals, all of them at once with
their backdrops. It is the `@source` failure again — nothing errors, and it is
worst on the slow connections you are least likely to be testing on.

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

## Driving components from client-side state

Two rules that are not obvious from the prop tables, and that both fail
**silently** — HTTP 200, nothing in the log, a component that looks right until
you interact with it. If you are using this package with plain Alpine rather
than Livewire, read both. Livewire re-renders on the server and sidesteps them
entirely.

### `open` is a reference, not a condition

`modal`, `drawer` and `sheet` do not just *read* `open`. They **write** to it —
the close button, the backdrop and Escape all set it to `false`. So it has to be
something JavaScript can assign to.

```blade
{{-- Broken: opens, and then nothing closes it --}}
<div x-data="{ side: null }">
    <x-ds::button @click="side = 'right'">Open</x-ds::button>
    <x-ds::drawer open="side === 'right'" title="Filters">…</x-ds::drawer>
</div>
```

That compiles to `side === 'right' = false`. Reading the expression is fine, so
the drawer **opens correctly**, and then the ✕, the backdrop and Escape all do
nothing at all. The only evidence is `Uncaught SyntaxError: Invalid left-hand
side in assignment` in the browser console.

```blade
{{-- Works: one property per panel --}}
<div x-data="{ show: { right: false, left: false } }">
    <x-ds::button @click="show.right = true">Open</x-ds::button>
    <x-ds::drawer open="show.right" title="Filters">…</x-ds::drawer>
</div>
```

Since v1.1 the three components **refuse** a non-assignable `open` with an
exception at render time, naming the component and the expression, rather than
letting it through to the console.

### `tone`, `variant` and `size` resolve to classes on the server

Every prop that selects a look is a lookup of literal class strings in PHP, done
once when the view renders. That is not an implementation detail to work around
— it is what lets Tailwind's scanner see the classes at all, and it is why the
set of values is closed. See [the trap it prevents](../CLAUDE.md).

The consequence is the part worth knowing: **binding one of those props to
Alpine state does nothing.**

```blade
{{-- Renders every toast neutral, whatever item.tone says --}}
<template x-for="item in toasts">
    <x-ds::toast ::tone="item.tone" title="Saved" />
</template>
```

`::tone` sets a `tone` *attribute* on the rendered element. Nothing reads it —
the classes were chosen before the browser ever saw the markup. Affected props:

| Component | Props |
|---|---|
| `toast` | `tone` |
| `badge` | `tone`, `variant` |
| `alert` | `tone` |
| `sheet-item` | `tone` |
| `button` | `variant`, `size` |
| `tab` | `active` |
| `modal`, `drawer` | `size` |

Two ways out.

**Re-render on the server** when the state lives there anyway — a Livewire
component, or a fresh page. Nothing to do; it already works.

**Bind the classes yourself**, with Alpine's **object** syntax:

```blade
<x-ds::tab
    controls="p-overview"
    :active="true"
    ::class="{
        'border-fg text-fg': tab === 'overview',
        'border-transparent text-fg-muted hover:border-border-strong hover:text-fg': tab !== 'overview',
    }"
    ::aria-selected="tab === 'overview'"
    ::tabindex="tab === 'overview' ? 0 : -1"
    @click="tab = 'overview'"
>Overview</x-ds::tab>
```

**The object form is not optional here.** Alpine's *string* form of `:class` only
**adds** classes — it never removes one that was already on the element. The
component server-rendered `border-transparent`, so the element ends up carrying
both and the underline is decided by whichever rule Tailwind emitted last:

```
class="… border-b-2 border-transparent text-fg-muted border-fg text-fg"
```

The object form removes a class whose value is falsy even when the server put it
there, which is the whole difference. Keep the PHP prop as well as the binding —
`:active="true"` above — so the first paint is right before Alpine boots, and
the binding takes over from there. That is the same rule the components follow
internally: bind for what moves, render what does not.

A worked example, arrow keys and all, is in [specs/tabs.md](../specs/tabs.md).

### Where this bites hardest: `toast`

A toast list is *inherently* dynamic, so it is the case where reaching for
`::tone` is most natural and most disappointing. Until a tone-per-item toast is
possible, the workaround is one `<template x-for>` per tone over a filtered
list:

```blade
@foreach (['success', 'danger'] as $tone)
    <template x-for="item in toasts.filter(t => t.tone === '{{ $tone }}')" :key="item.id">
        <x-ds::toast tone="{{ $tone }}" ::title="item.title" x-text="item.body" />
    </template>
@endforeach
```

That is correct, and it has a real cost: toasts group by tone rather than by
arrival order. If arrival order matters more than tone, render the region from
the server instead. See [toast.md](components/toast.md#a-dynamic-list-of-toasts).

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
| An overlay flashes on page load | `theme.css` is not imported, or is imported after your own rules override it. The `[x-cloak]` rule ships in it. |
| A dialog opens but the ✕, the backdrop and Escape all do nothing | `open` was given a comparison rather than a reference. See [Driving components from client-side state](#driving-components-from-client-side-state). |
| A `tone` or `variant` never changes when your Alpine state does | Expected — those resolve to classes on the server. Same section. |
| An empty state renders without its button | The button went in the default slot. It belongs in `<x-slot:action>`. |
| A modal or dropdown does nothing | Alpine is not loaded, or there is no `x-data` scope above it. |
| An icon renders enormous | You passed a `size` outside the generated range — see [icon.md](components/icon.md). |
| Dark mode does nothing | The `dark` class is on `<body>` rather than `<html>`, or `theme.css` is imported before `tailwindcss`. |
| `rounded-lg` changed size in your app | Not us — this system deliberately never redefines a Tailwind default. Check your own theme. |
