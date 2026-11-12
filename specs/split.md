# Split

Two or more panes with a divider between each pair that the reader can drag.
Covers `split` and `split-pane`.

## Anatomy

```
┌──────────┬┬─────────────────────────┐
│ Files    ││ Editor                  │
│          ││                         │
│          │├═════════════════════════┤  ← a nested vertical split
│          ││ Console                 │
└──────────┴┴─────────────────────────┘
           ↑ divider: a 1px line, a 24px hit area, role=separator
```

- **The split** is a flex row (`horizontal`, panes side by side) or column
  (`vertical`, panes stacked) filling its box (`size-full`): give the box a
  height.
- **A pane** is sized along the split by `--ds-split-size`, written by PHP
  from `size` (`%`, `px`, `rem`, `em`, or a number of pixels), and limited by
  `min` and `max` as `min-width` / `max-width` (or heights). A pane without a
  size grows into the room the others leave: leave one without. A pane
  scrolls its own content.
- **The divider** is drawn after every pane and hidden after the last (the
  split needs no count of its panes). A 1px `border` line with a 24px hit
  area in its `::before`, `accent` on hover, while dragged and on focus,
  where it also draws the focus ring.
- **The shield** covers the window while a divider is dragged, so an iframe
  in a pane cannot take the pointer, and carries the resize cursor.

## First paint

The sizes are CSS variables in the `style` attribute, so the panes are at
their sizes before any script runs, and a host without the script gets fixed
panes at exactly those sizes. A pane with no size writes
`--ds-split-size: initial`, because custom properties inherit and a pane of a
split nested in a sized pane would otherwise take that size as its own.

## Behaviour (`dsSplit`)

A divider moves the boundary between the pane before it and the pane after
it: the two sizes always add up to what they had, and each pane's limits hold.
It names and values **one** of them, the pane it controls (`aria-controls`):
the one before it, unless only the one after it is `collapsible`.

| Input | Result |
|---|---|
| Drag the divider | Resizes, under pointer capture; the cursor and no text selection only while dragging |
| ArrowLeft / ArrowRight (ArrowUp / ArrowDown) | Moves it 1% of the split; with Shift, 10% |
| Home / End | The controlled pane to its smallest / largest |
| Enter, double-click | Folds a collapsible pane to 0 and brings it back at its size |
| Below `stackBelow` | Panes stack at their natural size, dividers hide and do nothing |

Sizes are written back as percentages of the split, so each pane keeps its
share when the window changes. While a drag moves it dispatches `resize`, at
most once a frame; on release, after each key and after a fold, `resized`;
both from the root, bubbling, with `{ name, sizes }`, a percentage per pane.
It stores nothing: persisting is the host's (see the docs for the recipe).

Only the split's own children are its panes and dividers, so a split inside
a pane is a split of its own, keys and all.

## Accessibility

| | |
|---|---|
| Role | `separator`, focusable: the ARIA window splitter pattern |
| Orientation | `aria-orientation` is the divider's own: `vertical` between side-by-side panes |
| Value | `aria-valuenow`, `aria-valuemin`, `aria-valuemax`: the controlled pane's share in percent, and its range (from 0 when it folds) |
| Name | `label` on the split, for every divider in it |
| Focus | `tabindex="0"` once the script runs, the system ring; without the script not focusable at all, since it would do nothing |
| A folded pane | `visibility: hidden`, so nothing in it can be tabbed to |

## Out of scope

- Storing the sizes. The events carry them; the host stores them.
- Dragging a pane to another place, tabs inside panes.

## Do not

- **Do not size every pane.** One pane without a size takes up the slack; with
  all of them sized, sizes that do not add up leave a gap or squeeze.
- **Do not put the split in a box with no height** when it is vertical, or
  nested: `size-full` of nothing is nothing.
- **Do not hide important content in a collapsible pane** with no other way to
  reach it on a phone: use `stackBelow`.
