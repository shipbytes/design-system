# Tabs

One row, several views. Covers `tabs`, `tab` and `tab-panel`.

![Tabs](../images/tabs.png)

## Decide this first: are they links?

**`navigation` is not a styling flag. It is the accessibility contract.**

| The tabs are… | Use | You get |
|---|---|---|
| buttons that swap content on this page | `navigation="false"` (default) | `role="tablist"`, `aria-selected`, `aria-controls` |
| links to other pages | `navigation="true"` | `<nav>`, `aria-current="page"` |

`role="tablist"` **promises** a screen reader that arrow keys move between tabs
and the content changes in place. Put it on a row of page links and both halves
are false — arrow keys do nothing, and following one navigates away entirely.

This is the most common tabs bug and it is invisible without a screen reader.

## Links to other pages

```blade
<x-ds::tabs label="Account settings" :navigation="true">
    <x-ds::tab href="/settings/profile" :active="request()->is('settings/profile')">Profile</x-ds::tab>
    <x-ds::tab href="/settings/billing" count="3">Billing</x-ds::tab>
</x-ds::tabs>
```

## Panels on this page

```blade
<div
    x-data="{
        tab: 'overview',
        tabs: ['overview', 'open'],
        go(name) {
            this.tab = name;
            // Focus follows the selection, or the reader is left on a tab that
            // is no longer the selected one.
            this.$nextTick(() => this.$refs[name].focus());
        },
        move(step) {
            const at = this.tabs.indexOf(this.tab);
            this.go(this.tabs[(at + step + this.tabs.length) % this.tabs.length]);
        },
    }"
>
    <x-ds::tabs
        label="Report sections"
        @keydown.right.prevent="move(1)"
        @keydown.left.prevent="move(-1)"
    >
        <x-ds::tab
            id="tab-overview"
            controls="p-overview"
            x-ref="overview"
            :active="true"
            ::class="{
                'border-fg text-fg': tab === 'overview',
                'border-transparent text-fg-muted hover:border-border-strong hover:text-fg': tab !== 'overview',
            }"
            ::aria-selected="tab === 'overview'"
            ::tabindex="tab === 'overview' ? 0 : -1"
            @click="tab = 'overview'"
        >Overview</x-ds::tab>

        <x-ds::tab
            id="tab-open"
            controls="p-open"
            x-ref="open"
            count="12"
            ::class="{
                'border-fg text-fg': tab === 'open',
                'border-transparent text-fg-muted hover:border-border-strong hover:text-fg': tab !== 'open',
            }"
            ::aria-selected="tab === 'open'"
            ::tabindex="tab === 'open' ? 0 : -1"
            @click="tab = 'open'"
        >Open</x-ds::tab>
    </x-ds::tabs>

    <x-ds::tab-panel id="p-overview" labelledby="tab-overview" :active="true"
        ::hidden="tab !== 'overview'">…</x-ds::tab-panel>

    <x-ds::tab-panel id="p-open" labelledby="tab-open"
        ::hidden="tab !== 'open'">…</x-ds::tab-panel>
</div>
```

That is more than it looks like it should be, and all of it earns its place.

**`::active` does not work**, even though it is the obvious thing to write. The
double colon is Blade escaping a colon so Alpine gets `:active` — but that binds
an `active` *attribute*, and nothing reads it. The component picked its classes
from the PHP `$active` prop when the view rendered. The panel switches and the
tab never changes appearance, with no error anywhere.

**The string form of `::class` does not work either**, which is the trap under
the trap:

```blade
{{-- Broken, and it looks right --}}
::class="tab === 'x' ? 'border-fg text-fg' : 'border-transparent text-fg-muted'"
```

Alpine's string form of `:class` only *adds* classes. The component
server-rendered `border-transparent`, so the element ends up carrying both and
the underline is settled by whichever rule Tailwind emitted last. The **object**
form removes a class whose value is falsy even when the server put it there.

**Keep the PHP `:active` alongside the binding.** That is what makes the first
paint right, before Alpine boots — and right in anything that never runs the JS.

## Props

| Component | Prop | Default | What it does |
|---|---|---|---|
| `tabs` | `label` | *required* | Names the set. |
| `tabs` | `navigation` | `false` | See above. |
| `tab` | `href` | — | Makes it a link. |
| `tab` | `active` | `false` | |
| `tab` | `controls` | — | `id` of the panel it controls. |
| `tab` | `count` | — | A number beside the label. |
| `tab` | `disabled` | `false` | |
| `tab-panel` | `id` | *required* | Must match the tab's `controls`. |
| `tab-panel` | `active` | `false` | |
| `tab-panel` | `labelledby` | — | `id` of the controlling tab. |

> **`active` resolves to classes on the server**, like every `tone`, `variant`
> and `size` prop in the system — which is why the example above binds the
> classes rather than the prop. See
> [Driving components from client-side state](../getting-started.md#driving-components-from-client-side-state).

## What you own

Which tab is active, and — for real tabs — **the arrow keys**, since only you
know the set. The component does its half with a roving `tabindex`, so only the
selected tab is in the tab order.

## `count` is a number, not a status

How many things are behind the tab. Anything that needs a colour is a
[badge](badge.md) and probably does not belong in a tab.

## Don't

- **Don't bind `active`.** `::active` sets an attribute nothing reads: the panel
  switches and the tab never looks selected.
- **Don't use the string form of `::class`.** It only adds classes, so the
  server-rendered `border-transparent` stays and fights the one you added.

- **Don't put `role="tablist"` on page links.** See the top of this page.
- **Don't hide required form fields in an inactive tab.** The browser cannot focus
  an invalid control inside a hidden panel, so submit fails with no visible
  reason.
- **Don't use tabs for a sequence.** Tabs are peers; steps are a wizard.

More in [specs/tabs.md](../../specs/tabs.md).
