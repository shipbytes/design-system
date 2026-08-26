# Switch

A setting that takes effect. Not a [checkbox](checkbox.md).

## Switch or checkbox

| | Switch | Checkbox |
|---|---|---|
| Takes effect | immediately, or on save as a *setting* | when the form is submitted |
| Reads as | on / off | selected / not selected |
| Label sits | to the left, control on the right | to the right of the box |
| Announces | "on", "off" | "checked", "unchecked" |

**"Unchecked" for a setting that is simply off reads as a form the reader failed
to fill in.** That is why `role="switch"` exists, and it is the difference this
component is for.

The label-left/control-right order is not decoration either: a switch is one of a
list of settings and the reader scans the names down the left edge. A checkbox is
a choice attached to its own sentence, so it leads with the box.

## Anatomy

```
Email notifications              ( ●——)
At most one a month.
```

| Part | Token | Why |
|---|---|---|
| Track off | `fg/20` | Translucent, so one value works on the card and on the ground, in both themes. |
| Track on | `accent` | The same "on" colour the checkbox uses. Consistency beats novelty here. |
| Knob | `white` + `shadow-raised` | Light in both themes. The shadow is what separates it from a pale off-track. |

Construction is the checkbox's: the real `<input>` styled with
`appearance-none` **is** the track, and the knob is a sibling. See
[checkbox.md](checkbox.md#the-native-input-styled) for why the hidden-input
substitution is not used.

The knob is a sibling rather than a `::before` because **a pseudo-element does
not render on an `<input>` in every browser** — and finding that out means
finding it in the one browser that does not.

## `submitUnchecked`

On by default, and it is the prop most likely to matter.

**An unchecked checkbox sends nothing.** Without a hidden `0` alongside it, a
setting can be turned on and never turned off again through a plain form: the
request simply has no key for it, and the controller reads that as "unchanged".

```blade
<x-ds::switch name="notifications" label="Email notifications" :checked="$user->notifications" />
{{-- posts notifications=0 when off, notifications=1 when on --}}
```

Turn it off when something else owns the value — `wire:model`, or a JSON payload
assembled in JavaScript — where a stray `0` in the form data would be noise.

## Accessibility

- `role="switch"` on a real checkbox input. The states are the same; the words a
  screen reader says are not.
- The label is a real `<label for>`, so the text is a click target.
- `help` is wired with `aria-describedby`.
- `disabled` is the real attribute, so the field genuinely does not submit —
  which is also why the hidden `0` is skipped when disabled.

## Do not

- **Do not use one inside a form the reader must submit** without making it clear
  the change is not yet saved. A switch *looks* like it already took effect.
- **Do not use it for a choice between two named things.** "Light / Dark" is a
  radio pair or a segmented control; a switch's off state has no name.
- **Do not put a switch in a table row** as a bulk action. It reads as applied
  immediately and there is no undo.
- **Do not omit the label.** There is nothing else to announce.
