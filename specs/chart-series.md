# Chart series

Eight tokens — `series-1` … `series-8` — and they do **one** job: say which
series a mark belongs to. They are the identity channel of a chart and they are
not available for anything else.

## Why the semantic tokens could not do it

The system has six hues with meaning attached: `accent` is a link, `success` is
a gain, `warning` is a caution, `danger` is a loss, `neutral` is the absence of
a charge. A four-series chart drawn from that set has to borrow from it, and two
of the five are alarm colours.

That borrow is not a cosmetic compromise. Drawing a series called *Issues* in
`danger` red tells the reader the line is bad news; drawing *Weighments* in
`success` green tells them it is good. Neither is something the data said. The
chart ends up asserting a judgement the query never made, and the reader has no
way to know the colour is arbitrary.

So the identity channel needs hues that mean **nothing** — which is exactly what
these eight are.

## The order is the safety property

Slots are assigned **in sequence and never cycled**. A chart with three series
uses 1, 2, 3 — not 1, 4, 7 because they look nicer together, and not 1, 2, 3
re-shuffled per chart.

| Slot | Hue | Light | Dark |
|---|---|---|---|
| 1 | blue | `#2a78d6` | `#3987e5` |
| 2 | orange | `#eb6834` | `#d95926` |
| 3 | aqua | `#1baf7a` | `#199e70` |
| 4 | yellow | `#eda100` | `#c98500` |
| 5 | magenta | `#e87ba4` | `#d55181` |
| 6 | green | `#008300` | `#008300` |
| 7 | violet | `#4a3aa7` | `#9085e9` |
| 8 | red | `#e34948` | `#e66767` |

The ordering is measured, not chosen. Adjacent pairs are separated under
simulated protanopia and deuteranopia (Machado–Oliveira–Fernandes 2009 at
severity 1.0), scored as Euclidean distance in OKLab ×100:

- worst adjacent CVD ΔE **9.1 light / 8.4 dark** (target ≥ 8)
- worst adjacent normal-vision ΔE **22.9 light / 19.8 dark** (floor ≥ 15)
- every slot inside the per-mode lightness band, every slot over the chroma floor

**Re-stepping one slot by eye breaks the set.** The values are a validated
instance; if a slot has to move, the whole ordering is re-measured, not patched.

Dark is **selected, not flipped**. The dark column is the same eight hues
stepped for a dark surface and validated against it as its own set. An
algorithmic lighten of the light column does not land inside the dark band.

## The one thing you must ship with them

On a light surface, `series-3` (2.82:1) and `series-4` (2.17:1) sit below the
3:1 a non-text mark normally needs. That is a deliberate trade — pushing them
darker to clear 3:1 collapses the CVD separation into the warn band, and
colour-vision separation is the property that cannot be recovered any other way.

The relief is that **identity is never carried by colour alone**:

- a legend is always present from two series up, and
- at four series or fewer, each line is **also labelled directly** at its end.

A chart that drops the direct labels has broken the contract these tokens were
validated under. It is not a nicety.

## Series count

Eight is the ceiling, and it is a real one. A ninth series is never a generated
hue — fold the tail into an *Other*, facet into small multiples, or ask a
narrower question.

For charts where **any** two marks can end up side by side — scatter, bubble,
choropleth, small multiples — only the **first three slots** are validated.
Past three in those forms, facet; do not reach for slot four.

## Do not

- **Do not key a slot on a business value.** `state === 'OVERDUE' ? series-8 :
  series-1` is a palette keyed on domain meaning — the same defect as a role
  name in a conditional, and harder to see because it looks like design. The
  series' *position* picks the slot; nothing else may.
- **Do not use a series token for UI chrome.** No borders, no badges, no icons,
  no text. If it is not a mark in a plot, it is not one of these.
- **Do not use a status colour as "series 5".** `success` / `warning` / `danger`
  stay reserved, and when a chart genuinely encodes state it ships an icon and a
  label with the colour, never the colour alone.
- **Do not re-colour survivors when a filter changes the series count.** Colour
  follows the entity, not its rank — a series keeps its slot when its neighbours
  are hidden, or the chart repaints itself into a different story.
