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

| `size` | Width | When |
|---|---|---|
| `sm` | 24rem / 384px | A filter list. |
| `md` | 28rem / 448px | **Default.** |
| `lg` | 32rem / 512px | The widest the source dashboard ever used. |
| `xl` | 36rem / 576px | A record shown beside the list it came from. |
| `2xl` | 42rem / 672px | A detail pane with a table in it. |
| `full` | `calc(100vw - 3rem)` | A reader that wants the room but is still a drawer. |

Each is paired with `w-full`, so a drawer is full width on a phone and a panel on
a desktop, at every size.

`xl` and `2xl` are for the case the dashboard did not have: a drawer used to
*read* a record rather than to filter one. Filters are a narrow list and always
were.

**`full` is `calc(100vw - 3rem)`, not `max-w-none`, and the sliver is the
point.** This container is `fixed inset-0` with no padding of its own, so `none`
would run the panel edge to edge with nothing of the page visible behind it — and
a panel covering everything is a screen, not a drawer. The strip of page still
showing is what says the thing you came from is still there and one click away.
That is the whole difference between the two, and a full-bleed drawer would
quietly become a worse page.

Position, border side and both transition endpoints are separate from this and
come from the `side` map. The transitions are `translate-x-full` /
`-translate-x-full`, which are relative to the element's own width, so they hold
at every size without a second lookup.

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


### `open` must be assignable

`open` is a **reference**, not a condition. The component does not just read it —
the close button, the backdrop and Escape all write to it:

```blade
@click="{{ $open }} = false"
```

```blade
{{-- Broken: opens, and then nothing closes it --}}
<x-ds::drawer open="side === 'right'">

{{-- Works --}}
<div x-data="{ show: { right: false, left: false } }">
    <x-ds::drawer open="show.right">
</div>
```

The broken form compiles to `side === 'right' = false`. Reading the expression is fine,
so the drawer **opens correctly**, and then every dismiss path silently does
nothing. HTTP 200, a clean server log, and one `Uncaught SyntaxError: Invalid
left-hand side in assignment` in a console nobody had open. It cost a consumer
half an hour, which is why it is a render-time exception now
(`Support\OpenState`) rather than a docblock alone.

## Do not

- **Do not open a drawer from a drawer.** Same rule as the modal, same reason:
  two blocking layers give the reader no way to know what Escape means.
- **Do not use one for navigation above `lg`.** That is the sidebar. A drawer that
  hides primary navigation behind a button on a desktop hides it for no reason.
- **Do not put a form's only submit in the scrolling body.** That is what the
  footer slot is for.
- **Do not drive several drawers from one variable.** `open="side === 'right'"`
  is the natural way to write that and it is not assignable: the drawer opens
  and then the ✕, the backdrop and Escape all do nothing. One property per
  panel — see above.
- **Do not animate `width`.** Transform only — animating width relayouts the
  contents on every frame, and a filter list visibly reflows as it opens.
