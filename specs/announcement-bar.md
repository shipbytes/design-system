# Announcement bar

A full-width banner above the app chrome: maintenance, a trial ending, a new
feature. Plain Blade; `dismissible` and `dismissKey` need Alpine and the
package's script (`dsDismiss`), and `dismissKey` writes a pre-paint check
(`Support\Dismissal`).

## Anatomy

```
┌──────────────────────────────────────────────────────────────────────┐
│ [spacer]      ⚠  Maintenance Saturday 02:00 UTC.  Learn more →    ✕  │
└──────────────────────────────────────────────────────────────────────┘
  ↑ div[role=region][aria-label], grid 1fr → 0fr when dismissed
```

- **Root**: `div[role=region]` named by `label` (or `role=alert`, see
  Accessibility), `grid w-full grid-rows-[1fr]`, `data-ds-announcement`.
  `sticky top-0 z-40` with `sticky`. A dismissible bar also carries
  `data-ds-dismissible`, `x-data="dsDismiss({ key })"` and
  `x-bind:data-ds-state`, which is `shown` or `dismissed` once the behaviour
  has started, and absent before.
- **Pre-paint check** (`dismissKey` only): a `script[data-ds-dismiss-check]`,
  the root's FIRST child, with the CSP nonce. See Behaviour.
- **Row**: `min-h-0 overflow-hidden`, the single grid row, holding the ground:
  `flex items-center gap-3 px-4 py-2.5` and the tone's surface and rule. The
  ground is inside the clipped row so a closed bar leaves no rule behind.
- **Spacer** (dismissible only): `w-7`, as wide as the ✕, so the message is
  centred on the bar.
- **Message**: `flex-1`, centred, wrapping: the icon (`size-4.5`), the slot,
  the `href` link (`actionLabel` and an arrow), the `action` slot.
- **Close control**: `button[aria-label=Dismiss][data-ds-dismiss]`,
  `x-mark` at `size-4`, `p-1.5`, `invisible` until `data-ds-state` exists on
  the root (`in-data-[ds-state]:visible`).

## Variants

| Tone | Ground | Default icon |
|---|---|---|
| neutral (default) | `bg-surface-inverse text-on-inverse` | megaphone |
| accent | `bg-accent-wash text-on-accent-tint`, `border-b border-accent/25` | information-circle |
| success | `bg-success-wash text-on-success-tint`, `border-b border-success/25` | check-circle |
| warning | `bg-warning-wash text-on-warning-tint`, `border-b border-warning/25` | exclamation-triangle |
| danger | `bg-danger-wash text-on-danger-tint`, `border-b border-danger/25` | exclamation-circle |

The washes are the alert's, at the alert's contrast. neutral is the inverse
surface because a bar above the chrome must read as separate from it.

## States

| State | Treatment |
|---|---|
| shown | as drawn |
| dismissing | `data-ds-state=dismissed`: `grid-rows-[0fr]` and `invisible`, transitioned over `duration-dialog-leave ease-leave`, `motion-safe` only |
| dismissed | zero height, `visibility: hidden`: out of the tab order and the accessibility tree |
| remembered | `hidden` and `display: none`, set by the pre-paint check before any paint |
| no script | shown, with no close control |

## Behaviour

**The pre-paint check.** With `dismissKey`, the root's first child is an
inline script that reads `localStorage[dismissKey]` and, if it is set, puts
`hidden` and `display: none` on the root. It runs as the parser reaches it:
the root exists, nothing inside it has been parsed, and nothing has been
painted, so a dismissed bar is never drawn and the page below never jumps.
Storage that throws is caught and the bar shows.

**`dsDismiss`.** On start it repeats the storage check (for a page where the
inline check was blocked by a policy without the nonce) and writes
`data-ds-state` through the view's binding. A click on any `[data-ds-dismiss]`
whose nearest `[data-ds-dismissible]` is this root sets `dsDismissed`, writes
`1` under the key when there is one, and dispatches `dismiss` from the root,
bubbling. The scope exposes `dsDismissed` only.

**Livewire.** The collapse is an Alpine binding, so a morph that rewrites the
root's attributes keeps a dismissed bar collapsed.

## Choices and why

- **An inline script as the first child, not "hidden until checked".** Hiding
  the bar from the server and revealing it once storage says so would hide it
  on every page that never runs a script, and shift the page down for every
  reader who has NOT dismissed it: most of them. A cookie would let PHP
  decide, but the package sets no cookies and reads no request. The theme
  script made the same choice for the same reason.
- **A grid row, not a measured height.** 1fr to 0fr animates without
  JavaScript measuring anything, as the accordion does, and `invisible` in
  the same transition takes the content out of the tab order once closed.
- **A landmark, not a live region.** See Accessibility.
- **No ✕ without the script.** A control that looks pressable and does
  nothing is the bug the alert's dismiss once had.
- **`dismissKey` implies `dismissible`.** A key with nothing to dismiss would
  be a mistake, never an intention.
- **The key's value is `1`, not a date.** Expiry is the host's: a new
  announcement takes a new key.

## Limits

- Dismissal lives in one browser. A reader on another device sees the bar
  again; record `dismiss` on the server for that.
- Under a policy without the nonce, a remembered bar shows until Alpine starts
  and then collapses: one frame of it, and a shift.

## Accessibility

| | |
|---|---|
| Role | `region`, named by `label`; `alert` when `tone="danger"` and `urgent` |
| Name | `label` (default "Announcement") |
| Keyboard | Tab reaches the link, the action slot and the ✕; Enter or Space on the ✕ dismisses |
| Focus | The ✕ draws the system ring. Once dismissed nothing in the bar is focusable; the browser's sequential-focus starting point stays where the bar was, so the next Tab moves on from there |
| Live | None, except `urgent`: a bar present at load is read in place, and marketing must not interrupt |
| Motion | The collapse is `motion-safe`; with reduced motion it is gone at once |

## Do not

- **Do not stack bars.** One at a time.
- **Do not mark anything but an outage `urgent`.**
- **Do not reuse a key** for a new announcement.
- **Do not rotate messages or count down** in it. Out of scope by design.
