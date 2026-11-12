# Calendar

The month grid on its own, always visible: a booking page, a dashboard's
sidebar, a "which days" question that deserves the whole width of the form.

## One grid, two components

`ds::calendar` and the calendar in `ds::date-picker`'s popover are **one
implementation**: `resources/views/partials/calendar-grid.blade.php` draws it
from `Support\Calendar::grid()`, and `dsCalendar`
(`resources/js/behaviours/calendar.js`) redraws it. The 1.x date picker's
inline-Alpine grid was retired with it; see [date-picker.md](date-picker.md).

## Dates are strings, never `Date` objects

The date picker's rule, unchanged ([date-picker.md](date-picker.md#dates-are-strings-never-date-objects)).
Every day is `Y-m-d`, compared as a string. `Date.UTC` appears only for
arithmetic. **"Today" is the server's** (`today`, `now()` in the app's
timezone by default), never the browser's clock: a reader in another timezone,
or with a wrong clock, sees the same today the server validates against.

The arithmetic is written twice, `Support\Calendar` and
`resources/js/shared/dates.js`, and both are held to one fixture,
`tests/Fixtures/dates.json`: month and year boundaries, leap days, ISO weeks
across the new year, and the presets, from both sides.

## Anatomy

```
Delivery day
‹  [September ▾] [2026 ▾]  ›
Wk Mo Tu We Th Fr Sa Su
36     1  2  3  4  5  6
          ·  3              ← a dot in a tone, or a count, under the day
37  7  8  9 10 11 12 13     ← 10 chosen: surface-inverse
38 14 15 16 17 18 19 20     ← 14 today: fg, semibold
```

| Part | Token | Why |
|---|---|---|
| Chosen day | `surface-inverse` | The same "this one" the date picker uses. |
| Between a range's ends | `accent-wash` | A wash, as in the picker: it can cover weeks. |
| Today | `fg` + semibold | Weight, not colour. |
| Outside day (`showOutsideDays`) | `fg-muted` | Context, quieter than the month's own days, but selectable, so it keeps text contrast. |
| Week number | `fg-muted` | Read, so it keeps text contrast. |
| Disabled day | `fg-subtle`, `aria-disabled` | Still focusable, so the arrows can cross it and a screen reader says why it cannot be chosen. |
| Marker dot | the tone's base colour | A tone is a status; the label says it in words too. |
| Marker count | `fg-muted`, 10px | Under the number; on a chosen day `on-inverse`. |

**Six rows a month, always rendered.** Rows past a month's own are `hidden`.
The script redraws any month into the same cells and never creates one, so it
never adds a class a consumer's Tailwind did not see.

`months` (1–3) sit side by side in a wrapping row: two or three months are a
fixed width each, so a narrow container stacks them with no container query.
Previous is on the first month, next on the last; month and year selects
(`jump`) replace the first month's heading.

## Modes and what posts

| Mode | Value | Posts |
|---|---|---|
| one day | `Y-m-d` | `{name}` |
| `range` | `[from, to]` | `{name}_start`, `{name}_end`, the names ds::date-picker has always posted |
| `multiple` | a list | `{name}[]`, one a day |

Range: the first pick is the start; the second the end. **An earlier second
pick swaps the ends**, rather than starting again as the 1.x picker did.
Between the picks, the day under the pointer or the focus previews the range.

## Without the script

The grid is drawn complete by PHP, so the first paint is right: the month,
the chosen days, today, disabled days, markers. What posts is a row of
**native date fields** under the grid (one, From and To, or one per chosen day
plus an empty one), which the browser validates against `min` and `max`.
`dsCalendar` turns those same inputs into hidden ones and hides the row, so the
names are identical either way.

Chosen over making each day a radio or checkbox: a range cannot be two radio
groups in one grid, and the shared grid would then need two kinds of cell.
The cost is that, without the script, the grid is a picture and the fields do
the work. `disabledDates` and `disabledDays` are not enforced by a date field;
the server has to validate them anyway.

## Events

`events` maps a date to a tone (`neutral`, `accent`, `success`, `warning`,
`danger`) for a dot, or a positive count. The day's accessible name carries it
("Thursday 3 September 2026, 2 events", or "…, marked").

**There is no `day` slot**, and that is deliberate: the grid is rendered once
and then redrawn by the script for every month, so a slot rendered by Blade
for September would be wrong the moment the reader pressed next. A marker the
script can draw from data is the shape that survives.

`month-change` (`{ year, month }`) fires when the months in view change, so a
Livewire host can load that month's events; the options are read again after
a morph. Pass `month` (`Y-m`) back so the server renders the month the reader
is on.

## Accessibility

- Each month is `role="grid"`, named by its month and year, with
  `columnheader`s carrying the full weekday name (the two letters are
  `aria-hidden`), and a `rowheader` per week with `showWeekNumbers`.
- The calendar is a `role="group"`, named by `label` or `aria-label`.
- Each day is a button named by its full date (and its marker); its
  `gridcell` carries `aria-selected`. Today carries `aria-current="date"`.
- **One tab stop in the grids**: the chosen day in view, else today, else the
  first of the month.
- A polite live region says the month (or months) when they change.

| Key | Does |
|---|---|
| ← → | A day (mirrored in a right-to-left page) |
| ↑ ↓ | A week |
| PageUp / PageDown | A month, the same day (held to the end of a shorter month) |
| Shift + PageUp / PageDown | A year |
| Home / End | The first / last day of the week |
| Enter / Space | Choose the day |

Moving to a day outside the months in view brings its month into view.

## Do not

- **Do not use it for a date of birth.** As the date picker: three selects or
  a text field.
- **Do not build the today highlight from the browser.** Pass `today` if the
  server's `now()` is not the right one (a user's own timezone, say).
- **Do not expect a `day` slot.** See Events.
- **Do not use `multiple` for a range.** A range is two values with a meaning;
  a list of days is a list.
