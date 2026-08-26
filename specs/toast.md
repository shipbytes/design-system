# Toast

A brief report that something already happened. Covers `toast` and
`toast-region`.

## Toast or alert

| | Toast | [Alert](alert.md) |
|---|---|---|
| Sits | over the page, fixed | in the flow, with the thing it is about |
| Lasts | seconds | until the reason is gone |
| Is about | something that just happened | the state of the page |
| Announces | `polite` | `polite`, or `assertive` for `danger` |

**Anything the reader must act on is not a toast.** A toast is missable by
design: it is on screen for a few seconds, it is somewhere their eyes are not,
and on a phone it may be under a thumb. A failure that needs a retry belongs in
an alert next to the thing that failed.

## The region is always rendered

`toast-region` goes in the layout **once**, and stays there empty.

A live region only announces content that arrives *after* it is already in the
document. Render the region at the same moment as the first toast and **the first
toast is silent** — which is the one a reader most needs to hear, and the failure
is invisible unless you are listening.

```blade
{{-- in the layout, always --}}
<x-ds::toast-region position="bottom-right" />
```

`position`: `bottom-right` (default) | `top-right` | `bottom-center` |
`top-center`.

The region is `pointer-events-none` and each toast is `pointer-events-auto`, so
the empty container never swallows clicks on the page underneath — an invisible
full-width strip that eats clicks is a bug nobody attributes to the toast system.

## `polite`, never `assertive`

A toast reports something that already happened. `assertive` interrupts whatever
the screen reader is currently saying, and interrupting someone to tell them a
thing they already did worked is exactly the rudeness `polite` exists to avoid.

Anything that genuinely must interrupt is not a toast — it is an alert or a
modal.

## Surface, not wash

A toast is `surface` with a `border` and `shadow-float`, unlike the alert's
tinted wash.

It floats over arbitrary content instead of sitting in the flow, so it has to
paint an opaque ground — a wash over an unknown background is unreadable at the
one moment it matters. **The tone lives in the icon alone**, which is enough at
this size and stops four stacked toasts from becoming a colour chart.

`tone`: `neutral` (default) | `accent` | `success` | `warning` | `danger`.

## What the host owns

Everything that moves: the list, the timers, the removal. The component holds no
state.

`dismiss` takes an Alpine expression and renders the close control. A toast with
no auto-dismiss and no `dismiss` is permanent, which is not a toast.

## Timing, since the component cannot enforce it

- **Around 5 seconds** for a plain confirmation.
- **Longer, or never, when the toast has an action.** "Undo" on a 3-second timer
  is a button that is gone before the reader has finished reading the sentence
  offering it.
- **Pause the timer on hover and on focus**, or a reader who moves to click Undo
  watches it disappear under the pointer.

## Do not

- **Do not stack more than about three.** Four toasts is a log, and a log belongs
  on a page.
- **Do not put a form in one.** It can vanish mid-typing.
- **Do not use one for a validation error.** The field needs the message; see
  [input.md](input.md).
- **Do not use one for anything the reader has to keep.** There is no history.
- **Do not put the only copy of an "Undo" behind a timer** without a permanent
  path to the same action.
