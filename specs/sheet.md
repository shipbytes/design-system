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

The **handle** is decorative and marked `aria-hidden` on a 1.x sheet. It signals
the sheet can be pushed away; the backdrop and the close button are what
actually do it. With snap points it becomes a button (below). Its
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


### `open` must be assignable

`open` is a **reference**, not a condition. The component does not just read it —
the close button, the backdrop both write to it:

```blade
@click="{{ $open }} = false"
```

```blade
{{-- Broken: opens, and then nothing closes it --}}
<x-ds::sheet open="mode === 'more'">

{{-- Works --}}
<div x-data="{ show: { more: false, share: false } }">
    <x-ds::sheet open="show.more">
</div>
```

The broken form compiles to `mode === 'more' = false`. Reading the expression is fine,
so the sheet **opens correctly**, and then every dismiss path silently does
nothing. HTTP 200, a clean server log, and one `Uncaught SyntaxError: Invalid
left-hand side in assignment` in a console nobody had open. It cost a consumer
half an hour, which is why it is a render-time exception now
(`Support\OpenState`) rather than a docblock alone.

## Snap points, drag, the handle (2.0)

Props `snap`, `initialSnap`, `draggable`, `dismissible`, `handle`, `modal`.
**Any one of them** renders the sheet through `partials/sheet-layer`; with
none, `sheet.blade.php` emits exactly the 1.x markup
(`tests/SheetUnchangedTest`, a fixture taken before the change), so an
existing sheet changes by not one byte.

- **Points** are strings the script resolves against the visual viewport:
  `'25%'`, `'320px'` (a bare number is pixels) and `'full'`, which is
  `maxHeight`. Unknown values are dropped. `initialSnap` is an index or a
  value; it defaults to the first point, not the lowest, so the author says
  where it opens by the order they write.
- **How it moves.** The panel is drawn as tall as its highest point and
  slid down by a transform to the one it shows. The move between points is
  the panel's CSS transition on the slide tokens (`data-dragging` turns it
  off while a finger holds it), not a spring: one motion vocabulary.
- **Release** projects the position 200 ms ahead at the release velocity and
  settles on the nearest point; a dismissible sheet nearer to nothing than
  to its lowest point closes.
- **The nested-scroll rule.** Below the highest point the body does not
  scroll (`overflow: hidden`, `touch-action: pan-x`), and a finger on it
  drags the sheet. At the highest it scrolls; at its scroll top its
  `touch-action` is `pan-x pan-down`, so the browser scrolls a push up and
  hands a pull down to the sheet.
- **The handle** is a button only when there is more than one point to go
  to, and it is inert (`tabindex=-1`, `aria-hidden`) until the script makes
  it work: without the script there is nowhere for it to go. Its words,
  "Sheet size" and "Expanded" / "Half" / "Collapsed", are fixed English for
  now, like the rest of the sheet's.
- **Keyboard**: Enter, Space or a press step up and wrap; ArrowUp and
  ArrowDown move one. Keyboard focus into the body below the top raises it.

| Key, on the handle | Does |
|---|---|
| Enter, Space | Up one point; from the highest, back to the lowest |
| ArrowUp | Up one point |
| ArrowDown | Down one point (never closes) |
| Escape (anywhere in it) | Closes it, when dismissible; in a stack, only the top layer |
| Tab | Trapped in the modal form; the region form is in the page's order |

  The handle is a `<button>` named "Sheet size", described by the status
  region, which says "Expanded", "Half" or "Collapsed" as it moves.
- **visualViewport**: heights from its height, and the frame lifted by the
  height of an on-screen keyboard.
- **Non-modal** (`modal="false"`): a `role="region"` over the page at `z-40`,
  with no backdrop, no trap, no lock and no place in the stack; Escape inside
  it closes it when dismissible. A region needs a name, so it throws without
  a `title` or `aria-label`.
- **Modal** (the default with any 2.0 prop): the modal's layer
  (`partials/modal.php`), so the trap, focus in and out, the scroll lock and
  the overlay stack are the modal's own.

## Do not

- Stack more than two sheets. A sheet over a sheet takes its place in the
  overlay stack; a third is a page.
- Give `open` a comparison. `open="mode === 'more'"` opens and then ignores
  both the ✕ and the backdrop. It has to be assignable — see above.
- Use a sheet above `lg`. It is the mobile form of a modal, not a second modal.
