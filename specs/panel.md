# Panel

A bordered container with an optional header and a divided body. Four copies on
the dashboard alone, plus most of the admin screens.

## Variants

| | Shell | Use |
|---|---|---|
| **list** (default) | `rounded-control`, `border` | One of several panels in a column or grid. Stays quiet. |
| **feature** (pass an `icon`) | `rounded-panel`, `border-strong` | A panel that sits on the page in its own right and needs to be found. |

The difference is not decoration. A list panel is scanned alongside its
siblings; a feature panel is a destination. Giving both the same weight makes
the column read as a table of equals.

## Header

```
┌──────────────────────────────────────┐
│ [icon]  Title            View all →  │  ← header
│         Subtitle                     │
├──────────────────────────────────────┤
│  row                                 │  ← body, divided
```

**The title wraps; the action does not.** The title is content — truncating it
hides information the reader came for, and in a narrow column ("Recent Cover
Letters" in a three-up grid) truncation triggers constantly. `text-balance`
keeps a two-line wrap from leaving an orphan word.

The action is a fixed short label and takes `whitespace-nowrap`. The
hand-written version let it wrap, which produced "View" above "all" — the
worst of both, since it cost a line *and* looked broken.

The action is `fg-muted`, not `accent`. The panel's content is the point; a
"View all" styled as a link competes with the data for attention.

## Body

| `body` | Behaviour |
|---|---|
| `rows` (default) | Children separated by `divider`, each managing its own padding |
| `plain` | One padded region, for free-form content |

**Rows carry their own padding, the body does not.** A row hover that stops
short of the panel edge reads as a rendering bug.

`divider` is deliberately lighter than `border` — 5% against 10%. A divider
that matches the container's own edge makes a panel look like a table.

## Rows

`<x-ds::panel-row>` is `flex items-center gap-3`. Give it an `href` and it
becomes an `<a>` with a `surface-subtle` hover; without one it is a `<div>` and
has no hover state, because a hover affordance on something unclickable is a
lie.

Typical shape: a leading icon or thumbnail, a `min-w-0 flex-1` middle that
truncates, then trailing metadata and actions that `shrink-0`.

## Empty state

A panel with nothing in it still renders its header — the reader needs to know
*what* is empty. The body takes a centred message; see the empty-state spec for
the illustration rules.

## Collapsible

`collapsible` turns the header into a button that shows and hides the body: a
settings page of panels, a sidebar of reference. The disclosure pattern, as
[accordion.md](accordion.md) has it, with the panel's own chrome:

```
┌──────────────────────────────────────┐
│ <h2><button aria-expanded>           │
│ [icon]  Title          ⌄  View all → │  ← the action sits beside the button
├──────────────────────────────────────┤
│  body: role=region, labelled by it   │
```

- `open` (default `true`) is rendered by PHP: `aria-expanded`, the chevron and
  the body's grid row and visibility. Nothing moves when Alpine starts.
- The body closes with the accordion's grid-rows trick, and `visibility:
  hidden`, so what is inside leaves the Tab order and the accessibility tree.
- Pressing dispatches `expand` or `collapse` from the panel, bubbling, with
  `{ name }`. `name` is for the host to remember the state (a cookie, the
  session, a user setting) and render it back through `open`: the panel itself
  remembers nothing, because state that outlives the page is the host's.
- The header action stays a link **beside** the button, never inside it: a
  link in a button is two controls in one, and neither works properly.
- It needs a `title` (the button's name) and throws without one, and throws
  with a `header` slot, whose content the button could not hold safely.

Without the prop the panel's markup is byte for byte the 1.x panel
(`PanelUnchangedTest` holds it to a fixture), because collapsible is a partial
of its own.

Needs Alpine (inline, not the shared script). Without it the panel is drawn as
PHP rendered it and does not toggle.

## Accessibility

- The header is a heading (`h2`), so a reader can jump between panels.
- Collapsible: a `<button>` inside the heading, with `aria-expanded` and
  `aria-controls`; the body is `role="region"` labelled by the button. The
  chevron is decorative; the state is `aria-expanded`.
- A closed body is `visibility: hidden`: not focusable, not announced.
- Rows that are links are each a Tab stop; rows that are not are not.

## Do not

- Nest a panel inside a panel. If content needs its own frame inside a frame,
  the outer one should be a page section instead.
- Put a primary button in the header. The header action is navigation; an
  action that changes something belongs with the thing it changes.
- Hide required form fields in a collapsed panel: submit fails on a field the
  browser cannot focus, as in [accordion.md](accordion.md).
