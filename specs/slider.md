# Slider

A number, or a range of two, picked on a track: a volume, a price filter, an
opacity. Native `<input type="range">` underneath, one or two of them.

## Anatomy

```
Price                                  $20 – $80   ← label; showValue="inline"
  ○━━━━━━━━━━━━━━━━━━━━━○                         ← track, fill, two thumbs
  ·            ·            ·                       ← marks: ticks …
 $0           $50          $100                     ← … and their labels
Prices include tax.                                 ← help, or the error
```

- **The input** is a native range input, transparent: the browser owns its
  keyboard, its pointer and touch handling and what it posts. With `range`
  there are two, stacked on one track; the inputs let the pointer through and
  only their thumbs take it.
- **The thumb** is the input's own, drawn in `theme.css` (a pseudo-element is
  out of reach of a utility class): a 24px target at every size, of which the
  visible disc is 20px (`md`) or 16px (`sm`), the rest a transparent border.
- **The track and the fill** are the view's elements under the input, inset
  by half a thumb each side, which is where a native thumb's centre stops, so
  the fill ends under the thumbs. The fill's ends come from
  `--ds-slider-low` and `--ds-slider-high`, ratios from 0 to 1, written by
  PHP into the style and kept in step by the script.
- **The readout** (`showValue`): `inline`, beside the label; `tooltip`, a
  bubble over the thumb while it is dragged or has keyboard focus, shown by
  the script only.
- **Marks**: value => label, or a list of numbers labelled with their
  formatted values; a dot on the track and a label under it. Outside
  `min`..`max` a mark is dropped.
- `size`: `md` (a field's height, 44px below `sm` and 36px above, so a slider
  lines up with the fields beside it) or `sm` (32px).

## Histogram

```
Price per night                        $50 – $200
      ▂▃▇█▆▄▃▂▂▁ ▁ ▁▁ ▁  ▁                         ← bars, lit inside the range
  ○━━━━━━━━━━━━━○                                   ← the track as above
19 stays                                            ← the count
[Minimum price  50 −+] [Maximum price  200 −+]      ← inputs
```

- **`histogram`** is a count per bucket, the buckets equal widths from `min`
  to `max`; with **`buckets`** it is raw values, counted into that many in
  PHP (a value on `max` goes in the last; outside `min`..`max`, in none).
  Plain elements in a flex row inset half a thumb each side like the track,
  so each bar spans exactly the values it counts; heights against the tallest
  bucket, inline (data, not a class).
- **A bucket is in the range when its middle is**, inclusive. The lit bars
  (`data-ds-in`, which `data-ds-in:` reads) and the counted ones are the same
  set, so the count never disagrees with the picture. PHP lights them first;
  the script moves the attribute, never a class, and re-renders nothing.
- **The count**, `countLabel` with `:count` formatted as `format`'s locale and
  grouping and no decimals; "one|other" picks by the number (one form for 1).
  Shown at once in a line that is `aria-hidden`, and written half a second
  after the last move into a `role=status` region, so it is said once, not
  once per step.
- **`inputs`**: a `ds::number-field` per thumb (no name: the thumbs post),
  labelled `lowLabel` and `highLabel` (or `label`), `hidden` until the script
  binds it (`dsReady`). Each follows its thumb; while typed in it moves the
  thumb only once the number is one the thumb can take (its `min` and `max`
  are rewritten to the other thumb less `minGap`, so the browser's own check
  agrees); leaving it moves the thumb anyway, held, and writes back what the
  thumb took. Works on a single slider too.
- **Without it the slider is byte for byte as before**: SliderUnchangedTest
  holds a fixture rendered from the view before the prop existed. Every new
  block starts at column 0, where Blade leaves nothing behind when it is off.

## States

| State | Treatment |
|---|---|
| default | Fill `surface-inverse` (the primary button's fill), track `fg/10`, thumb `surface` with a `fg-muted` edge |
| hover | Thumb edge `fg` |
| focus-visible | Thumb edge `fg` and a 2px `focus-ring` ring 2px out from the disc |
| dragging | `grabbing` cursor; with `showValue="tooltip"` the bubble shows |
| error | Fill `danger`, the message under it, `aria-invalid` on each input |
| disabled | Inputs disabled, fill `fg-subtle`, thumb `surface-subtle` |
| forced colours | Thumb and fill in `Highlight`, the track outlined in `CanvasText` |

## Values

- `value` is snapped as the browser snaps it, inside `min`..`max` and onto
  `step` counted from `min`, so the fill PHP draws is where the thumb is.
  No value is the midpoint, as a native range input does; with `range`, no
  value is `[min, max]`. Two values in the wrong order are sorted.
- `range` takes `[low, high]` or `['low' => …, 'high' => …]`, so `old('price')`
  round-trips.
- **Posts** `name`, or with `range` `name[low]` and `name[high]` (one array in
  PHP), or `lowName` / `highName`.
- **Format**: `format` takes `Support\Chart::format()`'s keys (`prefix`,
  `suffix`, `decimals`, `grouping`, `compact`, `locale`). PHP writes every
  readout and `aria-valuetext`; the script formats in the browser with the
  same options through `shared/format.js`, so the two agree.

## Behaviour (`dsSlider`)

| Input | Result |
|---|---|
| Arrows, Home, End, PageUp, PageDown, drag, tap | The browser's own |
| Any change | Fill, `aria-valuetext`, readouts follow |
| `range`: a thumb pushed into the other | Stops `minGap` short of it (0 by default: they may meet, never cross) |
| `range`: a press on the track or the bars | The nearer thumb jumps there and takes focus; `input` and `change`. A press elsewhere in the component (a label, a number field) does not |
| `histogram`: any change | Bars in the range lit, the count written, and said 500 ms later |
| `range`: two thumbs at one end | The low thumb is on top past halfway, so they can be pulled apart |
| Host sets the value (`x-model`, `wire:model`) | Drawn, clamped and stepped as the inputs take it; handed back if it differed |

## Accessibility

| | |
|---|---|
| Role | Native `slider` (range input), one per thumb |
| Name | Single: the `label`, a `<label for>`. Range: `lowLabel` and `highLabel` on each thumb (required, it throws without them), the group named by the `label` |
| Value | `aria-valuetext`: the formatted value, "$20" |
| Keyboard | The browser's: arrows step, PageUp / PageDown jump, Home / End go to the bounds. In a right-to-left page the arrows follow the direction |
| Focus | A ring on the thumb, both themes; nothing moves focus but the track press in `range` |
| Target | 24px square thumb at every size (WCAG 2.5.8) |
| Readouts | The inline readout is plain text, not a live region, and the tooltip is `aria-hidden`: each thumb already speaks its value |
| Marks | Their labels are `aria-hidden`; the value text says where a thumb is |
| Histogram | `aria-hidden`; the count line is `aria-hidden` too, and its words are in a polite `role=status` |
| Inputs | Native number fields named like their thumbs |

## Without the script

The single slider is a complete native control: it moves, posts and is
announced. The fill and the readout stay where PHP drew them until the page
is reloaded. The range pair posts two valid values, but the two thumbs CAN
cross (the low one ending above the high one): nothing stops a native input
passing another. A histogram stays lit and counted as the server drew it;
`inputs` stay hidden. Validate the order on the server, which you must anyway. The
`x-data` throws its named error, so a page that forgot the include is told.

## Do not

- **Do not use it for a precise number.** A slider is for "about this much";
  an exact quantity is a [number field](number-field.md).
- **Do not use it for a range wider than its width in pixels.** Steps under a
  pixel cannot be dragged to; give a field beside it, or a coarser step.
- **Do not rely on the thumbs not crossing.** Without the script they can.
- **Do not put the meaning only in the marks.** Their labels are visual; the
  formatted value is what is announced.
