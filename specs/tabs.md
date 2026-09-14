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
─────────────────────────────     ← 1px rule, an INSET SHADOW on the row
```

| Part | Token | Why |
|---|---|---|
| Rule | `divider` | Drawn as an **inset shadow on the row**, not a bottom border — `shadow-[inset_0_-1px_0_var(--ds-divider)]`. A child's border paints over its parent's inset shadow, so the active tab's 2px underline covers the rule without the item hanging below the row. **The item has no negative margin. Do not give it one** — see below. |
| Active | `border-fg` + `text-fg` | **Not `accent`.** A tab row is structure, not a link — colouring the active tab accent makes the *inactive* ones look like the links. |
| Inactive | `fg-muted`, `border-strong` on hover | |
| Count | `neutral-tint`, or inverse when active | `tabular-nums`, so the row does not reflow as counts change width. |

The row scrolls horizontally rather than wrapping. Tabs that wrap to two lines
stop reading as one row of peers.

### Why the rule is an inset shadow, and not a border

**This is the part a port will otherwise undo.** The obvious markup — a
`border-b` on the row, and `-mb-px` on the item to pull the row onto it — is what
this component shipped with, and it drew a **vertical scrollbar on every tab row
in every consuming app** on platforms with classic scrollbars. macOS overlay
scrollbars hid it, which is how it survived review.

Two causes, and it needs both:

1. `overflow-x-auto` on the row forces the computed `overflow-y` off `visible`
   to **`auto`**. CSS has no "scroll one axis only": ask for one and you get the
   other. So the row was vertically scrollable although nothing declared it.
2. `-mb-px` on the item left each tab's border box exactly **1px below the row's
   padding box** — the negative margin was deliberate and did its job, but the
   overflow it created was now inside a scrollable box.

One pixel of overflow, and classic scrollbars drew 15px of chrome for it, taking
that width off the row as well.

Measured on the row itself. The **overflow** columns are what
`npm run test:behaviour` reproduces and now guards; the scrollbar column is what
a classic-scrollbar platform draws for that overflow, reported from a real
screen. Headless Chrome cannot show it — it draws zero-width overlay scrollbars
whatever `--disable-features=OverlayScrollbar` is set to, which is precisely why
the test asserts the overflow and not the scrollbar.

| | computed `overflow-y` | clientHeight | scrollHeight | scrollbar |
|---|---|---|---|---|
| border + `-mb-px` (as shipped) | `auto` | 45 | 46 | **15px** |
| **inset shadow, no `-mb-px`** | `auto` | 46 | 46 | 0 |
| `overflow-y: hidden` | `hidden` | 45 | 46 | 0 |
| `padding-bottom: 1px` | `auto` | 46 | 46 | 0 |

Only the inset shadow **removes** the overflow. The other two hide it:

- **`overflow-y: hidden` keeps the bug and clips it.** `scrollHeight` is still 46
  against a `clientHeight` of 45 — the overflow is there, merely cropped. It
  crops the bottom pixel of the active underline, and it would crop the **focus
  ring** too, which is drawn 2px *outside* the element by
  `focus-visible:outline-offset-2`. Trading a cosmetic scrollbar for an
  invisible keyboard focus ring is an accessibility regression.
- **`padding-bottom: 1px` detaches the underline.** It puts the row's bottom edge
  1px below the tab's, which is the two-lines problem `-mb-px` existed to
  prevent, in mirror image.

Dark mode needs nothing extra: the utility resolves `var(--ds-divider)` **at the
row**, so it picks up whatever the current theme has set — including under a
`.dark` applied to a subtree rather than to `<html>`.

#### The focus ring is drawn INSIDE the tab

`overflow-x-auto` does not only make the row scrollable — it makes the row **clip
paint**, on both axes. So an outline offset *outward* is cut off by the row's
edges. With `outline-offset-2` and a 2px ring that is 4px outside the tab, and it
was clipped 4px at the top and 4px at the bottom: the ring rendered as **two
disconnected vertical bars** rather than a rectangle around the tab.

Measured before and after the rule change — 4px either way. It is not a
consequence of the inset shadow; it predates it, and both the old markup and the
new one clip identically. It is the same root cause wearing a different hat.

The tab therefore uses a **negative** offset, `focus-visible:-outline-offset-2`,
so the ring is painted within the tab's own box and nothing can clip it. It is
the one place in the system that does this, and this is why.

Padding on the row is **not** the alternative. It would move the row's padding
box down away from the tabs, and the rule is an inset shadow drawn at that
padding box's bottom edge — so the rule would detach from the tabs, which is
exactly the failure `-mb-px` existed to prevent.

`tab-panel` keeps the ordinary outward `outline-offset-2`: it is not inside the
scrolling row, so nothing clips it.

#### Why an arbitrary value rather than a `shadow.rule` token

A named token would be more in keeping with a repo that names shadows for the
job (`raised`, `float`, `overlay`). It was tried, and the build cannot carry it:

- A DTCG reference — `"$value": "inset 0 -1px 0 {semantic.divider}"` — is
  **flattened to the light literal** by `resolve()` in `scripts/build-tokens.mjs`,
  emitted once at `:root`, with no `.dark` counterpart. Dark mode dies silently.
  This is the same trap CLAUDE.md records for `var()` inside a custom property.
- Writing the raw `var(--ds-divider)` into the token value survives the build,
  but it is no longer portable DTCG — which is the stated reason the token files
  are the shape they are — and `theme.css` does **not** re-declare shadows inside
  `.dark`, so it would resolve correctly only by the accident of `.dark` sitting
  on the same element as `:root`.

The arbitrary value is therefore not merely the smaller change; it is the one
that themes correctly. Revisit only if `build-tokens.mjs` grows real support for
a composite token that references a themed colour.

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
- **Do not draw the rule as a `border-b` on the row, and do not put `-mb-px` back
  on the item.** That pair is what put a vertical scrollbar on every tab row that
  has shipped. See "Why the rule is an inset shadow".
- **Do not "fix" a scrollbar on the row with `overflow-y: hidden`.** It clips the
  overflow rather than removing it, and takes the focus ring with it.
- **Do not give the tab an outward `outline-offset`.** The row clips paint on both
  axes, so the ring is cut into two vertical bars. See above.
- **Do not bind `active`.** `::active` sets an attribute nothing reads. The tab
  will switch its panel and never look selected. See above.
- **Do not use the string form of `::class`.** It only adds classes, so the
  server-rendered `border-transparent` stays on the element and fights the one
  you just added.
