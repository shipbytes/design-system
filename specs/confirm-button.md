# Confirm button

A destructive ds::button that asks in place before it acts: "Are you sure?
Cancel Delete". Needs Alpine and the package's script (`dsConfirm`).

The docs page carries the one table that chooses between the four guards
(hold button, confirm button, confirm popover, modal): see
[which confirmation do I use?](../docs/components/confirm-button.md#which-confirmation-do-i-use)

## Anatomy

```
at rest:   [ Delete ]                            ← ds::button, data-ds-confirm-trigger
asking:    Are you sure?  [ Cancel ] [ Delete ]  ← span[data-ds-confirm-ask]
           [sr-only live region]
```

- **Root**: `span[data-ds-confirm]`, `inline-flex items-center align-middle`,
  `x-data="dsConfirm({ timeout, loading })"`. Takes the host's `class`.
- **Trigger**: ds::button with `variant` (default `danger`; `secondary` and
  `ghost` in the danger tone), `size`, `type="button"`,
  `x-show="! $data.dsAsking"`.
- **Ask face**: `inline-flex items-center gap-2 whitespace-nowrap`,
  `style="display: none"` from the server, `x-show="$data.dsAsking === true"`,
  fading in over `duration-popover-enter`, `motion-safe` only. Holds:
  - the question, `font-medium text-fg` in the button's text size;
  - Cancel, ds::button `secondary`, `data-ds-confirm-cancel`, disabled while
    busy;
  - the confirm control, ds::button `danger`, `data-ds-confirm-confirm`,
    `type` from the prop (default `submit`), every host attribute but
    `class`, and a spinner shown while busy.
- **Live region**: `sr-only`, `aria-live="polite"`, the question while asking
  and empty otherwise.

State is read through `$data.` so that in a host without the script no
binding throws, and the server-rendered faces stay as they are.

## States

| State | Treatment |
|---|---|
| at rest | the trigger alone |
| asking | the ask face; focus on Cancel; the live region says the question |
| busy (`loading`, after confirming) | spinner, `aria-busy` and `aria-disabled` and `cursor-wait` on the control, Cancel disabled; Escape and the timeout ignored |
| no script | the trigger alone, inert |

## Behaviour (`dsConfirm`)

- A click on the trigger asks: `dsAsking`, focus to Cancel once it is shown,
  and the timer.
- **Timer**: `timeout` seconds (default 5; 0 never). Stopped while the pointer
  is over the root or focus is in it; started again IN FULL once both have
  left. When it runs out the question goes away, `cancel` is dispatched, and
  focus is not moved.
- **Cancel** and **Escape** (on the root, while asking and not busy; stopped
  only then) put it back, dispatch `cancel`, and focus the trigger.
- **The confirm control**: a click is caught by a listener CAPTURING on the
  root. While busy, or not asking, it is swallowed (default prevented,
  propagation stopped, so neither the form nor the host's `@click` /
  `wire:click` hears it). Otherwise `confirm` is dispatched from the control
  and the click goes on untouched: the host's listeners, then the form. With
  `loading`, `dsBusy` is set and stays set; without, the component is put back
  at rest after the click, focus to the trigger if it was inside.
- A `pageshow` from the back-forward cache puts it back at rest, unbusy.
- The scope exposes `dsAsking` and `dsBusy` only.

## Choices and why

- **The host's attributes go on the confirm control.** The action is the
  host's; putting it on the control means nothing the host wrote can run from
  the first press, by construction rather than by a check.
- **`type="submit"` by default.** The brief's primary use is a delete form,
  and a submit is what lets `@method('DELETE')` and CSRF travel. The cost is
  the inside-another-form case, which takes `type="button"` and is in the
  docs' Don't list.
- **Hidden from the server, shown by the script.** So the failure without the
  script is "nothing happens", never "it deleted on one click".
- **Focus to Cancel.** The safe choice, and the one Enter would press.
- **Polite live region, no group label.** The question is announced once, on
  asking; a labelled group around the buttons would announce it a second time
  as focus enters.
- **It grows, it does not reserve.** Reserving the question's width at rest
  would leave a gap beside every Delete in a table. Growing toward the inline
  end, never taller, is the documented behaviour; an end-aligned row absorbs
  it, and the confirm popover is the answer where nothing may move.
- **The timer restarts rather than resumes.** Whoever moved away may have
  been reading the question.

## Limits

- `loading` ends only with the page or the component. An action that fails
  and leaves the button on the page keeps the spinner; use a
  [confirm popover](confirm-popover.md) with `open`, which the host can close.
- Without the script, the button does nothing at all.

## Accessibility

| | |
|---|---|
| Role | `button` (three of them) |
| Name | the trigger's `label`; Cancel's `cancelLabel`; the control's `confirmLabel` |
| Keyboard | Enter or Space on the trigger asks; Tab between Cancel and the control; Enter or Space on either; Escape cancels |
| Focus | to Cancel on asking; back to the trigger on Cancel, Escape and a confirmation without `loading`; untouched by the timeout |
| Live | the question, politely, on asking |
| Busy | `aria-busy="true"` and `aria-disabled="true"` on the control |
| Motion | the ask face fades in, `motion-safe` only |

## Do not

- **Do not leave `type` as submit inside a form it must not submit.**
- **Do not put it before neighbours that must not move** in a start-aligned
  row.
- **Do not use it for the irreversible and important.** Use a modal or a
  confirm popover with type-to-confirm.
