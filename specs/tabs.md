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
<div x-data="{ tab: 'overview' }">
    <x-ds::tabs label="Report sections">
        <x-ds::tab controls="p-overview" ::active="tab === 'overview'" @click="tab = 'overview'">
            Overview
        </x-ds::tab>
    </x-ds::tabs>

    <x-ds::tab-panel id="p-overview" ::active="tab === 'overview'">…</x-ds::tab-panel>
</div>
```

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
