# OTP input

A fixed-length code, one box per character, posted as one field.

## Anatomy

```
Verification code                  ← label, names the group, targets box 1
┌──┐ ┌──┐ ┌──┐ ┌──┐ ┌──┐ ┌──┐
│ 4│ │ 8│ │ 2│ │  │ │  │ │  │      ← one box per character, field recipe "box"
└──┘ └──┘ └──┘ └──┘ └──┘ └──┘
We sent a six-digit code.          ← help, or the error in its place
[hidden input name="code" value="482"]
```

- **Boxes** use the field recipe (`Support\Field`), the same shell and control
  as ds::input, with the `box` padding: md's height, no side padding, the
  character centred in `tabular-nums`. 44px wide below `sm`, 36px above.
- **The row never wraps.** `flex` with `min-w-0` and `size="1"` lets six boxes
  narrow to fit 320px. A code broken over two lines reads as two codes.
- **One hidden input** carries the joined code and every host attribute but
  `class` and the bindings, so `@complete` and `required` sit on the thing that
  posts. The boxes have no `name`.
- **`wire:model*` and `x-model*` land on the root**, beside
  `x-modelable="dsValue"`, so they bind both ways (see Binding).
- **PHP renders `value` into the boxes** and the hidden input, so a code survives
  a failed round trip and posts without the script.

## States

| State | Treatment |
|---|---|
| empty, filled, focus, hover | As ds::input, per box |
| error | Every box gets the error border and `aria-invalid`; the message replaces help |
| disabled | Every box and the hidden input disabled; the recipe's flat shell |
| masked | `type="password"` boxes: dots, and a screen reader does not read the PIN |

## Behaviour (`dsOtp`)

| Input | Result |
|---|---|
| Typing an allowed character | Fills the box, focus moves to the next |
| A character not allowed (letter in numeric) | Ignored; the box keeps what it had |
| Typing into a filled box | Replaces its character (focus selects the box's content) |
| Backspace on a filled box | Clears it |
| Backspace on an empty box | Clears the previous box and moves there |
| Left / Right | Previous / next box |
| Home / End | First / last box |
| Paste, or autofill of several characters | Spread across the boxes, disallowed characters dropped; a full-length code fills from box 1, a shorter one from the current box; focus lands on the last box filled |

Each change sets the hidden input and dispatches `input` and `change` on it.
When every box is filled and the code differs from the last complete one, the
hidden input dispatches a bubbling `complete` CustomEvent with
`detail: { value }`.

No `maxlength` on the boxes: the browser's one-time-code autofill writes the
whole code into box 1, and `maxlength="1"` would cut it to one character before
the script could spread it.

## Binding

`x-modelable` is Alpine's core directive for a component that acts as a form
control. `dsValue` is the scope's one exposed name: the joined code, set by
the behaviour on every change the reader makes, and watched, so a code the host
sets is written into the boxes. A host value is filtered and cut to the length
like a paste, and the normalised code is handed back, so the host's state and
the boxes never disagree. A host change dispatches no `input`, `change` or
`complete`, the way setting a field's value from script fires nothing. On first
start the host's value wins over the server-rendered `value`. The name is
prefixed because the host's expression on the root is read in this scope: an
unprefixed `value` would shadow a host property called `value`.

## Accessibility

| | |
|---|---|
| Role | `group` around the boxes, labelled by the `label` (`aria-labelledby`) |
| Box name | "Digit n of N", or "Character n of N" when `numeric` is false |
| Description | Every box is described by the error or help, so it is read wherever focus lands |
| Invalid | `aria-invalid="true"` on every box |
| Autofill | Box 1 only: `autocomplete="one-time-code"`; the rest `off` |
| Phone keyboard | `inputmode="numeric"` when numeric; autocapitalise and autocorrect off |
| Focus | Tab enters the box with focus and leaves the group from the box it is in; `autofocus` puts focus on the first empty box |

Each box is a Tab stop. A roving tabindex would make the group one stop, but
someone tabbing back to fix one digit expects to land in it, and every OTP
field people meet elsewhere tabs box by box.

## Do not

- **Do not use it for a password** or anything without a fixed length.
- **Do not auto-submit with no button.** `complete` is for the happy path; a
  corrected code, or a page without the script, needs a button.
- **Do not trust the client filter.** Validate length and characters on the
  server.
- **Do not go past eight boxes.** A longer reference is one ordinary field.
