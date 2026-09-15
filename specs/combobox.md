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

**One choice**, at rest, with a value:

```
Gate
┌───────────────────────────────────────┐
│ Main gate                        ✕  ⌄ │  ← the chosen value, as TEXT
└───────────────────────────────────────┘
```

**Several choices:**

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

## A chosen value is text, not a placeholder

**This section is the one that was missing.** Both implementations rendered a
single chosen value as the input's `placeholder`, in `text-fg-muted` — the
styling whose entire job is to say *this is a hint, not a value*. A settled
field therefore drew itself as an empty, focused text box: faded grey with a
blinking caret, read by every user as "nothing chosen yet, type something",
when in fact the choice was made and constrained. The spec said nothing about
the single-select resting state, and its silence is what let Blade and React
agree on the same wrong answer without either being able to point at a contract
it broke.

A single-select has **three** states. Only the first two are visible at rest.

**Settled** — a value is chosen, nothing typed.

- The label is the field's **value**, at `text-fg`. Never the `placeholder`,
  never `text-fg-muted`.
- **No caret**: `caret-transparent`. There is nothing to edit there.
- The chevron sits at the right, and a clear ✕ to the left of it — see below.
- `placeholder` goes back to its real job: the hint shown when **nothing** is
  chosen.

**Opening** — clicked, or ArrowDown, with nothing typed.

- The list opens and **the label stays in the field at full strength**.
- The current value is ticked in the list.
- Still no caret. Nothing is lost by a stray click, which is the whole point of
  reaching for a constrained control.

**Searching** — at least one character typed.

- The field shows the **query**, at `text-fg`, with a visible caret.
- The chosen label steps aside while typing and stays ticked in the list.
- Deleting back to an empty query returns the field to **Settled** — the label
  comes back. Nothing is lost by typing and then changing your mind.

In one line: the field shows the query when there is one and the label
otherwise, and the caret is transparent exactly when it is showing a label.

**The first keystroke starts a fresh query, it does not edit the label.** The
label is in the input's `value`, so a browser hands back `Main gatex` when the
user types `x` into a settled field. What was inserted is the query; the label
is not a string anybody is editing.

**The focus ring stays.** It is the only thing telling a keyboard user where
they are. The caret was the part that lied, not the ring.

## Clearing one value

A single-select used to be a one-way door: backspace-to-clear was gated on
`multiple`, and the ✕ existed only on a chip. Once a value was picked it could
be swapped but never unset.

**`required`** (default `false`) is what decides whether it can be:

- A **clear ✕** shows when the field is not `required`, not `multiple`, and has
  a value. It sets the value to nothing and clears the query.
- **Backspace on an empty query** unsets the value, exactly as it removes the
  last chip when `multiple`. Ungated from `multiple`, gated on `required`.
- A `required` field shows **no ✕**, because clearing it could only produce a
  state the form rejects.
- `required` also sets `aria-required`.

Multi-select is untouched by all of this. Chips already render at full strength
inside the field and read correctly.

## A label that has not arrived yet

`labelFor` falls back to the raw value when the option is not in the list.
While the value is a faded placeholder that is merely odd; as full-strength text
it is a glaring `4711` sitting where a name belongs.

**While the label is unresolved, show the placeholder rather than the raw
value.** A host that resolves selected labels asynchronously — the React port's
consumers fetch them by id — has a window between the value arriving and its
label arriving, and an id is not a worse label, it is a wrong one.

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
- **Backspace on an empty query clears the selection** — the last chip when
  `multiple`, the single value otherwise, unless the field is `required`.
  Without it the only way to undo a selection is to aim at a 12px ✕, which on a
  touch screen is not a target at all.
- Escape closes and returns focus to the field.
- Typing into a **settled** single-select starts a new query rather than editing
  the label that is sitting in the field.

## Accessibility

- The text input is `role="combobox"` with `aria-autocomplete="list"`, a live
  `aria-expanded`, and `aria-controls` pointing at the listbox.
- The list is `role="listbox"` with `aria-multiselectable` bound to the mode.
- `aria-required` follows the `required` prop. Neither implementation exposed it
  at all before.
- Each chip's remove button carries its own label — "Remove Billing" — because
  half a dozen buttons all announcing "Remove" are indistinguishable. The single
  select's clear button carries "Clear Billing" for the same reason.
- **A chosen value belongs in the input's `value`**, not its `placeholder`.
  Screen readers treat a placeholder as a hint and announce it inconsistently or
  not at all, so a field whose only statement of its answer was the placeholder
  had no reliable way to say what was chosen.
- **An empty result shows a message.** A listbox that renders nothing reads as a
  broken control rather than as no matches.

## Do not

- **Do not use it under fifteen options.** See the table above.
- **Do not use it for actions.** A menu of verbs is a [dropdown](dropdown.md).
- **Do not expect server-side filtering.** See above.
- **Do not render a chosen value as a placeholder.** That is the defect this
  spec was silent about; see above.
- **Do not mark a field `required` to tidy the field up.** It removes the only
  way to unset a value, and it tells assistive technology something about the
  form that may not be true.
- **Do not use `multiple` where the total is unbounded.** Twenty chips wrap the
  field to four lines and push the page around; that wants a two-pane picker.
