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

| `body` | Behaviour |
|---|---|
| `rows` (default) | Children separated by `divider`, each managing its own padding |
| `plain` | One padded region, for free-form content |

**Rows carry their own padding, the body does not.** A row hover that stops
short of the panel edge reads as a rendering bug.

`divider` is deliberately lighter than `border` — 5% against 10%. A divider
that matches the container's own edge makes a panel look like a table.

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
