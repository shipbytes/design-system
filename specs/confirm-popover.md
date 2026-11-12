# Confirm popover

An anchored confirmation card on ds::popover: a question, its consequence,
Cancel and Confirm, and optionally a type-to-confirm field. Needs Alpine and
the package's script (`dsPopover`, `x-ds-anchor`, `dsConfirmDialog`).

## Anatomy

```
[ Delete project ]                       ← the consumer's trigger (slot)
╭──────────────────────────────────╮     ← ds::popover panel, role=alertdialog,
│ Delete project?                  │       aria-labelledby → title,
│ This cannot be undone.           │       aria-describedby → description
│ Type acme-prod to confirm        │     ← label for the field
│ [                              ] │     ← ds::input, no name, data-ds-autofocus
│            [ Cancel ] [ Delete ] │
╰──────────────────────────────────╯
```

- **Popover**: ds::popover with `role="alertdialog"`, `labelledby` the title,
  `describedby` the description, and the host's `placement`, `width`, `open`
  and `class`. Everything about opening, closing, focus return and placement
  is the popover's.
- **Card**: `div[data-ds-confirm-dialog]`,
  `x-data="dsConfirmDialog({ text, strict, loading })"`.
- **Title**: `p`, `text-body font-semibold text-fg`. **Description**: `p`,
  `mt-1 text-body text-fg-body`.
- **Field** (with `confirmText`): a `label` holding `confirmPrompt` with
  `confirmText` in a bold `strong`, and ds::input with a hashed id, no `name`,
  `autocomplete`, `autocapitalize` and `autocorrect` off, `spellcheck="false"`,
  `data-ds-confirm-field`, `data-ds-autofocus`.
- **Footer**: `mt-4 flex justify-end gap-2`: Cancel (ds::button `secondary`
  `sm`, `data-ds-confirm-cancel`, `data-ds-autofocus` when there is no field)
  and Confirm (ds::button `danger`, or `primary` for `tone="neutral"`, `sm`,
  `data-ds-confirm-confirm`, `type` from the prop, every host attribute but
  `class`, disabled from the server when there is a field, bound to
  `$data.dsMatched`, and a spinner shown while busy).

Ids are hashed from the title, description, confirm label and text, so a
Livewire morph patches rather than replaces.

## States

| State | Treatment |
|---|---|
| closed | the popover's |
| open | focus on Cancel, or on the field |
| unmatched | Confirm `disabled` (`opacity-50`) |
| busy (`loading`) | spinner, `aria-busy`, `aria-disabled`, `cursor-wait` on Confirm, Cancel disabled; stays open |

## Behaviour (`dsConfirmDialog`)

- It sits inside the popover's scope and reads and writes its `dsOpen`.
- **Matching**: on every `input`, `dsMatched` is `value === text`; with
  `strict: false`, both sides trimmed and lower-cased. With no `text`, always
  matched.
- **Enter in the field** is prevented (implicit submission would press the
  form's FIRST submit button, which need not be Confirm) and clicks Confirm
  when matched.
- **Confirm**: a click caught capturing on the card is swallowed while busy or
  unmatched; otherwise `confirm` is dispatched from Confirm, the click goes on
  to the host's listeners and the form, and the popover closes after it, or
  with `loading` stays open busy.
- **Cancel** closes it. The popover returns focus to the trigger.
- **On every close** that was not a confirmation (Cancel, Escape, a press
  outside, focus leaving), `cancel` is dispatched from Confirm. Busy ends.
- **On every open** the field is emptied and Confirm disabled again.
- A `pageshow` from the back-forward cache closes it.
- The scope exposes `dsMatched` and `dsBusy` only.

The popover's focus-in looks for `[data-ds-autofocus]` before `[autofocus]`,
a mark of the component's own that has none of `autofocus`'s page-load
meaning.

## Choices and why

- **`role="alertdialog"` over the popover's `dialog`.** The card exists to ask
  one question before something happens, which is what an alert dialog is;
  and a screen reader announcing an `alertdialog` reads its description
  (`aria-describedby`, the consequence) along with its name, where a `dialog`
  is announced by name alone and the consequence waits to be found. ARIA asks
  that an alert dialog be modal. This one is not trapped, but it is
  light-dismissed: Tab away, a press outside or Escape CANCELS it, so the page
  behind is never operated while the question is asked, which is the
  guarantee modality exists to give. `aria-modal` is left off because the
  page behind is not inert, and claiming it would make a screen reader hide
  the rest of the page.
- **Focus on Cancel, or the field.** The safe choice; and when there is
  something to type, focus where the typing goes.
- **Exact and case-sensitive by default.** What GitHub-style type-to-confirm
  readers expect, and the strictest reading of "the project's name". `strict`
  relaxes case and surrounding spaces only, never the characters.
- **The field has no name.** It is a check on the reader, not data; the
  server checks intent its own way.
- **`loading` ends with the close.** The host owning `open` is the host
  saying when the action is done.

## Limits

- Inherits the popover's: a transformed ancestor still clips it.
- The field's check is a convenience, not security.

## Accessibility

| | |
|---|---|
| Role | `alertdialog` on the panel, not modal |
| Name | the title, by `aria-labelledby` |
| Description | the description, by `aria-describedby` |
| Keyboard | Enter or Space on the trigger opens; Tab through field, Cancel, Confirm and out (which cancels); Enter in the field confirms when it matches; Escape cancels |
| Focus | to Cancel, or the field, on open; back to the trigger on Cancel, Escape and confirm |
| Field | a real `label`; no autocomplete, autocorrect, autocapitalise or spellcheck |
| Busy | `aria-busy`, `aria-disabled` on Confirm |
| Motion | the popover's: fade and 95% scale, `motion-safe` only |

## Do not

- **Do not use it when the page must wait for the answer.** That is a modal.
- **Do not ask for type-to-confirm on routine actions.**
- **Do not leave `type` as submit inside a form it must not submit.**
