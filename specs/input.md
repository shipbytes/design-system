# Input

A Catalyst-style double layer. In the app this is a ~40-line class string pasted
at roughly 40 sites; it is one component.

## Why two layers

The visible field is built from two pseudo-elements rather than borders on the
control itself:

- **`::before`** — inset by 1px, carries the white fill and `shadow-raised`, with a
  radius of `calc(radius-control - 1px)` so it nests cleanly inside the outline.
- **`::after`** — sits at `inset: 0` and carries the focus ring, drawn
  **inside** via `ring-inset`.

Drawing the ring inward is the point: the box never changes size on focus, so
nothing reflows and adjacent fields do not shift. A plain `outline` would.

## Anatomy

```
Label                       ← body, medium, fg
┌──────────────────────────┐
│ [icon]  value / placeholder │   height = control token
└──────────────────────────┘
Helper text                 ← meta, fg-muted
```

Helper text is **replaced by** the error, never stacked with it. Two lines of
guidance under one field is one line too many.

## States

| State | Treatment |
|---|---|
| default | `border`, white fill, `shadow-raised` |
| hover | border to `fg/20` |
| focus | 2px inset `focus-ring`; border unchanged |
| filled | identical to default — a filled field is not a state |
| error | border `danger`, message below in `danger` with a leading icon |
| disabled | `surface-subtle` fill, `fg-subtle` text, no shadow |

Dark drops the shadow entirely — the border carries the edge instead.

## Sizes

Same tokens as [button](button.md): `control-lg` below the `sm` breakpoint,
`control-md` above it. **16px text below `sm` is not a style choice** — iOS
zooms the page when a field under 16px receives focus.

## Types

`select` shares the shell exactly; only the caret differs, and it is
`pointer-events: none` so clicks reach the control. `textarea` keeps the border
and radius, grows its padding, and lets height run free.

Checkbox, radio and switch are separate specs. The rule between them: a
**switch commits immediately**, a **checkbox waits for Save**. Picking the wrong
one lies to the user about whether their change persisted.

## Accessibility

- Every field has a real `<label for>`. Placeholder is not a label — it vanishes
  on input and fails contrast.
- Error text is tied to the field with `aria-describedby`, and the field carries
  `aria-invalid="true"`.
- The error must say what to do ("Title must be at least 3 characters"), not
  just what is wrong ("Invalid").
- **Known deviation:** the resting outline is ~1.1:1 against the surface, below
  the 3:1 WCAG 1.4.11 asks of a component boundary. Inherited from Catalyst,
  where affordance comes from the fill and shadow. The focus ring does pass, on
  every surface. Recorded in [color.md](color.md) so it stays a decision.

## Porting note

The two-pseudo-element construction is pure CSS and carries across frameworks
unchanged. What does not carry is the binding — `wire:model.live.debounce.300ms`
on a search field and `wire:model.blur` on a date encode a per-field network
policy that has no equivalent outside Livewire, and has to be rebuilt as
explicit client state.
