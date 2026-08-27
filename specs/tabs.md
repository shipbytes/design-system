# Tabs

One row, several views. Covers `tabs`, `tab` and `tab-panel`.

## The decision that matters: `navigation`

**This is not a styling flag. It is the accessibility contract, and getting it
wrong is a real bug.**

| | `navigation="false"` (default) | `navigation="true"` |
|---|---|---|
| The tabs are | buttons that swap panels on this page | links to other pages |
| Wrapper | `role="tablist"` | `<nav aria-label>` |
| Item | `role="tab"` + `aria-selected` + `aria-controls` | `<a>` + `aria-current="page"` |
| Arrow keys | expected, and the host must wire them | not expected |

`role="tablist"` **promises** a screen reader two things: arrow keys move between
tabs, and the content changes in place. Put that role on a row of page links and
both halves are false — arrow keys do nothing, and following one navigates away
from the tablist entirely. The reader is told they are in a tab widget and handed
something else.

Links get a `<nav>`, which is what they are.

## Anatomy

```
 Overview   Open 12   Archived
 ─────────                        ← 2px underline, fg
─────────────────────────────     ← 1px divider, the row sits on it
```

| Part | Token | Why |
|---|---|---|
| Rule | `divider` | The row pulls onto it with `-mb-px`, so the active underline covers the rule instead of drawing a second line under it. |
| Active | `border-fg` + `text-fg` | **Not `accent`.** A tab row is structure, not a link — colouring the active tab accent makes the *inactive* ones look like the links. |
| Inactive | `fg-muted`, `border-strong` on hover | |
| Count | `neutral-tint`, or inverse when active | `tabular-nums`, so the row does not reflow as counts change width. |

The row scrolls horizontally rather than wrapping. Tabs that wrap to two lines
stop reading as one row of peers.

## `count`

A number beside the label — "Open 12". **Not a status.** A count is how many
things are behind the tab; anything that needs a colour is a
[badge](badge.md) and probably does not belong in a tab.

## Panels

`tab-panel` is hidden with the **`hidden` attribute**, not a class.

That takes the inactive panel out of the accessibility tree *and* out of the tab
order for free — no `aria-hidden` to keep in sync, and nothing focusable inside
it that Tab can still reach. A panel hidden with `display:none` via a class is
the same thing, but `hidden` says so in the markup where a reviewer can see it.

Each panel carries `tabindex="0"` so a panel with no focusable content is still
reachable. Without it, a keyboard reader tabs straight past the content they just
selected.

## What the host owns

The components are presentation. The host owns which tab is active, and — for
real tabs — **the arrow keys**, because it is the only thing that knows the set.

The component does its half: roving `tabindex`, so only the selected tab is
tabbable and the rest are reachable only through the arrows the host wires.

```blade
<div
    x-data="{
        tab: 'overview',
        tabs: ['overview', 'activity'],
        go(name) {
            this.tab = name;
            // Focus follows the selection. Without this the reader is left on a
            // tab that is no longer the selected one, and the next arrow press
            // moves from the wrong place.
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
            id="tab-activity"
            controls="p-activity"
            x-ref="activity"
            ::class="{
                'border-fg text-fg': tab === 'activity',
                'border-transparent text-fg-muted hover:border-border-strong hover:text-fg': tab !== 'activity',
            }"
            ::aria-selected="tab === 'activity'"
            ::tabindex="tab === 'activity' ? 0 : -1"
            @click="tab = 'activity'"
        >Activity</x-ds::tab>
    </x-ds::tabs>

    <x-ds::tab-panel id="p-overview" labelledby="tab-overview" :active="true"
        ::hidden="tab !== 'overview'">…</x-ds::tab-panel>

    <x-ds::tab-panel id="p-activity" labelledby="tab-activity"
        ::hidden="tab !== 'activity'">…</x-ds::tab-panel>
</div>
```

That is longer than it looks like it should be, and every line of it is load
bearing. This exact markup is a specimen in `scripts/behaviour-specimens.blade.php`
and is driven by `npm run test:behaviour`, because two earlier versions of this
example were wrong in ways nothing reported.

### Why not `::active`

The first version of this spec documented `::active="tab === 'overview'"`. It
does not work. `::active` binds an `active` **attribute** on the rendered
element, and nothing reads that attribute — the component chose its classes from
the PHP `$active` prop when the view rendered, before the browser saw anything.
The panel switched and the tab never changed appearance. No error.

This is not a tabs quirk. **Any prop that resolves to a class string in PHP
behaves this way** — see
[Driving components from client-side state](../docs/getting-started.md#driving-components-from-client-side-state)
for the full list and the reason the props are built that way.

### Why the object form of `::class`, and not the string form

The obvious repair is worse, because it half works:

```blade
{{-- Broken, and it LOOKS right in the source --}}
::class="tab === 'x' ? 'border-fg text-fg' : 'border-transparent text-fg-muted'"
```

Alpine's **string** form of `:class` only *adds* classes. It never removes one it
did not add, and the component server-rendered `border-transparent`. So the tab
switches its panel, and the element ends up carrying both:

```
class="… border-b-2 border-transparent text-fg-muted border-fg text-fg"
```

The underline is then decided by whichever rule Tailwind happened to emit last,
which is not a decision anyone made. The **object** form removes a class whose
value is falsy even when it was in the original `class` attribute. That is the
only reason it is written the long way.

### Why `:active` AND `::class` on the same tag

`:active` is the PHP prop; `::class` is the Alpine binding. Keeping both is what
makes the first paint correct — the right tab is already selected before Alpine
boots, and in anything that never runs the JS the markup is still right. The
binding takes over from there.

Bind for what moves; render what does not. The components follow the same rule
internally, which is why `select` renders its own tick from PHP.

## Do not

- **Do not put `role="tablist"` on page links.** See the top of this file. This is
  the single most common tabs bug and it is invisible without a screen reader.
- **Do not hide required form fields in an inactive tab.** The browser cannot
  focus an invalid control inside a `hidden` panel, so submit fails with no
  visible reason and no way to find the field.
- **Do not use tabs for a sequence.** Tabs are peers; steps that must be done in
  order are a wizard, and tabs let the reader skip to step three.
- **Do not exceed what fits.** A scrolling row of eleven tabs hides most of them
  off-screen with no affordance saying so.
- **Do not bind `active`.** `::active` sets an attribute nothing reads. The tab
  will switch its panel and never look selected. See above.
- **Do not use the string form of `::class`.** It only adds classes, so the
  server-rendered `border-transparent` stays on the element and fights the one
  you just added.
