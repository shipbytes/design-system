# Input

A text field, a textarea, or a native select. One component, three shapes.

![Input](../images/input.png)

## Use it

```blade
<x-ds::input name="email" label="Email address" type="email" />
<x-ds::input as="textarea" name="notes" label="Notes" :rows="4" />
<x-ds::input as="select" name="plan" label="Plan">
    <option value="free">Free</option>
    <option value="pro">Pro</option>
</x-ds::input>
```

## Props

| Prop | Type | Default | What it does |
|---|---|---|---|
| `as` | `input` `textarea` `select` | `input` | Which control to render. |
| `type` | string | `text` | Any HTML input type. Ignored for the other two. |
| `label` | string | — | Rendered as a real `<label for>`. |
| `help` | string | — | Guidance under the field. |
| `error` | string | — | Validation message. Styles the control and sets `aria-invalid`. |
| `icon` | string | — | Heroicon inside the control, before the value. |
| `rows` | int | `3` | Textarea only. |
| `disabled` | bool | `false` | |
| `readonly` | attribute | — | Not a prop: pass it like any HTML attribute. The field looks fixed (subtle fill, muted text, no hover) but stays focusable and is submitted. Ignored for `as="select"`, as the browser ignores it there. |

`name`, `value`, `placeholder`, `required`, `wire:model` and anything else pass
straight through to the control.

## `help` is replaced by `error`, never stacked

Two lines of guidance under one field is one too many. Pass both and the error
wins while it is there.

```blade
<x-ds::input
    name="email"
    label="Email address"
    help="We only use this for receipts."
    :error="$errors->first('email')"
/>
```

With Laravel validation that is the whole integration — `$errors->first()`
returns `null` when the field is clean, so `help` shows until it doesn't.

## An `id` is generated if you don't give one

The label needs a `for` and the help text needs an `aria-describedby`, so the
component generates an id per field. Pass your own `id` when something outside
needs to point at the control.

**Under `wire:model` the generated id is the same on every render**, a hash of
the binding, name, value and label. Livewire's morph matches elements by id, and
until v1.2.2 a fresh random id on each render made it *replace* a bound field
rather than update it: the field lost focus, and a live search kept the first
burst of typing and dropped the rest. A field with no binding still gets a
random id, so two look-alike fields on one plain page do not collide. The same
holds for checkbox, radio, switch, select, combobox, date-picker and
file-upload.

Two bound fields that agree on binding, name, value and label are the same field
twice. Give one an `id`.

## Readonly is not disabled

A value the user should see, copy and send but not change, such as the email a
password-reset link was sent to, is `readonly`:

```blade
<x-ds::input name="email" label="Email" type="email" :value="$email" readonly />
```

It looks fixed, with the subtle fill and muted text, and no hover border, so it
does not invite typing. Unlike `disabled` it stays in the tab order, its text can
be selected, and it is **submitted with the form**. A disabled field is left out
of the request entirely, which is how a reset form ends up posting no email.

## The select here is the native one

`as="select"` renders a real `<select>` — the OS control, with the OS popup.
**On anything phone-first that is the right choice**: it gets typeahead and the
platform's own scrolling for free.

Use [`x-ds::select`](select.md) when the unstyleable native popup is the problem.

## Accessibility

- `label` is a real `<label for>`, so clicking it focuses the field.
- `error` sets `aria-invalid="true"` and wires `aria-describedby`.
- The focus ring is drawn **inward**, so the box never changes size on focus and
  nothing below it shifts.
- Omit `label` only when a visible label elsewhere already names the field — and
  then give the control an `aria-label` yourself.

## Don't

- **Don't use a placeholder as the label.** It disappears the moment someone
  types, and it is invisible to autofill.
- **Don't put the error in an [alert](alert.md) above the form.** The reader then
  has to work out which field it meant.
- **Don't disable a field to mean "not applicable".** Hide it, or explain in
  `help` — a greyed-out field is a question with no answer.

More in [specs/input.md](../../specs/input.md).
