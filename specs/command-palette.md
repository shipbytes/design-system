# Command palette

A searchable list of actions and destinations, opened by a shortcut. Covers
`command-palette`, `command-group` and `command-item`.

## Anatomy

```
┌──────────────────────────────────────────┐
│ ⌕  Type a command or search…             │  ← field: role=combobox, keeps focus
├──────────────────────────────────────────┤
│ Go to                                    │  ← group heading (role=group, labelled by it)
│ ┌──────────────────────────────────────┐ │
│ │ 📄 Invoices                    G  I  │ │  ← active option: aria-selected, ring
│ └──────────────────────────────────────┘ │
│   ⚙ Settings                             │  ← disabled option, passed over
│ Actions                                  │
│   🗄 Archive invoice           ⌘ ⇧ A     │
│      Moves it out of the inbox           │  ← the item's slot, a second line
├──────────────────────────────────────────┤
│ ↵ to open                                │  ← optional footer slot
└──────────────────────────────────────────┘
```

- **The layer** is the modal's, from the modal recipe
  (`partials/modal.php`): backdrop, transitions, focus trap, focus in and
  back out, the scroll lock and Escape. Not a second dialog. The panel sits
  near the top of the window rather than centred, so the field stays put as
  the list grows and shrinks with the query.
- **The field** is a text input, `role="combobox"`, `aria-expanded="true"`
  (its list is shown whenever the palette is), `aria-controls` the list,
  `aria-autocomplete="list"`, named by `label`. Its ring is drawn on its row,
  inward.
- **The list** is a `role="listbox"` named by `label`, `aria-busy` while
  `loading`, scrolling inside a 20rem cap.
- **An option** is the listbox recipe's `activeOption` (`partials/listbox.php`):
  the ring the combobox's option draws on focus, drawn here on
  `aria-selected`, because focus never leaves the field. A link (`href`) is an
  `a role="option"`, so a middle press or Ctrl+press still opens a tab and
  `wire:navigate` still works; anything else is a `div`. `tabindex="-1"`
  either way. Its `shortcut` is drawn with ds::kbd and is not bound.
- **A group** is `role="group"` labelled by its heading, a flex column so the
  script can rank its options by CSS `order`.
- **The empty row** is outside the listbox (which may hold only options and
  groups) in a `role="status"` region, so `emptyText` is spoken when it
  appears.

## Behaviour (`dsCommandPalette`)

| Input | Result |
|---|---|
| `shortcut` (default `mod+k`: Cmd+K on a Mac, Ctrl+K elsewhere) | Opens; pressed again, closes. A combination with Ctrl or Cmd works in any field; a bare key (`/`) does nothing while typing in one. The key's default is prevented only when the palette acts on it |
| `open-command-palette` at the window, `detail.name` = `name` | Opens (both absent counts as a match). `close-command-palette` closes |
| Typing | Filters and ranks, the best match active; dispatches `search` with `{ query }`, debounced 200 ms |
| ArrowDown / ArrowUp | The next / previous enabled option, wrapping |
| Home / End | The first / last enabled option |
| Enter, or a press on an option | A link is followed (a click on it); an `action` dispatches that event with `{ value }`; the palette closes either way |
| Pointer over an option | It becomes the active one |
| Escape, with a query | Empties the query; stopped there |
| Escape on an empty field, the backdrop | Closes; focus back where it was |
| Closing | Empties the query (dispatching `search` with `''` if it was not empty) |

**Filtering** is case-insensitive and accent-insensitive, over the label and
the `keywords`: a match at the start ranks above one at a word start, above
one anywhere inside, above the letters in order with gaps ("gs" for "Go to
settings"). Keywords rank just below the same match in the label. Options are
ordered inside their group, and groups by their best option, through CSS
`order`: the DOM stays as the host rendered it, which is what keeps a
Livewire morph and the filter from fighting. A group with no match hides;
with no match anywhere the empty row shows. Matching text is not
highlighted. `:filter="false"` turns all of it off, for a host that searches
on the server.

**Options that change under it.** A MutationObserver re-reads the list on
any change the host makes (a Livewire re-render after `search`): the filter
runs again and the active option is kept if it is still there, by its id,
which `command-item` derives from its label, value, href and action so a
re-render keeps it. Otherwise the first enabled option becomes active.

## Accessibility

| | |
|---|---|
| Role | A modal `dialog` named by `label`, holding a `combobox` that controls a `listbox` of `option`s, in `group`s |
| Name | `label` names the dialog, the field and the list; a group is named by its heading |
| Keyboard | As the table above, after the ARIA combobox pattern with a listbox popup |
| Focus | Moves to the field on open and stays there; `aria-activedescendant` names the active option. Tab is trapped in the panel (where the field is the one stop). On close it returns to what had it |
| Announced | The active option through `aria-activedescendant`; "No results" through the status region |
| Without the script | Closed and inert: the layer is `x-cloak`ed and never shown. Give the page a visible route to the same places |

## Out of scope

- **Built-in fetching.** It dispatches `search`; the host fetches and renders.
- **Nested pages** (an option that opens a sub-list). A possible follow-up:
  it would be a stack of queries and a Backspace-to-go-back, on the same
  layer.
- **Binding the shortcuts it shows.** An option's `shortcut` is a label.

## Do not

- **Do not make it the only way somewhere.** It is invisible until summoned;
  every destination in it needs a link the page shows.
- **Do not put buttons or links inside an option.** An option is one thing to
  activate; its slot is a second line of text.
- **Do not filter twice.** When the server searches, pass `:filter="false"`,
  or the browser hides results the server matched on other fields.
- **Do not bind a bare letter as the shortcut.** `k` alone would fire on every
  k typed outside a field; use `mod+k`, or `/` as many sites do.
