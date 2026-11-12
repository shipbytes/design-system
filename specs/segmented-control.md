# Segmented control

One of a few views or modes in a track, over native radios, or links. Plain
Blade and CSS; the package's `x-ds-glide` directive adds a sliding highlight
when it is on the page.

## Anatomy

```
View                                         ← legend (or sr-only with hideLabel)
┌──────────────────────────────────────────┐
│ ┌────────┐                               │ ← track: sunken, bordered, p-0.5
│ │ ☰ List │   ▥ Board    ▦ Calendar       │ ← segments: label > sr-only radio + icon + words
│ └────────┘                               │ ← highlight: one raised thumb, aria-hidden
└──────────────────────────────────────────┘
```

- **Control, input mode**: `fieldset.min-w-0`, `legend`, then the track.
  Host attributes land on the fieldset, except the model.
- **Control, link mode**: `nav[aria-labelledby]`, a label `span`, the track.
- **Track**: `group/glide`, `data-ds-glide`, `x-data` with no behaviour name,
  `x-ds-glide` (with the bound expression, in input mode). `inline-flex`, or
  `flex w-full` with `fullWidth`.
- **Highlight**: the track's first child, `span[data-ds-glide-highlight]`,
  `hidden` until the directive has placed it. Positioned by the custom
  properties `--ds-glide-x/-y/-w/-h`, which the directive writes.
- **Segment, input mode**: `label[data-ds-glide-item]` holding
  `input.peer.sr-only` (radio), the optional icon and the words (sr-only with
  `iconOnly`).
- **Segment, link mode**: `a[data-ds-glide-item]` with the same children,
  `aria-current="page"` when active, no `href` when disabled.

## How the control reaches the segments

As in chip-group: `name`, `size` and `fullWidth` reach the segments through
`@aware`; the control reads the rendered slot for the `data-ds-segment` markers
to learn its mode (and throws when both are present); each radio carries a
`data-ds-segment-input` hook that the control replaces with the host's
`wire:model*` / `x-model*`.

## States

| State | Treatment |
|---|---|
| resting | `text-fg-body` on the sunken track |
| hover | `text-fg` |
| chosen, no script | the segment draws the thumb itself: `has-checked:bg-surface text-fg shadow-raised` |
| chosen, script placed | the segment's own face goes transparent (`group-data-[ds-glide-ready]/glide:`), the highlight carries it |
| focus-visible | `outline-2 outline-focus-ring` on the segment (`has-focus-visible:` for radios) |
| disabled | `text-fg-muted`, `cursor-not-allowed`, skipped |
| link active | the chosen face, written out, handed to the highlight the same way |

The highlight moves with `motion-safe:transition-[translate,width,height]`,
`duration-popover-enter ease-enter`. Resizes and the first placement jump
(`data-instant`), so only a change of choice animates.

## Sizes

| Size | Height | Text |
|---|---|---|
| sm | 32px | `text-meta` |
| md | 44px below `sm`, 36px from `sm` | `text-body` |
| lg | 44px | `text-body` |

## The glide directive

`x-ds-glide` is shared with toolbar and toggle-group. In **selected mode** (no
modifier) it follows the item that is chosen: `[aria-current]`,
`[aria-checked="true"]`, `[aria-pressed="true"]`, `[aria-selected="true"]`, or
an item holding `input:checked`. It re-measures on `change`, on attribute
changes inside the container, on resize (`ResizeObserver`), and when its
expression changes, at most once per animation frame. Offsets are read from
`offsetLeft`/`offsetTop`, which are physical, so right-to-left needs nothing
special.

## Choices and why

- **Radios, not buttons.** A control that chooses a value should post one and
  take the arrow keys natively. The script adds nothing a user needs.
- **The glide cannot throw.** The rule is that a component needing the script
  fails loudly without it. This one does not need it: the track is a plain
  `x-data` and an unregistered directive is skipped by Alpine, so a missing
  script leaves the CSS face, which is correct. The behaviour suite renders it
  in a host with no script to hold that.
- **No flash.** The highlight is `hidden` until placed, and the segment's own
  face stays until the track is marked ready, in the same frame.
- **The bound expression is watched.** A host setting the value checks a radio
  without an event; watching `x-model`'s (or `$wire.<prop>`'s) value is the
  only way to see it.
- **Sunken track, raised thumb.** In dark mode a surface-on-sunken pair stays
  lighter than its ground; a tinted track did not.
- **`md` is responsive** like `ds::button`'s, so a control beside buttons lines
  up with them at every width.

## Accessibility

| | |
|---|---|
| Role | `group` (fieldset) of radios; or `navigation` of links |
| Name | The legend, or the nav's label; each segment its words |
| State | Native `checked`; `aria-current="page"` on the active link; `aria-disabled` on a disabled link |
| Keyboard | Radios: Tab in, arrows move and choose, skipping disabled. Links: Tab and Enter |
| Focus | Ring on the segment |
| Not colour alone | The raised thumb and its shadow |
| Motion | The slide, `motion-safe` only |

## Do not

- **Do not make the glide carry state.** It is `aria-hidden` decoration; the
  radios are the state.
- **Do not use it for tab panels.** That is `ds::tabs`.
- **Do not put the binding on the segments.** The control carries it.
