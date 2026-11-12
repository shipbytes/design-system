# Usage meter

An allowance, and how much of it is used. Built on the progress bar's track and
fill, with its own semantics: a measurement, not a task.

## Anatomy

```
AI credits                       42 of 50 credits   ← label · amount (danger when over)
▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬░░░░   ← the shared bar, role=meter
(!) 240 over the limit                              ← only when over
● Files 4.5   ● Backups 2   ● Logs 1.25             ← legend, only with segments
```

| Part | Token | Why |
|---|---|---|
| Track and fill | `resources/views/partials/bar.blade.php` | The same partial [progress.md](progress.md) draws: one track, one tone map, one place to change them. |
| Amount | `fg-muted`, or `danger` + medium when over | |
| Over-limit line | `danger`, `exclamation-circle` mini | Words, not colour alone (WCAG 1.4.1). |
| Legend dot | the segment's tone | Decorative; the label beside it is the information. |

## Meter, not progressbar

`role="meter"`. ARIA's progressbar is a task moving towards completion; a meter
is "a scalar measurement within a known range", which is what an allowance is.
The difference reaches the reader: a progressbar implies something is
happening and will finish, and announcing "credits, progress bar, 84 percent"
suggests the credits are loading.

Screen readers that predate `meter` announce it as a generic value or as a
progress bar; the `aria-valuetext` below reads correctly either way, which is
why it is always set.

A native `meter` element was the alternative. It cannot draw a stacked bar,
and its appearance differs per browser and resists styling, so the system
would have shipped two looks of the same thing.

## Values

- `aria-valuemin="0"`, `aria-valuemax` = `limit`.
- `aria-valuenow` = `used`, **capped at `limit`**: a meter's value must lie
  inside its range, and an out-of-range one is clamped by the browser anyway,
  silently.
- `aria-valuetext` = "42 of 50 credits", or "…, 240 credits over the limit".
  This is what is read, so the overage is never lost to the cap.
- Attributes use bare numbers (`1200`); the visible text uses separators
  (`1,200`). `aria-valuenow="1,200"` is not a number.

## Tone

Derived, not passed: below `warnAt` (80) neutral, from `warnAt` warning, from
`dangerAt` (100) danger. Exactly at the limit is danger, because the next use
is refused. Over is danger and adds the line of words.

With `segments` each segment carries its own tone, and the thresholds colour
only the over-limit text. A segment's colour says what it IS; recolouring a
"Backups" segment amber because the disk is nearly full would make the legend
lie.

## Overflow

The bar never overflows its track. One bar: full. Segments: scaled together by
`max(used, limit, sum)`, so they fill the track exactly and keep their
proportions. Every width is an inline `style`, never a class (the scanner trap
in CLAUDE.md).

A `limit` of 0 is data, not a mistake: it renders, nothing used is empty and
anything used is over. It does not throw.

## Accessibility

| | |
|---|---|
| Role | `meter`, on the bar element |
| Name | `label`, as `aria-label` |
| Value | see Values |
| Keyboard | none; not focusable |
| Focus | never takes or moves focus |

The visible label and amount are `aria-hidden` (the meter already carries
both); the over-limit line and the legend are not, because they are content.

## Do not

- **Do not use it for a task in flight.** That is a progressbar.
- **Do not let the thresholds recolour segments.**
- **Do not pass a percentage.** Units are what the reader understands; the
  arithmetic is the component's.
- **Do not hide the over-limit line.** It is the state; the red is decoration.
