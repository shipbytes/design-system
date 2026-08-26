# Radio

One of a set. Covers `radio` and `radio-group`.

![Radio](../images/radio.png)

If the options are not mutually exclusive, they are [checkboxes](checkbox.md).

## Use it

**Always wrap radios in a `radio-group`.**

```blade
<x-ds::radio-group label="Billing period" help="You can change this later.">
    <x-ds::radio name="period" value="monthly" label="Monthly" :checked="$period === 'monthly'" />
    <x-ds::radio name="period" value="yearly" label="Yearly" help="Two months free." />
</x-ds::radio-group>
```

## `radio-group` props

| Prop | Type | Default | What it does |
|---|---|---|---|
| `label` | string | *required* | The question. Becomes the `<legend>`. |
| `help` | string | — | Guidance for the group. |
| `error` | string | — | Validation message for the group. |

## `radio` props

| Prop | Type | Default | What it does |
|---|---|---|---|
| `label` | string | *required* | |
| `help` | string | — | A footnote for this option. |
| `checked` | bool | `false` | |
| `disabled` | bool | `false` | |

`name` and `value` pass through. **Every radio in a group shares one `name`** —
that is what makes them a group to the browser, and it is what makes the arrow
keys work for free.

## The group label is not optional

A radio announces its own label and nothing else, so "Monthly" is read without
ever saying *what is being chosen*. The `<legend>` is what a screen reader
repeats as the group is entered, and it is the only thing that turns four
options into a question.

## The error belongs to the group

"Choose a plan" is about the question, not about the first option:

```blade
<x-ds::radio-group label="Plan" :error="$errors->first('plan')">
    …
</x-ds::radio-group>
```

## Don't

- **Don't ship a group with nothing selected and no "none" option** unless the
  question is genuinely optional. A radio group cannot be un-chosen once picked.
- **Don't use radios for more than about seven options.** That is a
  [select](select.md).
- **Don't put radios outside a `radio-group`.**
- **Don't disable the whole group to mean "not applicable".** Hide it, or say why.

More in [specs/radio.md](../../specs/radio.md).
