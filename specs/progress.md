# Progress

How far along a task is, or that it is running with no known end. A bar by
default, a ring when the space is square.

## Anatomy

```
Uploading report.pdf                64%   ← label (fg, medium) · value (fg-muted)
▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬░░░░░░░░░░░░░░░░   ← fill · track
```

| Part | Token | Why |
|---|---|---|
| Track | `fg/10` | The skeleton's fill, for the skeleton's reason: one translucent value reads on the panel, on the sunken ground and in dark. |
| Fill, `neutral` | `surface-inverse` | The primary button's fill, so the default bar carries the system's main weight rather than a hue. |
| Fill, other tones | `accent` `success` `warning` `danger` | The tone's base colour. |
| Ring | the same tones, as `stroke` | `currentColor`, through `text-*`. |

The track and fill are a shared partial, `resources/views/partials/bar.blade.php`,
which [usage-meter.md](usage-meter.md) draws on too. It carries no role or value;
each component owns its own semantics.

## Variants

`variant`: `bar` | `circle`. `size`: `sm` | `md`.

| | `sm` | `md` |
|---|---|---|
| bar | 4px track, `text-meta` label | 8px track, `text-body` label |
| circle | 20px, label and value beside | 56px, value inside, label beside |

A closed set of literal classes, never `size-{$n}` — see
[icon.md](icon.md#size-and-the-bug-that-shipped).

`tone`: `neutral` (default) | `accent` | `success` | `warning` | `danger`.
Server-side, so not bindable from Alpine — see the next section.

## The value is one custom property

The fill's length is not a class. The root carries `style="--ds-progress: 42"`,
a plain number from 0 to 100, and:

- the bar's fill is `width: calc(var(--ds-progress, 0) * 1%)`;
- the ring's arc is `stroke-dasharray: var(--ds-progress, 0) 100` on a circle of
  radius 100 / 2π, whose circumference is therefore exactly 100. `pathLength`
  would do the same, but older Safari ignores it on a `circle`.

Two reasons. A width class built from a number (`w-[42%]`) is invisible to
Tailwind's scanner, the `size-4.5` trap again. And a property on the ROOT is
something a host can bind from the component tag: `::style="{ '--ds-progress':
done }"` moves the bar with no inner element to reach and no class to rebuild.
The behaviour suite drives this binding; see
[docs](../docs/components/progress.md#driving-it-live).

`value` is clamped in PHP to `0..max`, and `max` that is not a positive number
falls back to 100. The browser never sees an `aria-valuenow` outside its range
or a fill wider than its track.

## States

| State | Shown as | `aria-valuenow` |
|---|---|---|
| determinate | the fill at `value / max` | `value` |
| indeterminate (`value` null) | a sweeping segment, or a spinning arc | **absent** |
| complete | a full fill; nothing else changes | `max` |

No "error" state: a failed task is a `danger` tone plus words beside it, which
the host writes. The component cannot know why it stopped.

## Motion

- The fill's change is `motion-safe:transition-[width]` (the ring:
  `transition-[stroke-dasharray]`) over `duration-slide-enter` and
  `ease-enter`: the fill travels along an edge, which is what `slide` is for.
  With reduced motion the new value is drawn at once.
- Indeterminate bar: `motion-safe:progress-indeterminate`, a utility in
  `theme.css` that sweeps a two-fifths segment across the track in 1.5s. The
  period is a literal rather than a motion token because the tokens are
  transition ceilings (none past 300ms) and this is the length of a loop, like
  Tailwind's own `spin` and `pulse` that the button and the skeleton use.
- Indeterminate ring: `motion-safe:animate-spin`.
- **Reduced motion is an explicit branch, not just a stopped animation.** The
  bar becomes a static striped track and the ring a dashed circle. A frozen
  partial bar would read as a real value, which is the one thing an
  indeterminate indicator must never say.

## Accessibility

| | |
|---|---|
| Role | `progressbar`, on the root element |
| Name | `label`, as `aria-label`, shown or not (`hideLabel`). Or the host's `aria-labelledby`, which then replaces it. |
| Value | `aria-valuemin="0"`, `aria-valuemax` = `max`, `aria-valuenow` = the clamped value; omitted when indeterminate |
| Keyboard | none. It is not focusable and not interactive. |
| Focus | never takes or moves focus |

- **No name throws.** A progress bar with no `label` and no
  `aria-labelledby` is refused at render time. "Progress bar, 40 percent" with
  nothing to say what is 40 percent done is useless, and on a page with two it
  cannot be told apart. It is a template mistake, never data, so it fails loud
  like `empty-state`'s dropped slot.
- The visible label and value sit inside the role and are `aria-hidden`: the
  name and value attributes already say both, and progressbar's children are
  presentational in ARIA anyway.
- The slot's wording ("3 of 12 files") is visual. The announced value stays
  the percentage, which is correct; pass `aria-valuetext` when the words matter
  more than the number.
- It does not announce updates. A progressbar is read when someone reaches it;
  announcing every tick would drown a screen reader. The end of a task belongs
  in a toast or a live region the host controls.

## Do not

- **Do not use it for an allowance.** That is a measurement:
  [usage-meter.md](usage-meter.md), `role="meter"`.
- **Do not bind `::value`, `::tone` or `::size`.** They are PHP props, read once.
  Bind `--ds-progress`.
- **Do not build the width as a class.** `w-[{{ $p }}%]` is the scanner trap.
- **Do not put content in the ring beyond the value.** An icon or a second
  number in a 56px circle is unreadable at the size it is drawn.
- **Do not animate an indeterminate indicator without a reduced-motion branch.**
  The stripes and the dashes are part of the component, not a fallback.
