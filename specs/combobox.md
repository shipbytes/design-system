# Combobox

A select with a text filter. `multiple` is a **mode of it**, not a second
component.

## Why one component

The keyboard handling, the popover, the option list and the aria wiring are
identical between single and multiple. Chips in the field and
`aria-multiselectable` are the whole difference.

Two components would mean two copies of the arrow-key and filtering logic —
which is exactly the pair that drifts, because a fix goes into the one you were
looking at.

## Combobox, select or checkboxes

| Options | Reach for |
|---|---|
| under ~15, one choice | [select.md](select.md) |
| under ~12, several choices | a list of [checkboxes](checkbox.md) — more scannable, and free |
| more than that, or unfamiliar values | **this** |

A combobox costs the reader a decision before they can act: they have to guess
what to type. Below about fifteen options a plain list is faster, and on a phone
the native `<select>` is faster still.

## Anatomy

```
Tags
┌───────────────────────────────────────┐
│ (Accessibility ✕) (Billing ✕) Search… │  ← chips live INSIDE the field
└───────────────────────────────────────┘
┌───────────────────────────────────────┐
│ Accessibility                      ✓  │
│ Billing                            ✓  │
│ Compliance                            │
└───────────────────────────────────────┘
```

**Chips sit in the field, not under it.** A list of choices below the control
reads as *results*, and people click them expecting to select rather than to
remove.

Clicking anywhere in the field focuses the text input. A combobox whose chips
take the click and leave the caret elsewhere feels broken in a way nobody can
articulate.

## Filtering is client-side

The whole option list is passed in and filtered in the browser. That is the
honest limit of a presentation-only component: server-side filtering needs a
request, and a request needs a backend contract.

For a list too large to send, the host wants a different component — one that
takes a search callback — and that does not exist yet.

## What posts

- **Single** — one hidden input, rendered by PHP *and* bound for Alpine, so the
  field still carries its value where the JS never runs.
- **Multiple** — `name[]` inputs generated with `x-for`, which **requires Alpine**.
  An unknown number of inputs cannot be server-rendered and then taken over
  without duplicating them.

That asymmetry is deliberate and documented rather than hidden: a combobox is an
Alpine component either way, and the single case costs nothing to make correct.

## Keyboard

- Typing filters. Arrow keys walk the **filtered** list — the handler reads the
  DOM, because what is on screen is the only correct source once a filter is on.
- Home and End jump to the ends; Enter and Space choose.
- **Backspace on an empty query removes the last chip.** Without it the only way to
  undo a selection is to aim at a 12px ✕, which on a touch screen is not a
  target at all.
- Escape closes and returns focus to the field.

## Accessibility

- The text input is `role="combobox"` with `aria-autocomplete="list"`, a live
  `aria-expanded`, and `aria-controls` pointing at the listbox.
- The list is `role="listbox"` with `aria-multiselectable` bound to the mode.
- Each chip's remove button carries its own label — "Remove Billing" — because
  half a dozen buttons all announcing "Remove" are indistinguishable.
- **An empty result shows a message.** A listbox that renders nothing reads as a
  broken control rather than as no matches.

## Do not

- **Do not use it under fifteen options.** See the table above.
- **Do not use it for actions.** A menu of verbs is a [dropdown](dropdown.md).
- **Do not expect server-side filtering.** See above.
- **Do not use `multiple` where the total is unbounded.** Twenty chips wrap the
  field to four lines and push the page around; that wants a two-pane picker.
