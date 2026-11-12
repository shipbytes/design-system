# Swipe actions

A list row that slides sideways to show the actions under it: Archive at
the start, Flag and Delete at the end. `swipe-actions` is the row,
`swipe-action` each button.

## The swipe is a shortcut, never the only way

Every action is a real `<button>` (or link) in the DOM and in the tab order
whether the row is open or not; the sliding is only how they are shown. So:

- **More**, a button at the end of the content, opens the end side, then the
  start side, then closes. That is the route for a mouse without a drag, a
  switch, a screen reader, anyone who does not swipe.
- **Keyboard**: focus moving onto an action opens its side, so a Tab through
  the row shows what it lands on. Escape closes the row and puts focus on
  More. Focus leaving the row closes it.
- **No script, or `disabled`**: the actions sit after the content in a plain
  flex row, visible, and there is no More, because nothing is hidden.

## Gesture

Hand-written on Pointer Events, through the one drag helper
(`resources/js/shared/drag.js`).

- **Axis lock.** The content is `touch-action: pan-y`, so a drag that starts
  vertical is the page's scroll; the helper lets a press go when it first
  travels further down than across.
- **Direction follows the reading direction.** Toward the inline end shows
  `start`, toward the inline start shows `end`; in a right-to-left page that
  flips with no prop.
- **Threshold** (px, default 56): released past it, or flung toward it, the
  row opens to the width of its actions; short of it, it closes. Past the
  actions' width the row gives a quarter of the way.
- **`full`**: dragged past 60% of the row's width, it dispatches `swipe` with
  `{ side }` and presses the action at that side's outer edge (start's first,
  end's last), then closes. The swipe event is for a host that wants to
  animate the row away; the press is the action itself, so the host has one
  handler, the button's.
- **One open at a time.** Opening a row closes the others of its group: the
  rows of the nearest list (`ul`, `ol`, `role=list`), or every row with the
  same `group` name.
- **A tap on the content of an open row closes it** and goes no further: the
  reader was closing, not following the link.
- **A mouse drag** works too, and the click that ends it is dropped.

## Why the actions are inline without the script, not a `<details>`

A `<details>` would hide them behind a disclosure with no script, which is a
worse row than the one with the actions simply showing: one more press for
every action, and a disclosure where the reader expects buttons. The swipe
hides them only because there is a gesture to bring them back.

## Accessibility

- **Role and name**: no role of its own; the row is whatever the host's
  content is (a link, text) inside the host's list item. Each action is a
  `<button>` named by its words; More is a `<button aria-expanded>` named
  `moreLabel` ("More actions").
- **Focus**: never moved by a swipe. Focus on an action opens its side, so
  the focused button is always on screen; Escape puts focus on More; focus
  leaving the row closes it.

| Key | Does |
|---|---|
| Tab | The content's own stops, More, then each action (opening its side) |
| Enter / Space on More | Opens the end side, then the start side, then closes |
| Escape | Closes the row; focus to More |
| Enter / Space on an action | The action |

## Tone

`swipe-action`'s `tone`: `neutral` (the inverse surface), `accent`,
`danger`. Each action is a solid block: a swipe shows colour before it shows
words, so the colour is what tells Delete from Archive at a glance.

## Do not

- **Put the only copy of an action in a swipe.** Swipe actions are a
  shortcut for actions the reader can also reach on the item's own page.
- **Put a form field in the row's content.** A drag that starts on a field is
  not a swipe (it is let go), but a row that is both editable and swipeable
  is two gestures fighting.
- **Give a side more than three actions.** They have to fit under the row.
