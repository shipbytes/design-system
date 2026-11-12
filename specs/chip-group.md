# Chip group

Pressable filter pills over native radios or checkboxes, or links. Plain
Blade and CSS: no behaviour.

## Anatomy

```
Status                                   ← legend (or sr-only with hideLabel)
( ✓ Open 12 )  ( Pending 5 )  ( Closed ) ← chips: label > sr-only input + check + icon + words + count
```

- **Group, input mode**: `fieldset.min-w-0`, `legend`, then a wrapping flex
  row of chips. Host attributes land on the fieldset, except the model.
- **Group, link mode**: `nav[aria-labelledby]`, a label `span`, the same row.
- **Chip, input mode**: `label[data-ds-chip=input]`, a rounded-full pill,
  holding `input.peer.sr-only` (radio, or checkbox posting `name[]`), the
  check cell, the optional icon (micro), the words, the optional count.
- **Chip, link mode**: `a[data-ds-chip=link]` with the same children; no
  `href` when disabled.
- **Check cell**: `overflow-hidden`, `w-0`, opening to the icon's width plus
  a 4px gap (`w-5`; `w-4.5` at sm) when chosen.

## How the group reaches the chips

A component's slot renders before the component's own view, so the group
cannot pass data down after the fact.

- `name`, `multiple` and `size` reach the chips through `@aware`.
- The group reads the rendered slot: the `data-ds-chip` markers tell it
  which mode it is in, and it throws when both are present.
- Each input carries a `data-ds-chip-input` hook, which the group replaces
  with the host's `wire:model*` / `x-model*` attributes (or nothing), so the
  binding lands on every input and the hook never reaches the page.

## States

| State | Treatment |
|---|---|
| resting | `border-border bg-surface text-fg-body` |
| hover | `border-fg/20 bg-surface-subtle` |
| chosen | `border-accent bg-accent-tint text-on-accent-tint`, check open |
| focus-visible | `outline-2 outline-offset-2 outline-focus-ring` on the pill (`has-focus-visible:` for inputs) |
| disabled | `bg-surface-subtle text-fg-muted` (the muted text of a disabled radio or checkbox label), `cursor-not-allowed`; chosen and disabled adds `border-border-strong`, check still open |
| link active | the chosen look, written out; `aria-current="true"` |

The colour change is `motion-safe:transition-colors`, the check
`motion-safe:transition-[width]`, both `duration-popover-enter ease-enter`.

## Sizes

| Size | Height | Padding | Text | Icons |
|---|---|---|---|---|
| sm | 28px | `px-2.5` | `text-meta` | 14px |
| md | 32px | `px-3` | `text-meta` | 16px |
| lg | 40px | `px-4` | `text-body` | 16px |

## Choices and why

- **Pills, not a segmented control.** They wrap, each stands alone, and the
  same look serves one-of and any-of.
- **The input is `sr-only`, not `hidden`.** It keeps focus, arrow keys and
  validation; the label carries the look through `has-checked:`.
- **Mixing modes throws** rather than rendering a group half of which posts
  and half of which navigates.
- **`label` is required**, visible by default; `hideLabel` keeps it as the
  name only.

## Accessibility

| | |
|---|---|
| Role | `group` (fieldset) of radios or checkboxes; or `navigation` of links |
| Name | The legend, or the nav's label; each chip its words and count |
| State | Native `checked`; `aria-current="true"` on the active link; `aria-disabled` on a disabled link |
| Keyboard | Radios: arrows move and choose, skipping disabled. Checkboxes: Tab and Space. Links: Tab and Enter |
| Focus | Ring on the pill |
| Not colour alone | The check |
| Motion | Width and colour transitions, `motion-safe` only |

## Do not

- **Do not use for actions.** That is a button.
- **Do not add script.** The inputs are the state.
- **Do not put the binding on the chips one by one** when the group can carry
  it; they would drift.
