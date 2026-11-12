# Button group

Related `ds::button`s joined into one shape. Plain Blade and CSS, backed by a
class (as `ds::scripts` is) for a reason that is not visual.

## Anatomy

```
┌──────┬────────┬───────┐
│ Left │ Center │ Right │   ← div[role=group][aria-label] > ds::button × n
└──────┴────────┴───────┘
┌──────────────┬───┐
│ Save changes │ ⌄ │        ← split: ds::button, then ds::dropdown > trigger ds::button
└──────────────┴───┘
```

- **Group**: `div[role=group]` named by `label`, `inline-flex` (or
  `flex-col` vertical). It sets two custom properties on each direct child:
  `--ds-join-start` and `--ds-join-end`, zero, except the control radius on the
  first child's start and the last child's end.
- **Button**: reads those properties for its corners
  (`rounded-s-(--ds-join-start) rounded-e-(--ds-join-end)`, or `-t`/`-b`
  vertical) only when it is in a group. Properties inherit, so a button inside
  a dropdown wrapper gets the wrapper's ends.

## How the group reaches the buttons

`ds::button` reads `@aware(['buttonGroup'])`, an array of size, variant and
orientation. `buttonGroup` is a public property of
`Shipbytes\BladeUi\View\Components\ButtonGroup`: a key no caller writes, so only
a button inside a group ever sees one.

An anonymous group would have had to offer plain `size` and `variant`, and
`@aware` hands a child the nearest ancestor holding a key, whatever component
that is: a button in `ds::modal size="lg"` would have turned large. The test
renders that case.

A button's own `size` or `variant` wins over the group's; with neither, it is
`md` and `primary` as always.

## Variants

| Group variant | Join |
|---|---|
| `secondary` (default), `ghost` | Neighbours overlap by 1px (`-ms-px`, or `-mt-px` vertical), so two borders are one line |
| `primary`, `danger` | `gap-px`: a hairline of the ground between filled buttons, which have no border to share |

## States

Each button keeps its own states. Hover and focus-visible add `z-10`, so the
hovered or focused button's border and ring sit above its neighbours'.

## Choices and why

- **Default variant `secondary`.** A row of filled buttons reads as one heavy
  block; the outlined group is the usual one, and a split primary button sets
  `variant="primary"` on purpose.
- **The split button needs no prop.** The dropdown's wrapper is the group's
  last child, so its trigger inherits the end radius, and `@aware` reaches
  through the dropdown to the button.
- **Joins by custom property**, not by `first:`/`last:` on the button: the
  button cannot know it is first when a dropdown wraps it.
- **No arbitrary `[&>*]` variants.** An ampersand or angle bracket in a class
  is entity-escaped in the rendered page, which breaks any Tailwind that scans
  rendered HTML; `*:` variants say the same thing safely.

## Accessibility

| | |
|---|---|
| Role | `group` |
| Name | `label` (`aria-label`) |
| Keyboard | Each button is its own Tab stop; nothing added |
| Focus | Each button's own ring, lifted above its neighbours |

## Do not

- **Do not use for a choice.** Pressed state belongs to toggle-group or
  segmented-control.
- **Do not set a filled variant on single buttons** of an outlined group; the
  join is decided by the group's variant.
- **Do not read `size` through `@aware` in a button.** That is the leak this
  component's class exists to avoid.
