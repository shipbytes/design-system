# Table

Twelve admin screens share one recipe. This is it.

## Anatomy

```
┌─ overflow-x-auto ────────────────────────────┐  ← scroll container
│ USER          ROLE      STATUS      JOINED   │  ← thead, surface-subtle
├──────────────────────────────────────────────┤
│ [av] Amara    Admin     Verified    Mar 04   │  ← row, hover
│ [av] Tobias   User      Unverified  Aug 19   │
└──────────────────────────────────────────────┘
   rounded-control · border · divide-divider
```

**The scroll container is not optional.** A wide table scrolls inside its own
rounded box; without it a single long cell makes the entire page scroll
sideways, which on a phone is indistinguishable from a broken layout.

## Columns

Passed as data, not markup:

```blade
<x-ds::table :columns="[
    'User',
    ['label' => 'Role', 'width' => 'w-16'],
    ['label' => 'Resumes', 'align' => 'center', 'width' => 'w-20'],
    ['label' => '', 'width' => 'w-14'],
]">
```

An empty label still reserves its width — that is how an actions column keeps
its space without inventing a heading for it. Headers use the `overline` style
(12/16, medium, 0.05em, uppercase) at `fg-muted`.

## Cells

`<x-ds::table-cell>` defines its own type — `body` at `fg-body`. The
hand-written cells set nothing and inherited the browser default, which is
16px: a size nobody chose, showing wherever a cell held bare text.

| Prop | Use |
|---|---|
| `align` | `left` (default), `center`, `right` |
| `nowrap` | Dates and counts. **Not** prose — a cell that refuses to wrap is what forces the scroll container to appear. |

Numbers that are compared down a column want `tabular-nums`; add it per cell
rather than globally, since it costs legibility on text.

## Rows

Rows hover by default, because in this app almost every row is clickable
somewhere. Pass `:hover="false"` for a genuinely inert row — a hover affordance
on something that does nothing is a lie, and the empty-state row is the usual
case.

## Empty state

A full-width cell with `colspan` covering every column, an icon, and a sentence.
It keeps the header visible: the reader needs to know *what* is empty, and a
table that collapses to a bare message loses that.

## Do not

- Reach for a table to lay out a card grid. If the columns do not mean the same
  thing on every row, it is not a table.
- Put a table inside a panel. Both draw a bordered container with a header; you
  get a double frame and two competing rules. Pick one.
- Right-align text. Right alignment is for numbers, where it lines up the units
  — on text it just makes the left edge ragged and the row hard to track.
