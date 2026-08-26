# Drawer

A panel that slides in from the edge and blocks the page behind it. The third
member of the family that [modal.md](modal.md) and [sheet.md](sheet.md) belong to.

## Which of the three

All three block the page. The difference is what they are *for*:

| | Enters from | For |
|---|---|---|
| **Modal** | the centre | a question — one decision, then gone |
| **Drawer** | the left or right edge | a workspace — filters, a detail panel, settings |
| **Sheet** | the bottom edge | the mobile form of either |

The tell is **how long the reader stays**. A modal is answered; a drawer is
worked in, and then closed. A drawer holding one yes/no question wastes the whole
height of the screen on it; a modal holding twelve filters scrolls.

All three share `z-50`, and none of them may be open at the same time.

## Anatomy

```
                    ┌──────────────────┐
                    │ Filters       ✕  │  ← header, fixed
                    ├──────────────────┤
      scrim         │                  │
                    │  body scrolls    │
                    │                  │
                    ├──────────────────┤
                    │   [ Apply ]      │  ← footer, fixed
                    └──────────────────┘
```

`side`: `right` (default) | `left`. Right for filters and detail — it is where
the reader's attention already is after they clicked something on the right.
Left for navigation, because that is where navigation lives.

`size`: `sm` | `md` | `lg` — `max-w-sm` through `max-w-lg`, plus `w-full`, so a
drawer is full width on a phone and a panel on a desktop.

Position, border side and both transition endpoints come from **one map**. Split
across three lookups, a `side` change silently keeps the previous slide
direction, and the panel flies in from the wrong edge.

## Motion

300ms in, 200ms out — slower than the modal's 200/150.

A panel travelling the full height of the screen at the modal's speed reads as a
flinch. Slower out than in is the same rule the sheet uses: leaving should feel
like the thing receding, not vanishing.

## The body scrolls, not the panel

The header keeps naming the dialog and the footer keeps its actions reachable.
A drawer is usually a long list of filters, which is exactly the case where
losing the Apply button off the bottom matters most.

## Accessibility

Identical to the modal's contract, and implemented the same way:

- `role="dialog"` + `aria-modal="true"`, named by `aria-labelledby` pointing at
  the visible title.
- Focus moves into the panel on open and **returns to the trigger** on close.
- Tab is trapped, both directions.
- Escape closes when `dismissible`.
- The page behind does not scroll.

**The focus trap is written out longhand rather than using `@alpinejs/focus`,**
for the reason given in [modal.md](modal.md#why-the-focus-trap-is-written-out-longhand):
Alpine skips a directive it has no handler for, so a missing plugin makes
`x-trap` nothing at all while `aria-modal="true"` keeps claiming otherwise.

### Why it is duplicated rather than shared

The trap and the focus management are byte-identical to the modal's, and they are
copied rather than extracted.

A Blade partial shared between the two would be included into both components'
attribute context, and the thing being shared is a *string of JavaScript* built
in `@php` — the include would have to pass the open-state expression through,
which is exactly the multi-line attribute-bag shape that makes Blade emit the tag
as literal text. Two copies of ten lines, each next to the component it belongs
to, is cheaper than that indirection. **If a third overlay appears, extract
then.**

## Do not

- **Do not open a drawer from a drawer.** Same rule as the modal, same reason:
  two blocking layers give the reader no way to know what Escape means.
- **Do not use one for navigation above `lg`.** That is the sidebar. A drawer that
  hides primary navigation behind a button on a desktop hides it for no reason.
- **Do not put a form's only submit in the scrolling body.** That is what the
  footer slot is for.
- **Do not animate `width`.** Transform only — animating width relayouts the
  contents on every frame, and a filter list visibly reflows as it opens.
