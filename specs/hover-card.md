# Hover card

A preview of what a link points at, shown on hover or keyboard focus. Needs
Alpine and the package's script (`dsHoverCard`, `x-ds-anchor`). Shares the
popover's surface, widths, transition and placement through
`partials/popover.php`.

## Anatomy

```
Assigned to Ada Lovelace since Monday.     ← the consumer's link (trigger slot)
            ╭──────────────────────────╮   ← span.block, fixed, 8px off the
            │ (AL) Ada Lovelace        │     trigger, data-side="bottom"
            │      Analyst, Engines    │
            │ View profile             │
            ╰──────────────────────────╯
```

- **Root**: `span[data-ds-hover-card]`, `x-data="dsHoverCard"`,
  `relative inline-block h-fit`. Spans throughout, so it is valid inside a
  paragraph.
- **Trigger wrapper**: `span.contents[data-ds-hover-card-trigger]`. The
  trigger is the consumer's element, unchanged.
- **Card**: `span.block[data-ds-hover-card-panel]`, no role, `x-show="dsOpen"`,
  `x-cloak`, `x-ds-anchor`. Same surface as `ds::popover`.

## Variants

None. Widths `sm` / `md` (default) / `lg`, as the popover's.

## Placement

As `ds::popover`: twelve placements, default `bottom-start`, flip, shift and
clamp by `x-ds-anchor`, `data-side` for the enter origin.

## States

| State | Treatment |
|---|---|
| closed | `display: none` |
| pending | the pointer or focus is on the trigger; nothing shown for 500 ms |
| open | shown, `opacity-100 scale-100` from `opacity-0 scale-95`, `duration-popover-enter`, `motion-safe` only |
| leaving | the pointer left both; still shown for 300 ms |
| closing | the reverse, `duration-popover-leave` |

## Behaviour

| Input | Result |
|---|---|
| mouse or pen enters the trigger | open after 500 ms, unless it leaves first |
| pointer leaves trigger and card | close after 300 ms, unless it enters either |
| pointer enters the card | cancels a pending close |
| keyboard focus on the trigger (`:focus-visible`) | open after 500 ms |
| focus moves into the card | stays open |
| focus moves outside trigger and card | close at once, unless the pointer is over them |
| Escape, focus anywhere | close; propagation stopped only when focus is inside the component |
| touch (`pointerType: touch`), or a tap's focus | nothing; the trigger's own action runs |
| pointerdown on the trigger | cancels a pending open: pressing is using, not previewing |

## Choices and why

- **No role on the card.** `tooltip` content may not be interactive, and this
  can hold links. `dialog` implies focus moves in and is managed. The card is
  supplementary content placed after the trigger in reading order.
- **No `aria-describedby`.** A description is read as one flat string and
  drops the links; the card is reached as content instead.
- **No `aria-expanded` on the trigger.** It is a link; activating it
  navigates. Saying it expands something would be false.
- **Reachable by Tab while open, never trapped.** WAI-ARIA has no hover-card
  pattern; this follows the guidance for content shown on hover or focus
  (WCAG 1.4.13: dismissible, hoverable, persistent) and keeps the card in the
  reading order so a keyboard reader can use what a pointer reader can.
- **The same delay for focus as for hover.** Tabbing along a list of names
  should not flash a card at every stop.
- **Never on touch.** A long-press preview would fight the platform's own,
  and a tap must remain the link.
- **Content is non-essential by contract.** The docs say so plainly: anything
  in the card must also be on the linked page.

## Accessibility

| | |
|---|---|
| Role | none on the card; the trigger's own |
| Name | the trigger's own |
| State | none exposed |
| Keyboard | focus the trigger and wait to open; Tab into the card's links; Tab past to close; Escape closes |
| Focus | never moved by the component |
| Motion | fade and 95% scale, `motion-safe` only |

## Do not

- **Do not put essential content or the only copy of an action in it.**
- **Do not use it for a short label.** That is `ds::tooltip`.
- **Do not wrap a control whose press does something other than navigate.**
- **Do not put block elements in a card that sits inside a paragraph.**
