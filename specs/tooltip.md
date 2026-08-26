# Tooltip

A short label that appears on hover or focus. Almost always attached to an
icon-only button.

## It is supplementary, never the only copy

`aria-describedby`, not `aria-label`. The trigger keeps its own accessible name;
the tip **describes** it.

This matters because a tooltip is the least reliable place in an interface to put
information: it does not exist on a touch screen (there is no hover), it does not
exist in a printout, and it disappears the moment the pointer moves. Anything a
reader *must* know cannot live only here.

The common mistake is an icon-only button whose only label is its tooltip. Give
the button an `aria-label` and let the tip add to it.

## Anatomy

```
   ┌───────────────┐
   │ Delete report │   ← surface-inverse, on-inverse, text-meta
   └───────┬───────┘
          [🗑]           ← the trigger, in the slot
```

| Part | Token | Why |
|---|---|---|
| Ground | `surface-inverse` + `on-inverse` | Inverted so it reads as an overlay on any background, not as a small card. |
| Elevation | `shadow-float` | It hovers. It does not block. |
| Type | `text-meta` | 12px, `max-w-56`. Longer than that and it wants to be help text. |

`placement`: `top` (default) | `bottom` | `left` | `right`. Position and centring
transform come from one map — split apart, a placement change keeps the previous
offset and the tip sits off-centre.

## Hover *and* focus, always both

`@mouseenter`/`@mouseleave` **and** `@focusin`/`@focusout`.

A tip that only appears on hover does not exist for a keyboard user, and the
trigger is usually an icon-only button whose whole meaning is in the tip.

`@keydown.escape` dismisses it. That is **WCAG 1.4.13**: content shown on hover
must be dismissible without moving the pointer. It is the part everyone forgets,
and it matters most for the case the criterion was written for — a tip that
covers the thing underneath it.

The tip is `pointer-events-none`. It sits over the trigger's edge, and a hover
that lands on the tip instead of the button flickers between states.

## Do not

- **Do not put anything interactive in it.** A link or a button inside a tooltip
  is unreachable for everyone not using a mouse: the tip is not focusable, there
  is no reliable pointer path into it, and it closes on blur. That is why `text`
  is a plain-string prop and not a slot — the component makes it impossible.
- **Do not attach one to a disabled button.** A disabled control fires no pointer
  events in most browsers, so the tip explaining *why* it is disabled never
  appears. Wrap it, or explain the state in the page.
- **Do not use it on touch.** There is no hover. If the interface is
  phone-first, the label belongs on the screen.
- **Do not put a paragraph in it.** Over `max-w-56` it is help text, and help text
  belongs under the field — see [input.md](input.md).
- **Do not repeat the visible label.** A tooltip that says "Save" on a button that
  says "Save" is a second thing to read that says nothing.
