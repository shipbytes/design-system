# Date picker

A calendar popover for one date or a range.

## The native field already exists

`<x-ds::input type="date" />` gives the platform's own date field, and on a
phone that is the better control: the OS picker is a wheel the reader already
knows, it is localised, and it costs nothing.

**This component exists because the native popup cannot be styled** — the same
argument as [select.md](select.md), and it deserves the same restraint.

## Dates are strings, never `Date` objects

This is the rule the whole component is built on.

`new Date('2026-03-29')` parses as **UTC midnight**. In any timezone west of
Greenwich that instant is the 28th, so a picker built on `Date` objects selects
the day before the one that was clicked — for some users, some of the time,
depending on where they are. It is the classic date-picker bug and it is
invisible to whoever wrote it.

Every value here is `Y-m-d`, compared as a string. String comparison on `Y-m-d`
is both correct and correctly *ordered*, which is what the range logic needs.
`Date.UTC` appears only for calendar arithmetic — how many days in a month, what
weekday the first falls on — where the timezone is irrelevant.

## Anatomy

```
Reporting period
┌────────────────────────────────┐
│ Sep 7, 2026 – Sep 19, 2026  📅 │
└────────────────────────────────┘
      ‹   September 2026   ›
      Mo Tu We Th Fr Sa Su
             1  2  3  4  5  6
       7  8  9 10 11 12 13        ← 7 and 19 inverse; between them, accent-wash
      …
      Clear                 Done
```

| Part | Token | Why |
|---|---|---|
| Popover | `surface` + `shadow-float` | It hovers over the page; it does not block it. |
| Selected day | `surface-inverse` | The same "this one" mark the primary button uses. |
| In range | `accent-wash` | A wash, not a tint: it covers up to five weeks of cells. |
| Today | `fg` + semibold | Weight, not colour — colour would compete with the selection. |

## What posts

`Y-m-d`, through hidden inputs, which is what a database column and
`Carbon::parse` both want.

A range posts **two fields** — `{name}_start` and `{name}_end`. Two dates are two
values, and squeezing them into one string means every consumer writing the same
parser and getting the separator wrong.

Rendered by PHP and bound for Alpine, so the field carries its value before the
JS runs.

## Range behaviour

First click sets the start and clears the end. Second click sets the end and
closes.

**A second click *before* the start re-opens the range from there** rather than
producing a backwards range the reader then has to undo. That is the one
interaction people get wrong when they misread the calendar, and it should cost
them nothing.

While a range is half-made, hovering previews it — `hovered` feeds the same
`isBetween` test the committed range uses, so the preview cannot disagree with
the result.

## Locale

Day and month names come from **Carbon**, so they follow the app's locale rather
than a hard-coded English array. The trigger's summary is formatted by
`toLocaleDateString` in the browser, so it follows the *reader's*.

`weekStartsOn` is a prop because which day a week starts on is regional, not
universal, and no default is right everywhere. The day names rotate
arithmetically from it rather than being a second list that can fall out of step.

## Accessibility

- The popover is `role="dialog"`, opened by a button with `aria-haspopup="dialog"`
  and a live `aria-expanded`.
- The grid is `role="grid"` with `columnheader`s and `gridcell`s.
- **Arrow keys move a day, PageUp/PageDown a month, Home/End the ends of it.** A
  calendar that only answers to Tab makes a keyboard reader press it thirty
  times to reach the end of the month.
- Moving past the edge of the month **follows the grid** — it changes month and
  keeps focus on the day it landed on.
- The month label is `aria-live="polite"`, because pressing an arrow changes the
  grid the reader is standing in and nothing else announces it.
- Today carries `aria-current="date"`.
- Only one day is in the tab order; the arrows do the rest.
- `min`/`max` disable days rather than hiding them, so the shape of the month
  stays readable.

## Do not

- **Do not use it on a phone-first screen.** See the top of this file.
- **Do not use it for a date of birth.** A hundred-year calendar is thirteen
  hundred presses of "previous month"; that wants three selects or a text field.
- **Do not build a `Date` from the value and format it server-side per user.** The
  value is a calendar day, not an instant.
- **Do not add times to it.** A date and a time are two questions, and a picker
  that answers both does neither well.
