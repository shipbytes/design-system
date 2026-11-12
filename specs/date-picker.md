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

**Today is the server's day** (`today`, `now()` in the app's timezone by
default), never the browser's clock.

## One grid: the calendar's

Since 2.0 the calendar in the popover is the one [calendar.md](calendar.md)
describes: `partials/calendar-grid.blade.php`, drawn by `Support\Calendar` and
redrawn by `dsCalendar` in the package's script. The 1.x inline-Alpine grid
was retired in batch 18 (the owner's decision), so the date picker **needs the
script**. What a consumer relies on is pinned by `DatePickerContractTest`: the
props, the posted names and values, the trigger, the popover's role, the label
and the error and help wiring. Upgrade notes are in
[getting started](../docs/getting-started.md#from-1x-to-20).

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

The calendar opens below the trigger, 4px off it, start-aligned. It is about
340px tall, which is why it was the overlay that ran furthest off the page:
`x-ds-anchor` (the package's script) places it with `position: fixed`, flips it
above when there is no room below, shifts it along the edge to stay 8px inside
the viewport, and caps its height with a scroll when neither side fits. With
room it lands where `absolute mt-1` puts it; the behaviour suite measures both.
The absolute classes are the placement before the script places it.

## What posts

`Y-m-d`, which is what a database column and `Carbon::parse` both want.

A range posts **two fields** — `{name}_start` and `{name}_end`. Two dates are two
values, and squeezing them into one string means every consumer writing the same
parser and getting the separator wrong.

## Without the script

The names post from **native date fields** (`<input type="date">`, two for a
range with an en dash between), rendered with the value, `min` and `max`, and
labelled by the label (and "Start" / "End"). `dsCalendar` turns those same
inputs into hidden ones, hides their row and shows the trigger, so the names
are identical either way. A disabled picker's fields are `readonly`, not
`disabled`, so they still post, as the 1.x hidden inputs did.

The trigger is rendered `hidden` and shown by the script, as the colour and
time pickers swap their native fields: without the script there is no button
that opens nothing. The cost is that the native field shows until Alpine
starts. With Alpine and no script the console names the missing include
(`dsCalendar is not registered: add <x-ds::scripts />…`).

## Range behaviour

First click sets the start and clears the end. Second click sets the end and
closes (unless `confirm`).

**A second click *before* the start swaps the ends** rather than producing a
backwards range the reader then has to undo: the earlier day becomes the start.
(1.x started the range again from there.)

While a range is half-made, the day under the pointer or the focus previews it,
through the same test the committed range uses, so the preview cannot disagree
with the result.

## Months, presets, minDays, maxDays, confirm

`months` is two side by side (one on a phone, whose next button is on the
first month). `minDays` / `maxDays` (with `range`) disable the ends that would
break them once a start is chosen, counted inclusively. `confirm` makes picks a
draft: Apply commits and closes, Cancel or Escape drops it, and nothing closes
the popover by itself.

Presets: `true` is Today, Yesterday, Last 7 days, Last 30 days, This month
(the 1st to today), Last month (the whole month) and This year (1 January to
today), counted from `today`, the server's day (`now()` in the app's
timezone), never the browser's clock. PHP works them out for the first paint
(`Support\Calendar::presets`) and the script again from the same `today`
(`shared/dates.js`); both are held to `tests/Fixtures/dates.json`. A host's
own `label => [from, to]` carries its dates. The preset equal to the range is
`aria-pressed`. The list is a `group` named "Presets", beside the grid from
`sm`, wrapping above it below.

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
- The grid is `role="grid"` with `columnheader`s, `row`s and `gridcell`s, each
  cell carrying `aria-selected`; every day is named by its full date.
- **Arrow keys move a day or a week, PageUp/PageDown a month (Shift: a year),
  Home/End the start and end of the week**, the ARIA date grid's keys. A
  calendar that only answers to Tab makes a keyboard reader press it thirty
  times to reach the end of the month.
- Moving past the edge of the month **follows the grid** — it changes month and
  keeps focus on the day it landed on.
- A polite live region says the month when it changes, because pressing an
  arrow changes the grid the reader is standing in and nothing else announces it.
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
