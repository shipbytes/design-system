# Radio

One of a set. Covers `radio` and `radio-group`.

If the options are not mutually exclusive, they are checkboxes — see
[checkbox.md](checkbox.md).

## Anatomy

```
Billing period                  ← legend
You can change this later.      ← group help
  ◉  Monthly
  ○  Yearly
     Two months free.           ← per-option help
  ○  Not now  (disabled)
```

Construction is identical to the checkbox — the native input, styled, in a grid
cell. See [checkbox.md](checkbox.md#the-native-input-styled) for why.

**A dot, not a tick.** The shape is the difference between "one of these" and
"any of these", and it is the only signal the reader gets before they click.
Round *and* ticked is the worst of both.

## The group is a fieldset

`radio-group` renders `<fieldset>` + `<legend>`, not a `<div>` with a heading.

A radio announces its own label and nothing else, so "Monthly" is read without
ever saying what is being chosen. **The legend is what a screen reader repeats as
the group is entered**, and it is the only thing that turns four options into a
question.

This is also why:

- **The error belongs to the group, not to an option.** "Choose a plan" is about
  the question. Attaching it to the first radio says the first radio is invalid,
  which it is not.
- `aria-describedby` on the fieldset carries the group's help and error, so both
  are announced once on entry rather than repeated per option.

Per-option `help` still exists, for the option that needs a footnote.

## Accessibility

- Every radio in a group shares one `name`. That is what makes them a group to
  the browser, and it is what makes the arrow keys work — **the browser provides
  arrow-key navigation for free**, which is a reason not to reimplement radios as
  buttons.
- A disabled radio uses the real `disabled` attribute and drops out of the arrow
  cycle, which is correct.
- The label is a real `<label for>` and carries the touch target.

## Do not

- **Do not ship a group with nothing selected and no "none" option** unless the
  question is genuinely optional. A radio group cannot be un-chosen once the
  reader picks — there is no way back to empty, and that surprises people.
- **Do not use a radio group for two options that fit a switch**, or for more than
  about seven, which is a [select](select.md).
- **Do not put radios in a group without a `radio-group`.** The legend is not
  decoration; without it the options are unlabelled.
- **Do not disable the whole group to mean "not applicable".** Hide it, or say why
  in the help — a greyed-out question is a question with no answer.
