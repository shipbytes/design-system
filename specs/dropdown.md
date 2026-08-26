# Dropdown

A menu of actions anchored to the control that opened it. The kebab menu on a
table row, the account menu in a header, the "Export as…" beside a button.

Covers `dropdown` and `dropdown-item`.

## Not a select

A dropdown holds **verbs**. A select holds **values** — it is a form field, it
posts something, and it lives in [input.md](input.md) as `as="select"`.

The tell is what happens on click: an item that *does* something is a menu item,
an item that *becomes the field's value* is an option. Reaching for this
component to build a form control produces a control that submits nothing.

## Anatomy

```
[ Actions ▾ ]                 ← trigger: the consumer's own element
     └──────────────────┐
     │ ✎  Edit          │     ← dropdown-item
     │ ⧉  Duplicate     │
     │ 🗑  Delete       │     ← tone="danger"
     └──────────────────┘
```

| Part | Token | Why |
|---|---|---|
| Menu | `surface` + `border` | It floats over arbitrary content, so it paints its own ground and draws its own edge. |
| Corner | `radius-control` | 8px, not `panel`. It is the size of a control and reads as attached to one. |
| Elevation | `shadow-float` | **Not `overlay`.** The token descriptions carry the distinction: `float` is "things that float over the page on demand — dropdowns, popovers"; `overlay` is "modal panels only — the one surface that blocks the page behind it". A dropdown does not block the page, and the shadow is where the system says so. |
| Item hover | `surface-subtle` | The same row-hover fill the table and the sheet use. |
| Danger item | `danger-wash` | A wash, not a tint. At full row width a tint reads as shouting — the same argument [color.md](color.md) makes for the alert. |

Minimum width `min-w-52` (208px), no maximum. Item labels truncate rather than
wrap: a menu that reflows to two lines stops looking like a list of choices.

## Placement

`bottom-end` (default) | `bottom-start` | `top-start` | `top-end`.

A closed set mapped to literal class strings — position, margin **and transform
origin** together, so the scale-up animation grows from the corner the menu is
pinned by rather than from its centre.

Literals and not `top-{$side}` for the scanner reason: a class built at runtime
is invisible to Tailwind and gets no rule. See the same note in
[modal.md](modal.md#sizes).

`bottom-end` is the default because the overwhelming case is a trigger on the
right of a row, where a menu opening left-aligned runs off the page.

There is no collision detection. The component does not know where the viewport
edge is; a menu near the bottom of the page takes `top-*` from whoever placed it.

## State — and why this one differs

`open` is **optional here**, and required on sheet and modal. That is deliberate.

Sheet and modal are page-level overlays whose open state the host usually
already owns — a selected record, a route, a pending confirmation. A dropdown's
state is local to its own trigger and shared with nothing, and a kebab menu on
every row of a table would otherwise need a wrapper `x-data` per row.

So: pass `open` and the host stays in charge; leave it out and the component
scopes its own.

```blade
{{-- Scoped: the common case --}}
<x-ds::dropdown>
    <x-slot:trigger>
        <x-ds::button variant="secondary">Actions</x-ds::button>
    </x-slot:trigger>

    <x-ds::dropdown-item icon="pencil-square" href="/edit">Edit</x-ds::dropdown-item>
    <x-ds::dropdown-item icon="document-duplicate">Duplicate</x-ds::dropdown-item>
    <x-ds::dropdown-item icon="trash" tone="danger" as="button">Delete</x-ds::dropdown-item>
</x-ds::dropdown>

{{-- Host-owned: when something else also opens it --}}
<div x-data="{ accountOpen: false }">
    <x-ds::dropdown open="accountOpen" placement="bottom-end"> … </x-ds::dropdown>
</div>
```

## The trigger is the consumer's element

The `trigger` slot takes whatever the host puts there — usually an
`<x-ds::button>`. The component wraps it in a `display: contents` element, so
the trigger sits in the layout exactly as it would unwrapped while clicks still
bubble to the wrapper.

Because the element is the consumer's, `aria-expanded` cannot be written on it at
compile time. The component **sets it at runtime** on whatever the slot turned
out to contain, along with `aria-haspopup="menu"`, and keeps it in sync.

Without that, nothing announces that the button owns a menu or that the menu is
now open. It is the accessibility equivalent of the unstyled-vendor bug: looks
completely fine, is completely broken, reports nothing.

## Items

`dropdown-item` renders an `<a>` when it has `href`, otherwise a `<button>`.
`as="button"` forces `type="submit"` for the delete that POSTs.

**Tone is `neutral` or `danger`. Nothing else.** Same rule as the sheet: a tone is
a claim about what the item *is*, not a way to tell items apart. `danger` is the
one that destroys something.

A **disabled** item renders as a `<button>` carrying `aria-disabled`, never a
disabled `<a>` — a disabled link is still focusable and still followable by
keyboard, so the only reliable way to stop it going somewhere is for it not to be
a link.

Items carry **no focus ring**. A ring inside a menu with 4px of vertical padding
clips against the rounded edge. The hover fill doubles as the focus fill, which
is why every item styles `focus-visible:` and `hover:` to the same value rather
than relying on hover alone.

## Accessibility

- `role="menu"` + `aria-orientation="vertical"` on the menu, `role="menuitem"` on
  each item.
- `aria-haspopup="menu"` and a live `aria-expanded` on the trigger.
- **Arrow keys move between items; Home and End jump to the ends.** `role="menu"`
  is a *promise* that this works. A menu that only answers to Tab tells a
  keyboard user one thing and gives them another.
- **Roving tabindex** — every item is `tabindex="-1"` and the menu moves focus
  itself. Leaving items tabbable makes Tab walk the menu one item at a time,
  which is the behaviour `role="menu"` exists to replace.
- Disabled items are skipped by the arrow keys, not merely dimmed.
- Focus enters the first enabled item on open and **returns to the trigger** on
  close.
- Escape closes. Tab closes and moves on, so the menu is never left hanging open
  behind the reader.
- Click outside closes — via `.outside`, not a full-screen invisible backdrop. A
  backdrop swallows the first click anywhere on the page, so closing a menu and
  pressing the thing underneath takes two clicks.

## Do not

- **Build a select out of it.** See the top of this file.
- **Put a form inside it.** Click-outside and Escape will discard input the reader
  typed, with no warning and no way back.
- **Nest a submenu.** There is no hover intent, no timing grace, and no way back
  out with a keyboard. If a menu needs a second level, it needs a modal or a page.
- **Use it for navigation between pages of the app.** That is `nav-item`; a menu
  hides the destinations, and primary navigation should not be hidden.
- **Use `shadow-overlay`** to make it feel more important. That shadow means "this
  blocks the page", which this does not.
