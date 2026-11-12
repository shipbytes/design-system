# Context menu

A menu of actions opened at a place in a region rather than from a button: a
right-click on a row, a long press on a card, Shift+F10 on the focused item.
Needs Alpine and the package's script (`dsContextMenu`, `x-ds-anchor`).
Shares its panel, transition and arrow keys with `ds::dropdown` through
`partials/menu.php`, and its items are `ds::dropdown-item`.

## Anatomy

```
┌ region (the default slot) ───────────────────────┐
│  Q3 revenue          Ready         £1,200        │
│        ✛ ← the pointer                           │
│        ╭──────────────────╮  ← div[role=menu], fixed, 2px off the
│        │ ✎ Edit           │    point, data-side="bottom"
│        │ ⧉ Duplicate      │    data-align="start"
│        │ 🗑 Delete         │
│        ╰──────────────────╯
└──────────────────────────────────────────────────┘
```

- **Root, the region**: `div[data-ds-context-menu]`,
  `x-data="dsContextMenu({ native })"`. No classes of its own: it is a block
  that holds what the consumer put in it, and their attributes go on it.
- **Panel**: last child of the root, `div[role=menu][aria-orientation=vertical]
  [tabindex=-1][data-ds-context-menu-panel]`, `x-show="dsOpen"`, `x-cloak`,
  `x-ds-anchor` at `dsAt`. Surface: the menu recipe, `z-50 min-w-52
  overflow-hidden rounded-control border-border bg-surface py-1 shadow-float`,
  plus `fixed left-0 top-0` so it takes no room before it is placed.
- **Items**: `ds::dropdown-item` in the `menu` slot.

## Variants

None.

## Placement

At the place it was opened from, as `x-ds-anchor`'s `at` box, measured from the
region's top-left corner so it moves with the region when the page scrolls:

| Opened by | Box | Offset |
|---|---|---|
| right-click | the pointer, a point | 2px |
| long press | the finger, a point | 2px |
| Shift+F10, Menu key | the focused element's box | 4px |

`bottom-start`: below the point, its leading edge on it. Near the foot of the
window it flips above. Near the inline end it takes the other alignment and
opens back across the point, as the platform's own context menus do, rather
than sliding along the edge until it covers what was pressed; that is the
anchor's behaviour for an `at` box only, and is written as `data-align`. The
enter origin reads `data-side` and `data-align`, so the menu grows from the
corner at the pointer.

## States

| State | Treatment |
|---|---|
| closed | `display: none` (x-cloak until Alpine, then x-show) |
| opening | `opacity-0 scale-95` → `opacity-100 scale-100`, `duration-popover-enter ease-enter`, `motion-safe` only |
| open | as drawn, focus on the first enabled item |
| closing | the reverse, `duration-popover-leave ease-leave` |

## Behaviour

- **Right-click** anywhere in the region: `preventDefault` on `contextmenu`,
  open at `clientX`/`clientY`. Again while open: it moves there.
- **Shift+F10 or the Menu key** on the focused element inside the region:
  `preventDefault`, open below that element. A `contextmenu` the browser sends
  after the key within 500ms is swallowed, not answered twice.
- **Long press** (touch pointer only): open after 500ms held within 10px. More
  movement, a `pointerup` or a `pointercancel` first cancels it. The click
  that ends a hold that opened the menu is swallowed in the capture phase, so
  a held link or button is not also pressed.
- **The browser's menu is kept** over an `input`, `textarea`, `select`,
  editable element or `a[href]` inside the region, for the mouse, the keys and
  the long press alike. `native: false` takes those too.
- Right-click inside the panel is prevented and does nothing.
- **Closes** on Escape (propagation stopped), Tab (prevented), a click on an
  enabled item, or a pointerdown outside the panel (captured on the document
  while open). A right-button press inside the region is not "outside".
- **Focus** goes to the first enabled item on every open, or the panel when
  there is none. On close it goes back to the element that had it when the
  menu opened. After a press outside it goes back only if the press left focus
  on the body; a press on something focusable keeps focus there.
- **Nesting**: a region inside a region takes the event first and prevents
  it; the outer one sees `defaultPrevented` and stands down.
- The scope exposes `dsOpen`, `dsAt` and `dsTarget`, the element pressed, so
  an item can act on the row it was opened over.

## Choices and why

- **The default slot is the region; the items go in `menu`.** The region is
  what the page shows and what the reader presses; the menu is secondary.
- **A requirement, not an enhancement.** Without the script there is no menu
  to show and nothing prevents the browser's, so the page keeps the browser's
  own menu; the `Behaviour::data()` scope reports the missing include.
- **The browser's menu stays on fields and links by default.** Copy, paste,
  spelling and "open in new tab" are what a reader right-clicks a field or a
  link for, and replacing them is a loss the region's actions rarely repay.
- **Tab returns focus rather than moving on.** The panel sits at the end of
  the region in the source, so Tab from it would land somewhere unrelated to
  where the reader was.
- **The dropdown's panel and items, shared, not copied.** A menu is a menu:
  the same surface, the same keys, the same focus ring.
- **A point flips its alignment, not just its side.** Shifting a menu opened
  near the right edge back over the pointer would cover the row it acts on.

## Limits

- The region needs something focusable for the keyboard to reach it: a row
  with `tabindex="0"`, a link, a button. A region of plain text opens only
  from the pointer.
- A context menu is not discoverable. Every action in it must also be
  reachable another way (a row's dropdown, a toolbar).
- The anchor's limits apply: inside a transformed ancestor the menu is placed
  correctly but can be clipped by that ancestor.
- iOS shows its own callout on a held link even with `native: false`; the
  long press there is for the rest of the region.

## Accessibility

| | |
|---|---|
| Role | `menu` on the panel, `aria-orientation="vertical"`; items are `menuitem` (`ds::dropdown-item`) |
| Name | `aria-label` from `label` |
| Keyboard | Shift+F10 or the Menu key opens it at the focused element. Arrows move and wrap, skipping disabled items; Home and End. Enter or Space chooses. Escape and Tab close |
| Focus | To the first enabled item on open; back to the element that had it on close |
| Pointer | Right-click; a touch held 500ms |
| Motion | Fade and 95% scale from the corner at the pointer, `motion-safe` only |

## Do not

- **Do not put an action only here.** It is invisible until someone thinks to
  right-click.
- **Do not wrap a whole page in one.** A region is the thing the actions act on.
- **Do not turn `native` off over a form.** The browser's menu is how people
  paste.
