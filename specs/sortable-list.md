# Sortable list

Rows the reader puts in order, by dragging, by keyboard, or with buttons.
Covers `sortable-list` and `sortable-item`. Vertical only.

## Anatomy

```
┌───────────────────────────────────────┐
│ ⠿  Write the brief              ⌃  ⌄  │  ← row: handle, content, move buttons
└───────────────────────────────────────┘
┌───────────────────────────────────────┐
│ ⠿  Review the draft    ← dragged, lifted (shadow-float), follows the pointer
└───────────────────────────────────────┘
┌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌┐
╎                                       ╎  ← placeholder: where it will land
└╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌┘
```

- **The list** is a `ul role="list"` named by `label`. Rows are cards
  (`surface`, `border`, `shadow-raised`, `rounded-control`) with a gap, so a
  lifted row reads as picked up from among them.
- **A row** (`sortable-item`) holds its content, and with `name` a hidden
  input `name[]` with its `value`. Rows are moved in the DOM, so the inputs
  post the order shown, with or without the script.
- **The handle** is a button named "Reorder" and the row's `label`
  (`grabText`), described by the list's `instructions`, `aria-pressed` while
  the row is picked up. `touch-action: none` on it alone, so a finger
  anywhere else scrolls the page. Hidden until the script runs: without it
  the button would do nothing.
- **The move buttons**, "Move … up" and "Move … down", are the route for a
  reader who cannot drag (WCAG 2.5.7). Shown on the row's hover and focus,
  and always on a coarse pointer, which has no hover; `aria-disabled` at the
  ends, so a button that just became an end keeps focus.
- **The placeholder** is a dashed box over the gap the other rows open.
- **The status region** speaks each step.

## States

| | |
|---|---|
| At rest | As rendered: the server's order |
| Dragging | The row `data-dragging` (lifted, no transition, following the pointer), the list root `data-dragging` (grabbing cursor, no text selection), the other rows translated out of the way |
| Picked up by keyboard | The row `data-lifted` (lifted, ringed), its handle `aria-pressed="true"` |
| Settling | Every row animates from where it was drawn to its slot (FLIP), `motion-safe` |
| A disabled row | No handle press, no buttons; other rows can pass it |
| A disabled list | Nothing moves; the hidden inputs still post |

## Behaviour (`dsSortable`)

| Input | Result |
|---|---|
| Press on the handle and move 4px | Picks the row up; it follows the pointer under pointer capture |
| With `:handle="false"`, a mouse or pen press anywhere on the row that is not a control | The same; touch still uses the handle |
| Moving | A row gives way once the dragged row's leading edge passes its middle, and slides past (CSS transforms, `duration-popover-enter`); the placeholder follows; the nearest scrolling box scrolls within 48px of its edge |
| Release | Drops; the row settles into its slot |
| Escape while dragging, a cancelled pointer | Everything goes back |
| Space or Enter on the handle | Picks up; "Picked up X, position 2 of 5" |
| ArrowUp / ArrowDown while picked up | Moves one place; "Moved to position 3 of 5" |
| Space or Enter while picked up | Drops; "Dropped X at position 3 of 5" |
| Escape while picked up | Puts it back; "Cancelled. X is back at position 2 of 5". Stopped, so a modal around it stays open |
| Focus leaving the handle while picked up | Drops where it is |
| Move up / Move down | One place at once, announced |

After any drop that changed the order, `reorder` is dispatched from the root,
bubbling, with `{ order: [values], moved: value, from, to }`, positions from 0.
The announcements are props (`pickedText`, `movedText`, `droppedText`,
`cancelledText`) with `:item`, `:position` and `:total`, English by default.

**Livewire.** Rows carry the host's `wire:key`; the host persists `order` and
re-renders, and the morph moves the rows to its order. Nothing in the
behaviour is held across a morph: the rows are read afresh on every press, and
the handles' and buttons' visibility is bound to `dsReady`, so a morph that
rewrites their attributes keeps them shown.

## Accessibility

| | |
|---|---|
| Role | `list` and `listitem`; each handle a toggle `button` |
| Name | `label` names the list; a handle is "Reorder X", described by `instructions` |
| Keyboard | As above. Every row is reachable without a pointer, and every move is possible without dragging |
| Focus | Stays on the handle (or the move button) through every move; the moved row is brought into view |
| Announced | Each step, politely, in the status region |
| Motion | The slide and the settle are transitions behind `motion-safe:`; with reduced motion rows jump |

## Out of scope

- Moving between two lists, horizontal lists and grids.
- Dragging several rows at once.

## Do not

- **Do not leave out `value`.** It is what posts and what `reorder` reports;
  the position is not an identity.
- **Do not put the only copy of the order in the DOM.** Persist `reorder`, or
  a re-render restores the server's order.
- **Do not make a row's whole content a drag area with `:handle="false"` when
  it holds controls a reader presses often.** Presses on controls are passed
  through, but the row's text can no longer be selected.
