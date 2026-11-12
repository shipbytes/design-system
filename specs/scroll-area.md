# Scroll area

A bounded box that scrolls natively, with a quiet scrollbar and a shadow at
each edge that has more content past it.

## Anatomy

```
┌──────────────────────────┐
│░░░░░░░░░░░░░░░░░░░░░░░░░░│  ← top shadow: there is more above
│ Line 4                  ┃│  ← the native scrollbar, thin, token colours
│ Line 5                  ┃│
│░░░░░░░░░░░░░░░░░░░░░░░░░░│  ← bottom shadow: more below
└──────────────────────────┘
```

- **The area**: the root, `relative overflow-hidden`, carrying the edges. The
  host's border and rounding go on it (`class`); the viewport takes its
  rounding (`rounded-[inherit]`).
- **The viewport**: `overflow: auto` on the axes `orientation` allows,
  `scrollbar-gutter: stable` when it scrolls vertically (so hiding the bar
  never moves the content), sized by `maxHeight` or `height`: a number of
  pixels or one CSS length. Anything else throws rather than writing more of
  the style attribute.
- **The edges**: one span per side that can scroll, `aria-hidden`,
  `pointer-events: none`, a gradient of the scrim (stronger in dark, where a
  light shadow would not show).
- **The scrollbar** (`scrollbar`): `auto`, the browser's width; `thin`
  (default); `hidden-until-hover`, thin and transparent until the pointer is
  over the area or it holds keyboard focus. All three take
  `scrollbar-color` from `border-strong`. Native only: no drawn scrollbar.

## The shadows

| Where | How |
|---|---|
| A browser with scroll-driven animations | Pure CSS (`theme.css`): the viewport names its scroll timelines, the area lifts them to its scope with `timeline-scope`, each edge's opacity runs over the first or last 1rem of scroll. A box that does not overflow has an inactive timeline, so no shadow |
| Without them | `x-ds-scroll-shadows` measures on scroll and on resize (ResizeObserver, or the window's resize) and marks each edge `data-ds-shown` |
| With neither | No shadows. The content still scrolls |

In a right-to-left page `start` is the right edge, in CSS and in the script.
The shadow is tied to the reader's own scrolling and does not move by itself;
under reduced motion it switches on and off without the 1rem fade.

## Keyboard

| Content | The region |
|---|---|
| Nothing focusable | `tabindex="0"`, `role="region"`, `aria-label` from `label` (required: it throws without one). A keyboard Tabs to it and scrolls with the arrows, PageUp, PageDown, Home, End, Space |
| A link, button, field, `summary`, `iframe`, or anything with a non-negative `tabindex` or `contenteditable` | No Tab stop of its own: Tab already scrolls the box to each of them. `role="region"` and the name only when `label` is given |

Read from the rendered slot in PHP, so the stop is right before any script.
Content that gains or loses its focusable elements later (a Livewire child
swapped in) is not re-read.

The region's focus ring is drawn inward (`-outline-offset-2`), because the
area clips.

## Accessibility

| | |
|---|---|
| Role | `region` when named; otherwise none |
| Name | `label` |
| Keyboard | As above: a stop of its own only when it needs one |
| Scrollbar | Never hidden under `prefers-contrast: more` or forced colours; `hidden-until-hover` shows it on keyboard focus as well as on hover |
| Shadows | Decorative, `aria-hidden` |

## ds::code-block

ds::code-block's scroll box is the same idea (a `pre` with `tabindex="0"`,
`role="region"` and a name) and is NOT rebuilt on this component: adopting
it would wrap the `pre` in two more elements and change the block's output.
There is no shared helper to adopt either; the code block writes its three
attributes itself.

## Do not

- **Do not nest scroll areas on the same axis.** A wheel that scrolls the
  inner box until it ends and then the outer one is a trap for a pointer and
  for a keyboard.
- **Do not use it to make a page fit a viewport.** The page scrolls; a box
  inside it is for content that must stay bounded, a list in a panel.
- **Do not hide the scrollbar to tidy a design.** `hidden-until-hover` is for
  a box whose scrollability the shadows already say.
- **Do not put the only copy of something important at its far end.** A box
  that scrolls is a box whose end some readers never reach.
