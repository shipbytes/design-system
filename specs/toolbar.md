# Toolbar

A set of tools that is one Tab stop, with roving focus and a hover highlight.
Needs Alpine and the package's script (`dsToolbar`, `x-ds-glide.hover`).
Covers `toolbar-button` and `toolbar-separator`.

## Anatomy

```
╭──────────────────────────────────────────╮
│  ↶  ↷  │ [B] I  U │  🔗 Link             │  ← div[role=toolbar]: tray
╰──────────────────────────────────────────╯
     ▲         ▲            ▲
  buttons  toggle-group   highlight (one, aria-hidden, follows pointer/focus)
```

- **Toolbar**: `div[role=toolbar][aria-label][aria-orientation]`,
  `data-ds-toolbar`, `x-data="dsToolbar"`, `x-ds-glide.hover`. A rounded-panel
  tray with `p-1 gap-0.5`.
- **Highlight**: first child, `span[data-ds-glide-highlight]`, positioned by
  `--ds-glide-*`, visible while `data-visible`.
- **Button**: `button` (or `a`) with `data-ds-toolbar-item` and
  `data-ds-glide-item`, optional icon, words (sr-only with `iconOnly`),
  optionally wrapped in `ds::tooltip`.
- **Separator**: `div[role=separator]`, `aria-orientation` across the toolbar.

## Variants

| Variant | Tray |
|---|---|
| `muted` (default) | `bg-fg/5`, no border: a tray within the page |
| `floating` | `bg-surface border-border shadow-overlay`: a tray over content |

## Sizes

| Size | Button height | Text | Icon-only |
|---|---|---|---|
| sm | 32px | `text-meta`, `px-2.5` | 32px square |
| md | 36px | `text-body`, `px-3` | 36px square |

Toggle groups inside take the toolbar's size unless they set one.

## States

| State | Treatment |
|---|---|
| resting | `text-fg-body` |
| hover | `text-fg`; the highlight moves under it |
| keyboard focus | the highlight moves to it, and the focus ring |
| pressed (`aria-pressed="true"`) | `bg-fg/10 text-fg` on the button |
| pressing (pointer down, or Space/Enter held) | the highlight darkens to `bg-fg/10` and scales to 95%; the button does not move |
| disabled | `text-fg-muted`, `cursor-not-allowed`, skipped by the arrows, highlight holds |

Highlight motion: `motion-safe:transition-[translate,width,height,scale,background-color,opacity]`,
`duration-popover-enter ease-enter`. Appearing from hidden jumps into place
and only fades, so it never slides in from the corner.

## Behaviour

`dsToolbar` is a roving tabindex over the toolbar's own items
(`[data-ds-toolbar-item]` whose closest toolbar is this one, which includes
the toggles of a toggle group inside):

- exactly one item has `tabindex="0"`: the last focused, else the first
  enabled;
- arrows in the toolbar's orientation (Left/Right mirrored under `dir="rtl"`)
  move to the next usable item, wrapping, skipping disabled and hidden ones;
- Home and End go to the ends;
- items added, removed, disabled or hidden later are picked up.

`x-ds-glide.hover` follows `pointermove` from a mouse or pen (never touch),
holding still over gaps and disabled items, hides on `pointerleave`, follows
`focusin` only when focus is `:focus-visible`, and marks pressing with
`data-pressing`.

## Choices and why

- **Named toolbar,** not floating-button-group: it is the ARIA role, and
  "floating" is only one of its two looks.
- **`pressed` is rendered, not managed.** A toolbar button's pressed state is
  the host's (is this post starred?), so the host flips it. Toggles that own
  their state are `ds::toggle-group`, which may sit inside.
- **Pressing scales the highlight, never the button,** so the words under the
  pointer do not move.
- **No hover tracking for touch.** A tap would leave the highlight stranded.
- **The tab stop ignores visibility; the arrows do not.** A toolbar rendered
  inside a closed modal still has a tab stop when it opens.

## Accessibility

| | |
|---|---|
| Role | `toolbar`, with `aria-orientation` |
| Name | `label` |
| State | `aria-pressed` on toggle buttons; `disabled`, or `aria-disabled` on links |
| Keyboard | Tab in/out (one stop). Arrows: next/previous, wrap, skip disabled. Home/End. Space/Enter activate |
| Focus | Ring on the focused button, highlight under it |
| Motion | Highlight slide and press scale, `motion-safe` only |

## Do not

- **Do not put text fields or selects in a toolbar.** They need the arrows.
- **Do not nest toolbars.**
- **Do not scale or move the button on press.**
