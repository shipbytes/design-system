# Stat tile

Roughly fifteen instances across the user dashboard and admin, with the count-up
animation duplicated verbatim five times in one file.

## Anatomy

```
┌────────────────────────────┐
│ My Resumes                 │  label   · body, medium, fg-muted
│ 12                         │  value   · display, tabular-nums
│ [+18%] from last week      │  delta   · chip + caption, body
└────────────────────────────┘
   radius-control · border-strong · surface · p-4
```

`border-strong` rather than `border`: a stat tile has to read as a raised object
in a row of them, and the default hairline is too quiet for that at this size.

## Value

`tabular-nums`, always. Two reasons, and the second is the one people forget:
digits line up across the tiles in a row, **and** the count-up does not change
width as it climbs. Proportional digits make the number jitter while it counts.

The value is rendered server-side as the final number. If JavaScript never runs,
the tile is still correct — the animation is an enhancement, not the source.

## Delta

| State | Treatment |
|---|---|
| positive | `success-tint` chip, `+N%` |
| negative | `danger-tint` chip, `−N%` |
| none | no chip — the caption takes the line instead |

**A missing comparison is not a zero.** "No change since last week" and "nothing
to compare against yet" are different statements, and a `0%` chip asserts the
first. When there is no delta, pass a caption and the chip is omitted entirely.

The app used `lime-400/20` and `pink-400/20` here — two colour families that
appear nowhere else in the system for the jobs `success` and `danger` already do.

## Count-up

Counts from zero over 600ms on first paint.

**Skipped when the reader has asked for reduced motion.** The hand-written
version animated regardless. This is the class of bug that survives review
indefinitely because it only affects people who are not in the room.

Implemented as one vanilla script pushed once per page, not per tile, and not in
Alpine — the component is presentation only, and a count-up should not decide
which JavaScript framework the host application runs. React and Vue
implementations do this their own way; the contract is only *what* happens.

## Interactive tiles

A tile that drills down is an `<a>` and gets a hover state: border to `fg/20`,
plus `shadow-raised`, and the label warms from `fg-muted` to `fg-body`. A tile
that goes nowhere is a `<div>` and has no hover state at all — a hover
affordance on something unclickable is a lie.

## Do not

- Put a unit inside the value (`12 resumes`). The label carries the noun; the
  value carries the number. Mixing them breaks `tabular-nums` alignment and
  makes the row impossible to scan.
- Use a stat tile for a single number with no comparison and no drill-down.
  That is a sentence, not a tile.
