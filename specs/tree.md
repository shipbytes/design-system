# Tree

Nested items that open and close: a file browser, a docs sidebar, a set of
permissions to grant. Covers `tree` and `tree-item`.

## Three trees, one markup

| `selectable` | What it is | What a row does | What posts |
|---|---|---|---|
| `none` (default) | A browser, or navigation when the items have an `href` | Opens and closes; a link follows | Nothing |
| `single` | A choice of one | Selects it | One hidden input, `name` |
| `multiple` | A checkbox tree | Checks it, and every enabled leaf under it | One `name[]` per checked leaf |

A tree is navigation **or** a choice, never both: an `href` in a selectable
tree throws. A row that both followed a link and toggled a selection would do
one of the two by surprise every time.

`selected` means the selection, the checked box, or (in a navigation tree) the
current page, which is written as `aria-current="page"` and not as a selection.

## Anatomy

```
┌──────────────────────────┐
│ ⌄ [▣] 📁 app          12 │  ← row: role=treeitem, aria-expanded, aria-checked
│   │ › [ ] 📁 Http        │  ← children: role=group, the row's sibling
│   │   [■] 📄 helpers.php │
│ › [ ] 📁 config          │
└──────────────────────────┘
  chevron  box  icon  label  count/badge
```

Each `li` is `role="none"`. The row inside it is the `treeitem`, and its
children's `ul role="group"` sits beside the row, not inside it. That keeps the
focus ring and the row's accessible name on the row alone: a `treeitem`
wrapping its whole subtree would be named by every word under it, and ringed
around the lot.

## Structure is counted in PHP

A component's slot renders before the component, so while an item renders it
cannot know how deep it is or how many siblings it has. Every row renders at
level 1, one of one; each parent renumbers the rows in its own slot (its direct
children counted among themselves, everything below moved one level down), and
the root numbers the top level. `aria-level`, `aria-setsize` and
`aria-posinset` are therefore in the HTML before any script runs, which a
screen reader in browse mode and a test both rely on. `Support\Tree` holds the
rewrite; `TreeTest` holds its exact strings.

In a checkbox tree the root also works out every parent's box from the leaves
under it, after any checked ancestor has checked them: all checked is `true`,
none `false`, some `mixed`. A parent's own state is never stored; it is always
what its leaves say.

## State from PHP

- `open` left out: a parent is open exactly when it holds the current page or
  (single) the selected item, so the reader lands with it in view.
- `selected` on a checkbox parent checks its enabled leaves, overriding a
  leaf's own `false`.
- `disabled` rows can be focused and opened (what is under them stays
  reachable) but never chosen; a disabled leaf keeps its state and does not
  post.
- `expandable` draws a folder with nothing in it yet, for children the host
  loads itself on `expand`.

## Accessibility

- `role="tree"` named by `label`; `aria-multiselectable="true"` in a checkbox
  tree. Rows `role="treeitem"` with level, setsize and posinset; parents carry
  `aria-expanded`; children are in `role="group"`.
- Single selection: `aria-selected` on every row. Multiple: `aria-checked`
  (`true`, `false`, `mixed`) on every row. Navigation: `aria-current="page"`.
- **One Tab stop** (roving tabindex): the current page, else the selected row,
  else the first. The script keeps it where the reader last was; a stop inside
  a closed parent moves up to the parent.
- Without the script, a file or choice tree is still one stop, and a
  navigation tree's links are each their own: every page is still reachable by
  Tab, which is the safe way to fail.
- Selection and the current page are drawn with weight as well as colour.
- Closing a parent that holds focus moves focus to the parent.

### Keyboard

| Key | Does |
|---|---|
| Down / Up | Next / previous row that is showing |
| Right | A closed parent opens; an open one moves to its first child |
| Left | An open parent closes; anything else moves to its parent |
| Home / End | First / last row showing |
| A letter | Next row starting with it; typed quickly, letters run together; the same letter again cycles |
| `*` | Opens every parent beside this one |
| Enter | Follows a link; otherwise selects (single), checks (multiple), or opens and closes (none) |
| Space | The same, except on a link |

Right and Left swap on a right-to-left page. A press on a row does what Enter
does; a press on its chevron only opens or closes it, and never follows a link.

### Events

`expand` and `collapse`, bubbling from the row, with `{ value }`, when the
reader opens or closes a parent. Not dispatched for a value the host sets.

## Binding

`wire:model` or `x-model` on the tree goes to the root beside
`x-modelable="dsValue"`: a string (single) or an array of leaf values
(multiple). A bound tree with `selectable="none"` throws: there is nothing to
bind.

## Do not

- **Do not mix links and choosing in one tree.** It throws.
- **Do not nest deeper than the reader can follow.** Past four levels, consider
  a page per branch.
- **Do not use a tree for a flat list.** One level is a list, a select or radio
  cards; the tree's keys are a cost there with nothing to pay for.
- **Do not use it for a menu of actions.** A tree is for things, not commands;
  that is a dropdown.
- **Do not lazy-load without `expandable`.** A folder with no children and no
  chevron cannot be opened, so `expand` never fires.
