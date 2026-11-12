# Icon swap

Two icons stacked in one cell that turn into each other, or three bars that fold
into a cross. Decorative, CSS only, driven by the state of the element around it.

## Anatomy

```
┌──────┐
│ ☰/✕  │   ← span[aria-hidden][data-ds-icon-swap][data-preset].inline-grid
└──────┘     two faces in one cell (ds::icon), or three bars (menu)
```

- **Root**: `relative inline-grid place-items-center`, `size-4|5|6` with a
  matching font size (the menu bars are sized in em off it). It holds the state
  as one custom property, `--ds-on`, 0 or 1.
- **Faces**: two ds::icon in `col-start-1 row-start-1`; every transform and the
  opacity are `calc()` on `var(--ds-on)`, so one selector drives every face.
- **Menu bars**: three `absolute` spans, `0.6875em × 0.0625em`, `0.21875em`
  apart: the 16.5 / 1.5 / 5.25 of 24 of `bars-3`. Centred with `top-1/2` and a
  `-50%` translate, which also makes them paint at the stroke weight of the
  outline icons rather than snapping to whole device pixels.

## State

`--ds-on` is 1 when an ancestor matches any of `[aria-expanded=true]`,
`[aria-pressed=true]`, `[aria-checked=true]`, `[data-state=open|on|checked|active]`
(the `in-[…]` variant), or when `active` is set. Nothing else; no script.

## Presets

| Preset | At rest → on |
|---|---|
| `crossfade` (default) | `from` fades and shrinks to 0.5; `to` grows in from 0.5 |
| `rotate` | The same, turning a quarter, scale 0.75. With no `to`, `from` turns 180° alone |
| `flip` | Both faces `backface-hidden`, turned on Y by 180°, under `perspective-near` |
| `menu` | Outer bars move to the middle and turn ±45°; the middle bar fades and narrows |

`motion-safe:` transitions on opacity, scale, rotate, translate and transform,
`duration-popover-enter ease-enter`. With reduced motion the state still
changes, at once.

## Props

`from` (required, except `menu`), `to` (required, except `menu` and a lone
`rotate`), `active`, `preset`, `size` (`4`, `5` default, `6`), `variant` (as
ds::icon). A missing required icon throws when the view renders.

## Accessibility

| | |
|---|---|
| Role | none: `aria-hidden="true"` |
| Name, state | The parent control's: its name, and `aria-expanded` / `aria-pressed` |
| Motion | `motion-safe:` only |

## Do not

- **Do not morph paths.** Two honest pictures, or spans.
- **Do not give it a name.** The button has one.
- **Do not place it inside a stateful region's content.** The selector matches
  any ancestor; inside an expanded panel it turns too.
- **Do not rely on it to show the state.** The parent's attribute is the state.
