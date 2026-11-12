# Hold button

A ds::button confirmed by holding it, or for those who cannot hold, by
pressing it twice. Needs Alpine and the package's script (`dsHold`).

## Anatomy

```
[ Hold to delete      ]   ← ds::button secondary, data-ds-hold, type=button
[█████████ to delete  ]   ← the fill: the words again, in the fill colours,
                            clipped to --ds-hold-rest from the inline end
[sr-only live region]
```

- **Scope wrapper**: `span.contents`, `x-data="dsHold({ duration, submit })"`.
- **Button**: ds::button `variant="secondary"` with `tone` (`danger` or
  `neutral`) and `size`, `type="button"` always, `data-ds-hold`, every host
  attribute, plus `overflow-hidden select-none touch-none
  [-webkit-touch-callout:none]`.
- **Faces**: `label` and `armedLabel` in one grid cell, centred, the hidden
  one `invisible` through the object form of `:class` on `$data.dsArmed`. The
  button is as wide as the longer from the first paint.
- **Fill**: `span[data-ds-hold-fill]`, `aria-hidden`, `absolute inset-0`, the
  same two faces in `bg-danger text-on-danger` (or the inverse surface for
  neutral), clipped by `clip-path: inset(0 var(--ds-hold-rest, 100%) 0 0)`
  (mirrored under `rtl:`). The behaviour writes `--ds-hold-rest` on the
  button: 100% at rest, 0% full.
- **Live region**: `sr-only`, polite, `armedLabel` while armed.

## States

| State | Treatment |
|---|---|
| at rest | outline, `label` |
| holding | the fill sweeping in from the inline start over `duration` |
| rewinding | the fill going back, three times as fast; at once with reduced motion |
| armed | `armedLabel` in place of `label`, announced |
| confirmed (`submit`) | full, `aria-disabled="true"`, refusing another hold until a back-forward-cache restore |
| confirmed (button) | full for 150 ms, then rewinds |

## Behaviour (`dsHold`)

- **Pointer**: a primary-button pointerdown captures the pointer and starts the
  fill. pointerup, pointercancel, the window losing focus, or the pointer
  leaving the button's box rewind it. A long press's `contextmenu` is
  prevented while holding.
- **Keys**: Space and Enter have their defaults prevented on keydown and keyup
  (no native click), and a repeat is ignored. Keydown starts the fill; keyup
  before it is full rewinds and ARMS; keyup while armed confirms.
- **Clicks**: every click on the button is caught by a listener capturing on
  the wrapper and swallowed (default prevented, propagation stopped). A click
  that a pointerdown of ours began is only swallowed. A click no press began
  (assistive technology, voice control, a switch) ARMS, or confirms when
  armed.
- **Disarming**: focus leaving the button, or Escape (stopped only when it
  disarmed).
- **Confirming**: a form that would refuse the submit gets `reportValidity()`
  and nothing is confirmed. Otherwise `confirm` is dispatched from the button,
  and in a task of its own (a `click()` made while a click is being dispatched
  is ignored) the button is clicked once with the swallow lifted, with
  `type="submit"` for the duration of that call when `submit` is set.
- **Reduced motion**: the painted fill is quantised to quarters; the rewind is
  immediate.
- The scope exposes `dsArmed` only.

## Choices and why

- **Two presses for keyboard and assistive technology; never for a mouse.**
  Holding is not available to everyone (the spirit of WCAG 2.5.1 and 2.5.6):
  browse-mode screen readers, voice control and switch access deliver one
  synthetic click. Those clicks, and keyboard presses let go early, arm the
  button and say "Press again to confirm"; the next one confirms. A mouse
  click let go early only rewinds, because two quick mouse clicks are exactly
  the slip the hold exists to catch. Keyboard users can also simply hold Space
  or Enter.
- **Armed until focus leaves, with no timer.** A timer would expire while a
  screen reader was still reading the announcement; focus leaving is the
  reader's own signal that they moved on.
- **One real click on confirmation.** A `@click` or `wire:click` then runs
  exactly once, and a submit carries the button as the submitter, name and
  value included, through the browser's own path.
- **`type="button"` from the server, submit for one call.** So no single press
  can submit, without the script or with it, and a Livewire morph that resets
  attributes cannot leave a submitter behind.
- **The words drawn twice.** A bar behind the words would leave them
  unreadable where the fill's edge crosses them; a clipped second copy in the
  fill's colours keeps both halves legible.
- **The instruction is a prop.** The brief's rule, and the only way to be sure
  every hold button says how it works.

## Limits

- Without the script a host `@click` or `wire:click` on the button still runs
  on one click: Alpine and Livewire bind it themselves. `@confirm` and
  `type="submit"` stay inert. The docs say so.
- A pointer user who can neither hold nor use a keyboard has no two-step
  path. Assistive technology that synthesises clicks does.

## Accessibility

| | |
|---|---|
| Role | `button` |
| Name | `label`, then `armedLabel` while armed |
| Keyboard | Space or Enter held confirms; pressed twice confirms; Escape disarms |
| Focus | stays on the button throughout; the system ring as ds::button |
| Live | `armedLabel`, politely, on arming |
| Motion | the fill is the progress indicator; with reduced motion it moves in quarter steps and rewinds at once |

## Do not

- **Do not use it for the irreversible and important.**
- **Do not set `duration` far below a second.**
- **Do not write the instruction beside it.**
- **Do not hang the action on `wire:click`** where the script might be
  missing; listen for `confirm`.
