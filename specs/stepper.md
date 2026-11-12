# Stepper

Where someone is in an ordered flow. `ds::stepper` is the list; `ds::step` is
each item. Presentation only: the host decides which step is current.

## Anatomy

```
 (✓) Account ────── (2) Plan ─────── (3) Billing ────── (4) Review
     ada@example.com    Pick what you need
  ↑     ↑                                 ↑
marker  title + description            connector (solid behind a completed step)
```

| Part | Token | Why |
|---|---|---|
| Marker, complete | `surface-inverse` + `on-inverse`, check icon | The primary button's pair: done is the heaviest thing in the row. |
| Marker, current | 2px `fg` ring on `surface`, number | Stands out by outline, not by a hue, so it holds in both themes. |
| Marker, upcoming | 1px `border-strong`, number in `fg-muted` | |
| Marker, error | `danger` + `on-danger`, "!" | |
| Title | `text-body`; semibold when current, `fg-muted` when upcoming | |
| Description | `text-meta`, `fg-muted` | |
| Connector | `fg` after a completed step, `border-strong` otherwise | The line already travelled is drawn solid. |

Markers are 28px (`size-7`). Literal classes throughout.

## Numbering

A CSS counter: the list resets `ds-step`, every item increments it, and the
number is the marker's `::before` content. A step never needs to be told its
position, which Blade could not give it anyway: the children render before the
parent's template runs. The number is decorative (the marker is
`aria-hidden`); the `ol` gives a screen reader the position.

## Orientation

`horizontal` (default): items in a row, each a marker with its text beside it
and a connector filling the rest of its share. `vertical`: a column, the
connector running down from each marker to the next, absolutely positioned so a
long description stretches it rather than the other way round.

The stepper passes `orientation` to its steps with `@aware`.

## Narrow widths

A horizontal stepper narrower than **40rem** (`sm`, measured on the stepper:
the root is `@container`, the variants `@min-[40rem]:`) keeps its markers and
connectors and:

- takes each title and description off the screen with `sr-only`, NOT
  `hidden`, so the list still reads in full;
- prints, `aria-hidden`, "Step 2 of 4" and the current title under the row.

The count and position come from the rendered slot. By the time the stepper's
template runs its steps are finished HTML, so it reads back the
`data-ds-step` and `data-ds-step-title` attributes each step writes on its list
item. Only steps write them. With no `current` step the first `error` is
treated as current; with neither, the line counts the complete ones ("All 4
steps complete").

A container query rather than the viewport's `sm:` because a stepper lives
inside things: a 4-step flow in a narrow modal on a desktop has to collapse,
and one in a wide panel on a tablet does not. Container queries are supported
everywhere the rest of this system's CSS runs; without them the `@min-` rules
never apply and the stepper stays collapsed, which is the safe failure.

A container cannot take its width from its content, so the root is `w-full`.

## Links

A step with `href` renders its marker and text inside one anchor. Without
`href` the same element is an anchor with no `href`, which is a plain inline
element: no role, no tab stop. One element either way keeps the markup free of
an `@if` inside an opening tag (the Livewire marker trap), and below 40rem the
link is still the visible marker, so its focus ring shows. `href` on the
current step is ignored: a link to where the reader already is costs a tab stop
and goes nowhere, the breadcrumb's rule.

## Accessibility

| | |
|---|---|
| Role | `list` (an `ol`) with `listitem`s; the `ol` is named by `label` |
| Current | `aria-current="step"` on the current item |
| Status | in words, `sr-only`, after each title: "completed", "current step", "not started", "needs attention" |
| Keyboard | none of its own. Linked steps are links (Tab, Enter); the rest are not focusable. |
| Focus | links show `focus-visible` with the focus-ring token, offset 2px |

"Current step" is spoken as well as `aria-current`, because not every screen
reader announces `aria-current` on a list item that is not focused.

## Do not

- **Do not hold state in it.** It renders what it is told; the flow decides.
- **Do not use it for unordered navigation.** That is tabs or a nav.
- **Do not hide titles with `hidden`.** The collapse is visual only.
- **Do not let an upcoming step link somewhere it cannot be used yet.**
