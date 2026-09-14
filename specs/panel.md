# Panel

A bordered container with an optional header and a divided body. Four copies on
the dashboard alone, plus most of the admin screens.

## Variants

| | Shell | Use |
|---|---|---|
| **list** (default) | `rounded-control`, `border` | One of several panels in a column or grid. Stays quiet. |
| **feature** (pass an `icon`) | `rounded-panel`, `border-strong` | A panel that sits on the page in its own right and needs to be found. |

The difference is not decoration. A list panel is scanned alongside its
siblings; a feature panel is a destination. Giving both the same weight makes
the column read as a table of equals.

## Header

```
┌──────────────────────────────────────┐
│ [icon]  Title            View all →  │  ← header
│         Subtitle                     │
├──────────────────────────────────────┤
│  row                                 │  ← body, divided
```

**The title wraps; the action does not.** The title is content — truncating it
hides information the reader came for, and in a narrow column ("Recent Cover
Letters" in a three-up grid) truncation triggers constantly. `text-balance`
keeps a two-line wrap from leaving an orphan word.

The action is a fixed short label and takes `whitespace-nowrap`. The
hand-written version let it wrap, which produced "View" above "all" — the
worst of both, since it cost a line *and* looked broken.

The action is `fg-muted`, not `accent`. The panel's content is the point; a
"View all" styled as a link competes with the data for attention.

## Body

**One inset, `px-5 sm:px-6`, on every surface in the panel** — the header, the
body and the row all read it, so a title, a toolbar and a row's text share a
left edge. There is no mode to choose and no way to end up with none.

Four values used to be in play: a feature header at `px-5 sm:px-6`, a plain
header at `px-4`, a padded body at `px-5 sm:px-6`, and a row at `px-4`. A panel
with an icon and a list therefore had its heading and its rows 8px out of step,
permanently.

`divider` is deliberately lighter than `border` — 5% against 10%. A divider
that matches the container's own edge makes a panel look like a table.

### The `body` prop is gone

There were two modes — `plain` (padded) and `rows` (bare, for lists whose rows
pad themselves) — and `rows` was the default.

Across 140 call sites in the first application to consume this, **`rows` was
chosen deliberately nought times**, and its default left **52 panels with their
content flush against the panel border**: forms, link lists, empty states and
toolbars, none of which is a row. This spec already said plainly what the two
modes did, and it was misused anyway. The documentation was never the problem.
The default was.

So the mode was removed rather than reversed. A prop nobody sets on purpose,
whose default is wrong for all but a handful of panels, is not offering a choice
— it is collecting mistakes. Leaving it deprecated would have kept two ways to
reach the edge, which is the thing this change exists to end.

## Reaching the edge

**Rows still carry their own padding, and the body no longer lacks it.** A row
hover that stops short of the panel edge reads as a rendering bug, so a divided
list breaks out of the inset with `<x-ds::bleed>` (`PanelBleed` in React) and
pads its own rows at `px-5 sm:px-6`:

```
<x-ds::panel title="On the bridge" icon="scale">
    <x-toolbar />
    <x-ds::bleed>
        <ul class="divide-y divide-divider">…</ul>
    </x-ds::bleed>
</x-ds::panel>
```

The bleed negates the inset exactly, which is only possible because there is one
inset to negate.

A negative margin is more fragile than padding that was never applied, and that
is the real cost of dropping `rows`. It is worth paying: **26 of the 33 list
panels** in the first consuming application hold a list *and* a toolbar, button,
empty state or pagination, so they need the inset and the breakout together. The
negative margin was unavoidable for the large majority; a mode serving the
remaining seven bought nothing but a second convention.

## Rows

`<x-ds::panel-row>` is `flex items-center gap-3`. Give it an `href` and it
becomes an `<a>` with a `surface-subtle` hover; without one it is a `<div>` and
has no hover state, because a hover affordance on something unclickable is a
lie.

Typical shape: a leading icon or thumbnail, a `min-w-0 flex-1` middle that
truncates, then trailing metadata and actions that `shrink-0`.

## Empty state

A panel with nothing in it still renders its header — the reader needs to know
*what* is empty. The body takes a centred message; see the empty-state spec for
the illustration rules.

## Do not

- Nest a panel inside a panel. If content needs its own frame inside a frame,
  the outer one should be a page section instead.
- Put a primary button in the header. The header action is navigation; an
  action that changes something belongs with the thing it changes.
