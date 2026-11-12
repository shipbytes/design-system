# Swap button

A ds::button whose face swaps to confirm what it did: "Save" to "Saved",
"Follow" to "Following". The swap mechanics are ds::copy-button's, shared.

## Anatomy

```
┌──────────────────┐
│  +  Follow       │   ← ds::button[data-ds-swap-button].group/swap
└──────────────────┘     one grid cell: face at rest, face swapped
  [sr-only role=status]   ← sibling of the button, inside the scope wrapper
```

- **Scope wrapper**: `span.contents[data-ds-swap]` carrying `x-data` for
  `dsSwap`, so the button lays out in the host's row as a ds::button would.
- **Button**: ds::button with `variant` (default `secondary`), `size`, and every
  host attribute (`type`, `href`, `wire:click`). It carries `data-ds-swapped`
  when swapped, rendered by the server and bound to `dsSwapped`.
- **Faces**: the default slot and the `swapped` slot, each an inline-flex span
  (`gap-2`, icon and words), stacked in one grid cell with
  `justify-items-center` by the swap recipe (`partials/swap.php`,
  `Support\Swap::recipe()`). The cell is as wide as the wider face from the
  first paint.
- **Status**: `sr-only`, `role="status"`, outside the button. Empty at first
  paint; after each change, the text of the face now showing.

## States

| State | Treatment |
|---|---|
| at rest | Default face; swapped face `invisible opacity-0 translate-y-1`, `aria-hidden`, `inert` |
| swapped | The reverse: rest face fades up and out, swapped face fades up in |
| swapped from the server (`swapped`) | Drawn swapped before Alpine; with `revert` > 0 the timer starts on init |
| toggle (`revert="0"`, swap on click) | As above, plus `aria-pressed` |
| disabled, hover, focus, loading | As ds::button |

The swap is `motion-safe:` opacity, translate and visibility over
`duration-popover-enter ease-enter`. Visibility is in the transition so the
leaving face stays visible until its fade ends. With reduced motion it swaps at
once.

## Behaviour (`dsSwap`)

| Option | From | Does |
|---|---|---|
| `swapped` | `swapped` attribute | Initial `dsSwapped` |
| `revert` | `revert` prop, default 2000 | ms until it swaps back by itself (`shared/flag.js` `timedFlag`, as dsClipboard); 0 never |
| `click` | `swapOnClick`, default true unless `event` | A press swaps it; with revert 0, toggles it. A press while swapped restarts the time |
| `event` | `event` | A window listener: the event swaps it |

The press is never prevented: a submit still submits, a link still navigates.
The status region is written from a `$watch`, so the first paint says nothing.

`swapped` is read from the attribute bag BEFORE `@props`: a named slot called
`swapped` would otherwise overwrite a prop of the same name.

## Accessibility

| | |
|---|---|
| Role | `button` (or link, with `href`), as ds::button |
| Name | The face showing. A toggle: the face at rest, by `aria-labelledby`, whatever is showing |
| State | `aria-pressed` on a toggle only |
| Hidden face | `aria-hidden="true"` and `inert`, and `invisible` once faded |
| Announcement | Polite `role="status"` outside the button |
| Keyboard | Enter and Space, as any button |
| Focus | Stays on the button through the swap |

The APG button pattern: a toggle button's name must not change with its state,
or "Following, pressed" reads as two claims. The faces still change for sight.

## Do not

- **Do not write a third swap.** Faces go through the swap recipe; the timer is
  `timedFlag`.
- **Do not swap before the work is done.** Use `event` for a save that can fail.
- **Do not put the status inside the button.**
- **Do not let the button change width.** That is what the one cell is for.
- **Do not name the slot's prop.** `swapped` is a slot and an attribute; there
  is no `@props` entry for it.
