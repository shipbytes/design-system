# Gauge

One reading in a known range, drawn as an arc, with bands that say what the
reading means.

## Anatomy

```
        ╭──────╮            ← track: the arc's own path, text-fg/10
      ╭─┘ 76%  └╮           ← value: the arc again, dashed to the reading, in the band's tone
      │ Warning │           ← the band's word (semi md/lg; otherwise under the label)
   0%              100%     ← range (semi md/lg)
          Memory            ← label, aria-hidden
```

| Part | Token | Why |
|---|---|---|
| Track | `text-fg/10` | The same quiet track as progress. |
| Value arc | the band's tone, or `tone` | `currentColor`. |
| Stroke | 12 / 9 / 8 in a 100-unit box (sm / md / lg) | Thicker when small, so the arc stays readable at 48px. |
| Box | semi `w-20` / `w-36` / `w-48`; ring `size-12` / `size-24` / `size-32` | |
| Value | `text-meta` medium / `text-title` / `text-display`, `tabular-nums`, `fg` | |
| Word, range, label | `text-meta` `fg-muted`; label `text-body` medium `fg` | |

## Geometry

`Support\Chart::arc()` draws the track: centre (50, 50), radius 42, angles in
degrees clockwise from twelve o'clock. Semi is −90° to 90° in a `0 0 100 56`
box; ring is a full turn, drawn as two half arcs because a single arc from a
point to itself draws nothing. The value is the same path with
`stroke-dasharray="L·fraction L+1"`, its length computed in PHP rather than
left to `pathLength`, which older Safari ignores. No animation: the reading is
drawn where it is.

## Bands

`thresholds` is a list of `['at' => n, 'tone' => …, 'label' => …]`, sorted by
`at`. The value's band is the highest `at` it has reached. The band sets the
arc's tone and its word: the `label`, or the tone's word (`success` "Good",
`warning` "Warning", `danger` "Critical"; neutral and accent have none). A
value under the lowest `at` has no band and is neutral.

The word is on screen at every size, so colour is never the only signal (WCAG
1.4.1): inside a semi gauge under the value at md and lg, where its open
bottom has room; under the label for a ring (whose hole is narrower than
"Overloaded" at md) and at sm.

## Values

- `aria-valuemin`, `aria-valuemax` as given (swapped if reversed).
- `aria-valuenow` clamped into the range, as a meter's must be; the arc is
  clamped the same way.
- `aria-valuetext`: the printed value, then past the range "above the maximum
  of 100%" or "below the minimum of 0%", then the band's word. "120%, above
  the maximum of 100%, Critical". The printed value is never clamped.
- `null` value: no `aria-valuenow`, `aria-valuetext="No reading"`, an empty
  track and a dash.

## Meter, progress or usage meter

`role="meter"`, like the usage meter: a measurement, not a task. The three
divide the work by what the number IS:

| | Is | Reads |
|---|---|---|
| progress | a task heading for done | "64%" |
| usage-meter | an allowance being spent, in its units | "42 of 50 credits" |
| gauge | a level that rises and falls | "76%, Warning" |

## Accessibility

| | |
|---|---|
| Role | `meter`, on the root |
| Name | `label`, as `aria-label`, or the host's `aria-labelledby` |
| Value | see Values |
| Keyboard | none; not focusable |
| Focus | never takes or moves focus |

The SVG, the value, the word, the range and the visible label are
`aria-hidden`: the meter carries all of them, and they would be read twice. A
gauge without a name throws, as progress does.

## Do not

- **Do not let the arc overflow.** Past the range it is full or empty, and the
  words say the rest.
- **Do not colour by tone without a word.** If a band has no word that means
  something, it should not have a colour that pretends to.
- **Do not animate the arc.** Chart geometry animation is out of scope; a
  dashboard that redraws should not swing its dials.
