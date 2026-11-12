# Time picker

A time of day, typed or picked from a list. What posts is always `HH:MM`, 24
hours; what shows may be 12-hour.

## Anatomy

```
Start
┌──────────────────────────┬──┐
│ 9:30 AM               ✕  │◷ │   ← text field (combobox), clear, clock
└──────────────────────────┴──┘
┌─────────────────────────────┐
│ 9:15 AM                     │   ← listbox of times at `step`
│ 9:30 AM                  ✓  │
│ 9:45 AM                     │
└─────────────────────────────┘
Office hours.
```

- **The field** is ds::input's, from the field recipe, with room at the
  trailing edge for the clock and, with `clearable`, the ✕. Visually paired
  with ds::date-picker: the same label, height, border, radius and a trailing
  icon in the same place, so the two sit side by side as one row.
- **The list** is the listbox recipe ds::combobox draws with
  (`partials/listbox.php`): the same panel, the same options, the same
  arrow-key handler, placed by `x-ds-anchor` with the same absolute fallback.
  Every time from `min` (midnight without one) to `max` (23:59) at `step`,
  rendered by PHP. Under a 5-minute step the list counts in the smallest
  multiple of the step that is at least 5; typing still takes every allowed
  minute.
- **The hidden input** posts. Rendered with no name; the script gives it the
  field's name when it takes the field over.
- `size`: `md` or `sm`, as ds::input.

## Two modes

| | Without the script | With it |
|---|---|---|
| The field | A native `<input type="time">` with the same `name`, `min`, `max` and `step` (in seconds) | A text field, `role="combobox"` |
| Posts | The native field, `HH:MM` | The hidden input, `HH:MM` |
| Picking | The browser's own picker | The list |
| Bounds and step | The browser's own validation | The same, by `setCustomValidity` |
| Shown | The browser's own format | `format`: `24` ("09:30") or `12` ("9:30 AM") |

Every attribute the upgrade changes (type, name, role, the ARIA state,
placeholder) is also bound to the behaviour's `dsReady`, so a Livewire morph,
which rewrites attributes to the server's, keeps the upgraded field. The
bindings read `$data.dsReady`, which is undefined and harmless where the
script is missing.

## Typing

Forgiving: `9`, `930`, `0930`, `9:30`, `9.30`, `9 30`, `9h30`, `9:30:00`,
`9.30pm`, `9:30 P.M.`, `21:30` all read; `12am` is midnight, `12pm` noon.

The text is settled on **commit**: Enter, leaving the component, a press on
an option, or a `change` (the ✕ sends one).

| Committed text | Shown | Posts | Marked |
|---|---|---|---|
| A time inside the bounds, on a step | As the list shows it | `HH:MM` | valid |
| A time outside `min`..`max`, or off the step | **The reader's text, kept** | `HH:MM` | `aria-invalid`, and a validity message, so the form does not submit |
| Not a time | **The reader's text, kept** | nothing | `aria-invalid`, and a validity message |
| Empty | — | nothing | valid (`required` is the browser's) |

Never quietly turned into another time: "7pm" in an office-hours field stays
"7pm", marked, rather than becoming 6:00 PM.

## Behaviour (`dsTimePicker`)

| Input | Result |
|---|---|
| Click in the field | Opens or closes the list |
| Typing | Opens the list and scrolls it to the nearest time |
| ArrowDown, Alt+ArrowDown | Opens the list, focus to the chosen time or the nearest to what is typed |
| ArrowUp (list open) | Focus into the list |
| Arrows, Home, End in the list | Move, wrapping (the listbox recipe) |
| Enter or Space on an option, click | Picks it, closes, focus back to the field |
| Escape | Closes the list, focus to the field; stopped only when the list was open |
| A character typed in the list | Goes to the field |
| Enter in the field | Commits; with the list open, closes it and does not submit |
| Leaving the component | Commits and closes |
| Host sets the value | Shown, not announced; a value that is not a time is cleared and handed back |

A committed change writes `dsValue` and dispatches `input` and `change` from
the hidden input, bubbling.

## Accessibility

| | |
|---|---|
| Role | Without the script, the native time field. With it, `combobox` with `aria-expanded`, `aria-controls` the `listbox`, `aria-autocomplete="none"` (the list is not filtered) |
| Name | The `label`, a `<label for>`; the list is labelled by it too |
| Options | `role="option"`, `aria-selected` on the chosen time, a ✓ drawn from it |
| Keyboard | As the table above, the ARIA combobox pattern ds::combobox follows: focus moves into the list |
| Focus | The field's ring is the field recipe's; an option's is drawn inward, as the list scrolls |
| Errors | `error` sets `aria-invalid` and describes the field; it stays marked after the reader fixes the time, because the server's message is still there |

## Out of scope

- **Date and time in one field.** Put a [date picker](date-picker.md) and a
  time picker side by side; see the docs page.
- **Arbitrary disabled slots.** `min` and `max` only.
- **A range across midnight.** `min` must be earlier than `max`.

## Do not

- **Do not post the shown text.** It is 12-hour and in English; HH:MM is what
  the server gets, always.
- **Do not use it for a duration.** "1:30" of work is not half past one.
- **Do not trust `min`, `max` and `step`.** Validate on the server.
