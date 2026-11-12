# Popover

A small anchored surface for arbitrary content, opened by a trigger: a
non-modal dialog. Needs Alpine and the package's script (`dsPopover`,
`x-ds-anchor`). Shares its surface with `ds::hover-card` through
`partials/popover.php`.

`ds::dropdown` stays a menu (`role="menu"`, arrow keys, items that act). A
popover holds anything else: fields, a form, text with links.

## Anatomy

```
[ Filters ▾ ]                        ← the consumer's button (trigger slot)
╭────────────────────────────╮       ← div[role=dialog], fixed, 8px off
│ ☐ Only failed runs         │         the trigger, data-side="bottom"
│ Owner  [ Anyone       ▾ ]  │
│ [ Apply ]                  │
╰────────────────────────────╯
```

- **Root**: `div[data-ds-popover]`, `x-data="dsPopover({ dismissible })"`,
  `relative inline-block h-fit`. With `open`, also
  `x-modelable="dsOpen" x-model="{open}"`.
- **Trigger wrapper**: `div.contents[data-ds-popover-trigger]`, so the
  consumer's button sits in the layout as if unwrapped. The first button,
  link or field in it is the control.
- **Panel**: `div[role=dialog][tabindex=-1][data-ds-popover-panel]` (`role`
  `alertdialog` with the `role` prop), named by `aria-labelledby` or
  `aria-label`, described by `aria-describedby` with `describedby`, `x-show="dsOpen"`, `x-cloak`,
  `x-ds-anchor`. Surface: `rounded-panel border-border bg-surface p-4
  text-body text-fg-body shadow-float`, `z-50`.

## Variants

None. One surface, three widths.

| Width | Class |
|---|---|
| sm | `w-56` (14rem) |
| md (default) | `w-72` (18rem) |
| lg | `w-96` (24rem) |

All three carry `max-w-[calc(100vw-1rem)]`, the window less the anchor's
two 8px margins.

## Placement

Twelve: `top | bottom | left | right`, then `-start | -end` or nothing for
centred. The default is `bottom-start`: the panel's leading edge lines up
with the trigger's, as a field's list does.

The placement is a preference. `x-ds-anchor`:

1. flips to the opposite side when the preferred one lacks room and the
   opposite has it; a `left`/`right` panel with no room on either falls back
   to below or above;
2. shifts along the cross axis to stay 8px inside the viewport;
3. when no side fits, takes whichever of above or below has more room and caps
   the height there, scrolling inside;
4. recomputes once per frame on scroll (any scroller), resize, and a change
   in the panel's or trigger's size, while open only.

Start and end follow the reading direction. The side it took is written as
`data-side` (server-rendered as the preferred side), and the enter origin
reads it: a panel that flipped above grows upward.

Before the anchor runs, the panel carries absolute placement classes from the
recipe, so nothing is ever drawn at the top left of the page. The anchor
writes `position: fixed` and its coordinates inline over them.

## States

| State | Treatment |
|---|---|
| closed | `display: none` (x-cloak until Alpine, then x-show) |
| opening | `opacity-0 scale-95` → `opacity-100 scale-100`, `duration-popover-enter ease-enter`, `motion-safe` only |
| open | as drawn; trigger `aria-expanded="true"` |
| closing | the reverse, `duration-popover-leave ease-leave` |
| clamped | inline `max-height` and `overflow-y: auto` |

## Behaviour

- A click on the trigger wrapper toggles `dsOpen`.
- Opening (after the first frame, so a popover open at page load takes no
  focus): focus to `[data-ds-autofocus]`, then `[autofocus]`, in the panel, else the first visible
  focusable element, else the panel itself.
- Closing: if focus is in the panel, it returns to the control, except after a
  press outside or Tab away, which already put focus somewhere the reader
  chose.
- Escape on the root closes and stops propagation, so the innermost open
  popover takes it. A press outside the root (pointerdown, captured on the
  document while open) closes. Focus moving to an element outside the root
  closes. `dismissible: false` turns all three off.
- A nested popover, a combobox's list or a select's list is inside the root,
  so it counts as inside.

## Choices and why

- **Non-modal dialog, not `role=menu` or a disclosure.** The content is
  arbitrary and can hold fields, which a menu cannot, and focus moves into it,
  which a disclosure does not do. Not modal, because nothing behind it is
  blocked and it closes as soon as attention leaves.
- **No focus trap.** Tab leaving closes it, which is the light-dismiss
  behaviour readers know from the platform's own popups.
- **Focus goes in on open.** Otherwise a keyboard reader opens the panel and
  has to find it; the trigger is right before it in the page, so Shift+Tab is
  the way back.
- **Self-scoped by default,** like the dropdown: a popover per table row
  should not need a wrapper `x-data` each. `open` hands the state to the host.
- **Named by the trigger when not named.** A dialog must have a name, and the
  trigger usually says what the panel is.
- **Fixed positioning, not teleporting to the body.** The panel stays in the
  DOM where it was written, so it inherits the host's scope, form, `dir` and
  dark class, and "inside" for nesting is plain containment. Fixed escapes
  every `overflow` except that of a transformed ancestor, whose position is
  subtracted (see Limits).
- **Left and top, not a transform,** so an open panel is never the
  containing block of a popover nested in it.
- **rounded-panel, not rounded-control.** A card of content, not a list.

## Limits

- A transformed, filtered or contained ancestor is the containing block of a
  fixed element: the panel is placed correctly inside one, but that
  ancestor's own `overflow: hidden` still clips it, and its scale applies.
- A panel capped in height scrolls, and clips a list that opens inside it.
- Nothing that leaves the root (a teleported element) counts as inside.

## Accessibility

| | |
|---|---|
| Role | `dialog` on the panel (`alertdialog` by the `role` prop); no `aria-modal` |
| Name | `aria-labelledby` (prop), `aria-label` (prop), else labelled by the trigger |
| State | `aria-expanded` and `aria-controls` on the trigger control |
| Keyboard | Enter/Space on the trigger toggles. Tab moves through the panel and out, closing it. Escape closes and returns focus to the trigger |
| Focus | Into the panel on open; back to the trigger on Escape or when closed from inside |
| Motion | Fade and 95% scale, `motion-safe` only |

## Do not

- **Do not put a menu of actions in a popover.** That is `ds::dropdown`.
- **Do not use it for something the reader must answer.** That is `ds::modal`.
- **Do not open it on hover.** That is `ds::hover-card`.
- **Do not teleport the panel.** "Outside" is outside the root.
- **Do not pass a condition as `open`.** It is assigned to.
