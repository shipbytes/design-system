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

## Sorting

Presentation only: the component says which column the rows are sorted by and
gives each sortable header a way to ask for an order. It never reorders rows.

```
│ REPORT ⇕    STATUS    TOTAL ▾ │   ⇕ only on hover/focus · ▾ the sorted column
```

- A column opts in with `sortable` and a `key`; the table takes `sort` (a key)
  and `direction` (`asc` | `desc`, anything else reads as `asc`).
- **Header control.** A sortable header's content is a link (`sortUrl`
  pattern or closure, default the current URL with `sort`, `direction` and no
  `page`) or, with `sortAction`, a `button type=button` with that
  `wire:click`. The label is in a span, the arrow an `aria-hidden` mini icon.
  `group/sort -mx-1 px-1 rounded-chip inline-flex gap-1`, the focus ring of
  every other control, `hover:text-fg`.
- **Next direction.** The sorted column asks for the opposite; every other
  asks for `asc`.
- **States.** Sorted: label `text-fg`, `chevron-up` (asc) or `chevron-down`
  (desc) in `text-fg`, always shown, and `aria-sort` on the `th`. Sortable,
  not sorted: `chevron-up-down` in `fg-subtle` at opacity 0, raised to 1 on
  `group-hover` and `group-focus-visible`, `motion-safe` fade at
  `duration-hint-enter`; the space is kept so nothing moves. Right-aligned:
  `flex-row-reverse`, arrow inside.
- **One `aria-sort`.** Only the sorted column has it. "none" is not written
  on the rest: it would add a word to every header for no information.
- **No key, no sort.** A sortable column without a key throws, naming the
  column, at render time: a header that links to `?sort=` would sort by
  nothing, silently.
- **Unchanged without it.** A table with no sortable column renders
  byte for byte what it did before sorting existed; `TableUnchangedTest`
  holds the 1.x output.

Single column only. Multi-column sort, resizing and client-side sorting are
not built.

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
- Sort the rows in the view or in Alpine. The order is the query's.
- Right-align text. Right alignment is for numbers, where it lines up the units
  — on text it just makes the left edge ragged and the row hard to track.
