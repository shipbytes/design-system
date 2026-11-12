# Number field

A native number input with a decrease and an increase button.

## Anatomy

```
Seats
┌──────────────────────┬───┬───┐
│ 3                    │ − │ + │   ← buttons inside the shell, trailing
└──────────────────────┴───┴───┘
Up to 10 on this plan.
```

- **The control** is ds::input's, from the field recipe, with right padding
  reserved for the two buttons and the browser's own spinner hidden.
- **The buttons** sit together at the trailing edge, inset by the border. Both
  on one side keeps the number where every other field's value starts, and
  keeps the pair under one thumb.
- `size`: `md` (the input's own 44px / 36px) or `sm` (32px everywhere, for a
  table row). Anything else falls back to `md`.

## States

| State | Treatment |
|---|---|
| default, hover, focus, error | As ds::input |
| at min / at max | That button disabled. PHP renders it from `value`, `min` and `max`; the script keeps it in step with typing |
| disabled | The input and both buttons disabled |

## Behaviour (`dsNumberField`)

| Input | Result |
|---|---|
| Press a button | Steps once with the native `stepUp()` / `stepDown()`, clamped; dispatches `input`, then `change` |
| Hold a button | Repeats after 400ms, every 75ms, until release, pointer leaving the button, cancel, or the bound; `input` per step, `change` once at the end |
| Activation without a pointer (a screen reader's click) | Steps once, `input` and `change` |
| Typing, Up / Down arrows | The browser's own number field |

`step="any"` has no native step; the buttons then move by 1.

## Accessibility

| | |
|---|---|
| Role | The native number input (spinbutton), with its own value and range |
| Name | The `label`, a real `<label for>` |
| Buttons | `type="button"`, "Decrease {label}" / "Increase {label}", `aria-controls` the input |
| Keyboard | Arrow keys step the input; the buttons are `tabindex="-1"` |
| Focus | Pressing a button does not move focus; the caret stays in the field |
| Phone | `inputmode="numeric"` for whole steps from zero up, `decimal` for a fractional step, none when `min` allows a negative (the iOS digit pads have no minus) |

The buttons are out of the tab order because the arrow keys already do their
job from the field, and two more stops per field would triple the Tab presses
through a form of them. A screen reader still finds them by their names.

## Without the script

The input is a complete number field and posts the server's value. The
buttons' `x-data` throws a named error on init, so a page that forgot the
script is told rather than left with buttons that do nothing.

## Do not

- **Do not use it for identifiers.** A postcode or phone number is text.
- **Do not use it for wide ranges.** Pressing to 1,000 is slower than typing.
- **Do not trust `min` and `max`.** Typing can pass them. Validate on the server.
