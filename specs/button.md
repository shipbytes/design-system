# Button

The dashboard currently writes this button **eight different ways** — differing
in padding, weight, size, whether it carries a shadow, and which of
`transition` / `transition-colors` / `transition-all` it uses. The intent was
always the same. This is that intent, written once.

## Anatomy

```
┌─────────────────────────────────┐
│  [icon]  Label  [icon]          │   gap: 0.5rem
└─────────────────────────────────┘
   ↑ height = control token
   ↑ radius = radius-control (0.5rem)
   ↑ border: 1px, always present — transparent on filled variants, so
     swapping variant never changes the box size
```

## Variants

| Variant | Fill | Label | Border | Use |
|---|---|---|---|---|
| `primary` | `surface-inverse` | `on-inverse` | transparent | The one action the page is for |
| `secondary` | `surface` | `fg` | `border` + `shadow-raised` | Everything else with a box |
| `ghost` | transparent | `fg-body` | transparent | Tertiary, and toolbar icon buttons |
| `danger` | `danger` | `on-danger` | transparent | Destructive, confirmed |

**One primary per view.** The studio header has five actions and exactly one is
filled. If two things look primary, neither is.

## Sizes

| Size | Height | Padding | Type | Use |
|---|---|---|---|---|
| `sm` | `control-sm` (32px) | `0 0.625rem` | `meta` | Inside table rows and card footers |
| `md` | `control-md` (36px) | `0 0.6875rem` | `body` | Default, at `sm` breakpoint and up |
| `lg` | `control-lg` (44px) | `0 0.8125rem` | `body-touch` | Below `sm`, and the studio action bar |

`md` and `lg` are the *same component at two breakpoints*, not two components.
That is what gets a 44px touch target on phones without a mobile variant.

Padding is `calc(<step> - 1px)` to absorb the 1px border, which is why the
numbers look odd. Keep it — it is what makes `primary` and `secondary` line up.

## States

`default` · `hover` · `active` · `focus-visible` · `disabled` · `loading`

- **hover** lightens `primary` (`zinc-950 → zinc-800`), tints `ghost`
  (`fg/5`), and strengthens `secondary`'s border.
- **focus-visible** only — never bare `:focus`. Two-pixel `focus-ring`, offset
  by two so it reads on any surface. Never remove it without replacing it.
- **disabled** is `opacity: 0.5` (0.4 in dark) plus `cursor: not-allowed`, and
  must also set the `disabled` attribute — opacity alone still submits forms.
- **loading** keeps the button's width and its full contrast, swaps the leading
  icon for a spinner, and sets `aria-busy="true"`. It also carries the `disabled`
  attribute — a second submit while the first is in flight is the bug this state
  exists to prevent — but it must NOT pick up the disabled styling: faded, it
  reads as "refused" rather than "working", and those mean opposite things. It
  does not replace the label with "Loading…", which makes the button jump.

## Icon-only

Square: width equals the size's height. Always needs an accessible name —
`aria-label`, or visible text in a tooltip. The mobile FAB is this component at
`radius-full`, not a separate thing.

## Accessibility

- Real `<button>`, or `<a>` when it navigates. Never a `<div>` with a click
  handler.
- `type="button"` unless it genuinely submits — the default is `submit`, which
  is how a "Cancel" button ends up saving the form.
- Destructive actions confirm in a modal; the button itself is not the guard.

## Do not

- Reach for `<x-primary-button>`. It is untouched Breeze — grey, uppercase,
  letter-spaced — and clashes with everything here. It gets deleted.
- Add a shadow to `primary`. Filled buttons on this system are flat; only
  `secondary` carries `shadow-raised`, to lift it off the card.
