# Sheet

The mobile form of a modal or a drawer. In this app it is the "More" menu — the
largest single block in the shell.

## Why it stops short of the top

Capped at 85% of the viewport, never full height. Leaving the page visible
behind it is what makes a sheet feel *dismissible*; a full-height takeover reads
as a new screen, and the reader loses the thread of where they were.

## Anatomy

```
        ────                ← drag handle, decorative
┌───────────────────────┐
│ More Services      ✕  │  ← title (18/28) + close
├───────────────────────┤
│ [tile] Label       ›  │  ← rows scroll; the page behind does not
│        Description    │
```

The **backdrop** uses `scrim`, which stays dark in both themes. Deriving it from
`fg` would make it white in dark — lighting the page up instead of pushing it
back.

The **handle** is decorative and marked `aria-hidden`. It signals the sheet can
be pushed away; the backdrop and the close button are what actually do it. Its
colour is `fg/20` rather than a fixed grey, so it adapts to dark instead of
glowing.

## Rows

A tile, a label, an optional description, and a chevron.

**The chevron follows the element, not the styling.** It is a claim that the row
goes somewhere, so a link gets one and a submit button does not. Sign out is a
`POST` to `/logout`; giving it a chevron would promise a page it never shows.
Disabled rows fall out of the same rule rather than needing a special case —
they render as a `div`, and a `div` is not going anywhere.

## Tone

`neutral` | `accent` | `danger`. **Not a free-form colour.**

The source app gave every feature its own hue — teal, blue, pink, green, amber,
violet, emerald, indigo, purple — as wayfinding: you found a row by its green
tile rather than by reading. It was dropped.

Ten hues is exactly the sprawl this system exists to prevent, and the cost is
paid on every screen the palette touches, not just this one. The icon already
carries the recognition — the tile behind it was a second, weaker copy of the
same signal. Flattening the ten to `neutral` also makes the list *scannable*:
with everything shouting, the two rows that matter were invisible.

The two that keep a tone are the two that differ in **kind**, not merely in
feature:

| Row | Tone | Why |
|---|---|---|
| Admin Panel | `accent` | a role-gated door out of the app, not a feature of it |
| Sign Out | `danger` | destructive-adjacent, and the only row that ends the session |

A tone is a claim about what the row *is*. If a new row needs one, the question
to answer first is which of those two kinds it belongs to — not which colour is
still unused.

## Type

The title is `title` (18/28), a size added to the scale *because of* this
component. The scale originally jumped 14 → 20, which would have rounded 99
existing 18px usages up — a change disguised as a migration.

## Do not

- Nest a sheet inside a sheet. Two dismissible layers give the reader no way to
  know what "back" means.
- Use a sheet above `lg`. It is the mobile form of a modal, not a second modal.
