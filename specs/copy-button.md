# Copy button

A ds::button that copies a value and confirms in place.

## Anatomy

```
┌───────────────┐
│ ⧉  Copy       │   ← ds::button; icon and label each a two-face grid cell
└───────────────┘
  [sr-only live region]   ← sibling of the button, inside the scope wrapper
```

- **Scope wrapper**: a `span.contents` carrying `x-data` for `dsClipboard`
  (`{ text }` or `{ target }`), so the button lays out in the host's row as a
  plain ds::button would.
- **Button**: ds::button with the host's `variant` (default `secondary`),
  `size`, `iconOnly`, and every host attribute.
- **Icon cell**: `clipboard-document` and `check`, stacked in one grid cell by
  the swap recipe (`partials/swap.php`), which ds::swap-button shares.
- **Label cell**: `label` and `copiedLabel`, stacked the same way. The cell is
  as wide as the longer one, so a copy never changes the button's width.
- **Live region**: `sr-only`, `aria-live="polite"`, empty until a copy, then
  `copiedLabel` for the same 1.5 s.

## States

| State | Treatment |
|---|---|
| idle | Clipboard icon, `label` |
| copied (1.5 s) | Check icon, `copiedLabel`, the live region says `copiedLabel` |
| failed | Unchanged; `copy-failed` dispatched from the button, bubbling |
| disabled, hover, focus | As ds::button |

The faces swap by the swap recipe: the button is `group/swap` and carries
`data-ds-swapped` while `copied`, bound from the scope; each face's classes read
it (`group-data-ds-swapped/swap:`), so the script adds no class. They crossfade
with a short slide, `motion-safe:` over `duration-popover-enter`; with reduced
motion they change at once. The 1.5 s is `shared/flag.js` `timedFlag`, the
timer ds::swap-button uses too.

## Behaviour (`dsClipboard`)

`copy()` copies `text`, or reads `target` at the moment of the press: the
element with that id, else the first match for it as a selector; its `value`
for an input, textarea or select, else its `textContent`, trimmed. A target not
on the page, or an invalid selector, fails the copy. The view's handler is
`copy().then((ok) => ok || $dispatch('copy-failed'))`: dispatched from the
button, so a listener written on the component tag hears it.

## Accessibility

| | |
|---|---|
| Role | `button`, `type="button"` |
| Name | The visible face of the label; `aria-label` = `label` when `iconOnly` |
| Announcement | Polite live region, outside the button |
| Icons | `aria-hidden` |
| Keyboard | Enter and Space, as any button |
| Focus | Stays on the button through the copy and the swap back; the fallback copy hands focus back after borrowing it |

The hidden label face is `aria-hidden` and `inert`, and `visibility: hidden`
once faded, so the name is never "Copy Copied", even mid-fade.

## Do not

- **Do not report success that did not happen.** `copied` goes up only when
  the write resolved.
- **Do not let the button change width.** That is what the stacked faces are
  for; a label swapped by `x-text` would.
- **Do not put the live region inside the button.**
- **Do not rely on it alone.** The value stays visible and selectable.
