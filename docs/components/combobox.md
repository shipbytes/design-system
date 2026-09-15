# Combobox

A select with a text filter. Multi-select is a **mode** of it.

![Combobox](../images/combobox.png)

> **Needs Alpine.** See [getting started](../getting-started.md#alpine).

## Use it

```blade
<x-ds::combobox
    name="country"
    label="Country"
    :options="$countries"
    :value="old('country', $user->country)"
/>
```

Pick several by adding `:multiple="true"` — chosen values become removable chips
inside the field:

```blade
<x-ds::combobox name="tags" label="Tags" :options="$tags" :value="$selected" :multiple="true" />
{{-- posts tags[]=a11y&tags[]=billing --}}
```

## Props

| Prop | Type | Default | What it does |
|---|---|---|---|
| `name` | string | *required* | Submitted field name. `[]` is appended for you when `multiple`. |
| `options` | array | `[]` | `value => label`. The whole list — filtering is client-side. |
| `value` | string\|array | — | Selected value, or an array of them. |
| `label` | string | — | Field label. |
| `multiple` | bool | `false` | Pick several. |
| `required` | bool | `false` | Single-select only. Hides the clear ✕, refuses backspace-to-clear, sets `aria-required`. |
| `placeholder` | string | `Search…` | Shown when **nothing** is chosen. A chosen value is the field's text. |
| `emptyText` | string | `No matches` | Shown when the filter matches nothing. |
| `help` | string | — | |
| `error` | string | — | |
| `disabled` | bool | `false` | |

## A chosen value is text

One chosen value renders as the field's **text**, at full strength, with no
caret — because there is nothing to edit there. Click it, or press ArrowDown,
and the list opens with the label still in the field and the current value
ticked. Type, and the field switches to your query; delete back to nothing and
the label comes back.

It used to render as the `placeholder`, in muted grey with a blinking caret,
which read as "nothing chosen yet, type something" when the choice was in fact
made. `placeholder` is now only the hint shown when the field is empty.

## Clearing

A non-required single-select can be unset — by the **✕** beside the chevron, or
by **backspace on an empty query**. Both were multi-select-only before, so a
single choice was a one-way door: swappable, never clearable.

```blade
{{-- No ✕, no backspace-to-clear, and aria-required="true" --}}
<x-ds::combobox name="gate" label="Gate" :options="$gates" :value="old('gate')" :required="true" />
```

**Mark it `required` only when it is.** It removes the only way to unset a
value, and it tells assistive technology something about the form.

## Which control

| Options | Reach for |
|---|---|
| under ~15, one choice | [select](select.md) |
| under ~12, several choices | a list of [checkboxes](checkbox.md) — more scannable, and free |
| more than that, or unfamiliar values | **this** |

A combobox costs the reader a decision before they can act: they have to guess
what to type. Below about fifteen options a plain list is faster, and on a phone
the native `<select>` is faster still.

## Filtering is client-side

You pass the whole list and the browser filters it. There is **no server-side
search** — that needs a request, and a request needs a backend contract this
system deliberately doesn't have.

For a list too large to send down, you want a different component than this one.

## Keyboard

Typing filters. Arrows walk the **filtered** list, Home and End jump, Enter and
Space choose, Escape closes.

**Backspace on an empty query clears the selection** — the last chip when
`multiple`, the single value otherwise, unless the field is `required`. Without
it, undoing a selection means aiming at a 12px ✕, which on a touch screen isn't
a target.

Typing into a settled single-select starts a **new query** rather than editing
the label sitting in the field.

## With validation

```blade
<x-ds::combobox
    name="country"
    label="Country"
    :options="$countries"
    :value="old('country')"
    :error="$errors->first('country')"
    help="Where the invoice is issued from."
/>
```

## Don't

- **Don't use it under fifteen options.** See the table above.
- **Don't use it for actions.** A menu of verbs is a [dropdown](dropdown.md).
- **Don't expect server-side filtering.**
- **Don't render a chosen value as a placeholder.** It reads as an empty field.
- **Don't mark a field `required` to tidy it up.** See above.
- **Don't use `multiple` where the total is unbounded.** Twenty chips wrap the
  field to four lines and shove the page around.

More in [specs/combobox.md](../../specs/combobox.md).
