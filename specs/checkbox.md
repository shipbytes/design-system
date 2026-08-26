# Checkbox

One independent yes/no. Not one of a set — that is [radio.md](radio.md).

## Anatomy

```
☑  I agree to the terms
   At most one email a month.     ← help, or error
```

| Part | Token | Why |
|---|---|---|
| Box | `border-strong` on `surface` | 18px (`size-4.5`), the same mark size the alert uses. |
| Checked | `accent` + `on-accent` | The one place `accent` means "on" rather than "link". |
| Error | `border-danger` | Plus `aria-invalid`. |
| Disabled | `surface-subtle`, `fg-subtle` fill | Checked-and-disabled stays visibly checked — a locked-on setting must still read as on. |

## The native input, styled

`appearance-none` on the real `<input type="checkbox">`, not a hidden input beside
a decorated `<span>`.

The hidden-input trick loses the browser's own focus behaviour, Windows
high-contrast rendering and `forced-colors` support — and **every one of those
failures is invisible in a normal browser on a normal machine**. Nobody finds
them until a user does. `forced-colors:appearance-auto` hands the control back to
the OS palette, where the fill is stripped and the native mark is the only thing
left.

The input and its tick occupy **one grid cell**, stacked, rather than the tick
being absolutely positioned. Absolute positioning against a label that wraps to
two lines drifts; a grid cell cannot.

## Indeterminate

`indeterminate` is the "select all" state, where some children are checked.

**It is a DOM property. No attribute sets it.** The component emits `x-init` to
set it, which covers any host with Alpine; a host without Alpine sets it in one
line of its own.

The important part is what the *styling* keys off: the `:indeterminate`
pseudo-class — the property itself — and **not** the prop. So in a host where
nothing sets the property, the box renders unchecked, which is exactly what a
screen reader announces. The feature is absent rather than lying, and a developer
testing it sees that immediately.

Styling from the prop instead would draw a mixed mark on a control reporting
"unchecked", and that divergence is invisible until an assistive technology
reads it.

## Label

`label` is required, and it is a real `<label for>`, so **its whole width is a
click target**. On a phone that is most of the touch target — the 18px box on its
own is well under the 44px minimum, and the label is what makes up the
difference.

`help` is replaced by `error`, never stacked with it. Two lines of guidance under
one control is one too many; the same rule as [input.md](input.md).

## Accessibility

- `aria-describedby` wires help *or* error to the input.
- `error` sets `aria-invalid="true"`.
- Disabled uses the real `disabled` attribute — a checkbox is a form control, and
  a disabled one should genuinely not submit.
- The focus ring is on the box, `focus-visible` only.

## Do not

- **Do not use one for a two-way choice.** "Send as HTML / Send as plain text" is
  a radio pair; a checkbox makes the reader work out what unchecked means.
- **Do not use a checkbox as a switch.** A switch applies immediately; a checkbox
  is submitted. If it takes effect on click, it needs `role="switch"` and a
  different component.
- **Do not omit the label** and rely on a table column header. The header is not
  programmatically associated, and the touch target collapses to 18px.
- **Do not put the error on the box** in a group of checkboxes — it belongs to
  the group. See [radio.md](radio.md#the-group-is-a-fieldset).
