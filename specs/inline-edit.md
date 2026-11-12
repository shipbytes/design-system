# Inline edit

Text that turns into a field where it stands: a title renamed in its
heading, a cell edited in its row.

## Anatomy

```
Read                              Edit
Q3 roadmap  ✎                     ┌────────────┐
└ a button, pencil on              │Q3 roadmap  │   [Save] [Cancel]   ← buttons mode
  hover and focus                  └────────────┘
                                   ⓘ That name is taken.               ← error
```

- **Read state**: the text in a `button`, with a pencil at its end that shows
  on hover and on keyboard focus, and always on a coarse pointer (touch has
  no hover). While empty it shows `placeholder` in `fg-muted`.
- **Edit state**: an input, or a textarea with `multiline`, in the same box:
  the same padding, a one-pixel border (transparent while reading) and the
  FONT OF WHERE IT SITS (`font: inherit`), so in a heading the field is
  heading-sized and the words do not move when it opens. It opens at the
  width the text took and grows with it (`field-sizing: content`, or the
  script where that is missing).
- **Buttons** (`submit="buttons"`): Save and Cancel after the field.
- **The message**: under the field, from `error` or the host.
- **The form** (`action`): a real form around it, posting to `action` with
  `method` (PUT, PATCH and DELETE spoofed with `_method`) and the CSRF token.
- `size`: `md`, or `sm` with less room around the text, for a table cell.
  The box is pulled out by its padding (`-mx-*`) so the text lines up with
  the text around it.

## States

| State | Treatment |
|---|---|
| reading | Text, `hover:bg-fg/5`, the pencil on hover and focus |
| editing | The field, `border-border`, the focus ring outside it |
| saving | A spinner in the pencil's place, `aria-busy` on the root |
| error | Editing, `border-danger`, `aria-invalid`, the message under it |
| disabled | The button and the field disabled |

## Behaviour (`dsInlineEdit`)

| Input | Result |
|---|---|
| Press the text (click, Enter, Space) | Opens the field with the text selected |
| Enter (Ctrl/Cmd+Enter when `multiline`) | Saves, except with `submit="blur"`; never submits a form around it by itself |
| Leaving the component | Saves, except with `submit="buttons"`, which keeps editing |
| Save / Cancel | Save / cancel |
| Escape | Cancels: the text goes back to what it was |
| After a save or cancel from a key or a button | Focus back on the text's button |
| After a save by leaving | Focus stays where the reader went |

**A save** that changed nothing just closes. Otherwise it dispatches `save`
from the root (bubbling, cancelable, `{ value, previous }`), shows the new
text and writes `dsValue`. That is all it assumes: no optimistic state
beyond the text, which the host's next render replaces. Inside the view's
form, a `save` nobody cancelled submits the form and marks it saving.
**A cancel** dispatches `cancel` with `{ value }`.

**Saving and failing are the host's to say**, as two attributes on the root
that the behaviour watches: `data-ds-saving` (the `saving` prop, Livewire's
`wire:loading.attr`, or `dsSaving` in Alpine) and `data-ds-error` (the
`error` prop, or `dsError`). An error opens the field again on the text the
reader tried, moves focus into it if it was on the text's button, and keeps
it open until a save gets through or the reader cancels. A morph that brings
or clears either is heard, so a Livewire re-render with the validation
message lands as it should.

The read state and the field are bound to `dsReady` and `dsEditing`, so a
morph keeps the reader where they were.

## Accessibility

| | |
|---|---|
| Read role | `button`, named "Edit {label}: {text}" ("Edit Title: Q3 roadmap"), or "Edit {label}" while empty |
| Field | A text field named by `label` (`aria-label`, the label is not shown), described by the message |
| Keyboard | As the table above |
| Focus | The button's ring is outside it; the field's ring is outside it too. Focus never drops to the page on open or close: the field is shown and focused before the button hides, and back |
| Touch | The pencil always shows on a coarse pointer |

## Without the script

The field is shown and always editable, named, posting under `name`: inside
the host's form, or inside the one `action` draws, with a Save (submit) and
a Cancel (the form's reset, which puts the text back). On a page the script
never reaches the text can still be changed. The `x-data` throws its named
error, so a page that forgot the include is told. Until the script starts
the field shows, so the first paint of a page with the script shows fields
for a moment.

## Do not

- **Do not use it for a form.** One value at a time; several fields that
  belong together are a form with a Save.
- **Do not use it for text that needs formatting.** It is plain text; rich
  text editing is out of scope.
- **Do not hide what failed.** Pass the server's message as `error`, or set
  `dsError`; a field that silently reverts is a lost edit.
- **Do not use it where the reader cannot tell it is editable.** The pencil
  shows on hover and focus only; a page of nothing but inline edits needs
  another cue.
