# Pull to refresh

Pull a list down at its top to refresh it, on a touch screen, with a Refresh
button for everyone else.

## Two ways in, one refresh

- **The gesture is touch only.** A finger pulling down while the box (or the
  page) is at its top brings the content down after it, against a resistance
  that grows (`2T(1 − e^(−d/2T))`, `T` the threshold: quick at first, never
  past twice the threshold), and fills a `ds::progress` ring on the way to
  `threshold`. Let go past it to refresh; short of it, it springs back.
- **The Refresh button**, for a mouse, a keyboard, a switch, a screen reader:
  the same refresh. Without the script it is what it is: a link (`href`) or a
  submit button for the form it is in. `button="false"` leaves it out; only
  do that when the page has another way.

## Refreshing

The root is `data-refreshing` and `aria-busy="true"`, the spinner shows, the
content is held down under it, and `refresh` is dispatched from the root,
bubbling and **cancelable**. Then one of:

- **The host answers.** It calls `preventDefault()` (`x-on:refresh.prevent`),
  does the work, and ends it: dispatches `refreshed` on the root, or
  re-renders it. A Livewire re-render (a morph) drops the attribute the
  server did not render, and the component ends the refresh when it sees it
  go. Rendering `refreshing` on the server starts one showing.
- **Nobody answers.** The button does what it does without the script:
  follows its link or submits its form. The page that comes back is the
  refreshed one.
- **`timeout`** (ms, default 10000) ends a refresh no one ends, so a lost
  request does not leave a spinner forever. 0 for none.

The end is said in the status region (`updatedLabel`, "Updated"), politely.

## Scrolling

- **touch-action**: `pan-x pan-down` at the top, so the browser scrolls a
  push up itself and leaves the pull to the script, and the default anywhere
  else, so a scrolled box pulls back up as any box does.
- **`overscroll-behavior-y: contain`** on the box, so the browser's own pull
  to refresh and scroll chaining stay out of a box that scrolls.
- With no height the root is not a scrolling box and the **page** scrolls it;
  the gesture reads the page's top instead. The browser's own page refresh
  is the browser's there: `overscroll-behavior-y: contain` on `<body>` is the
  host's choice, not the component's.

## Accessibility

- **Role and name**: `role="region"` named by `label`, when there is one.
  `aria-busy` on it while it refreshes; the indicator is `aria-hidden` (the
  busy state and the status line say what it shows). The end is said in a
  polite `role="status"`.
- **Focus**: never moved. The button keeps it through a refresh.

| Key | Does |
|---|---|
| Tab | The Refresh button, then the content's own stops |
| Enter / Space on Refresh | Refreshes (a link follows with Enter only, as any link) |

## Do not

- **Make the gesture the only way.** Keep the button unless the page has its
  own Refresh.
- **Nest one in another, or in a box that also scrolls sideways by drag.**
- **Leave a refresh unanswered by both the host and the button**: no `href`,
  no form, and no `x-on:refresh` means the spinner runs until the timeout.
