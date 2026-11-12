# Mention input

A textarea (or a one-line field) that suggests people or things after a
trigger character, `@` or `#`, and writes the chosen one into the text. A plain
`textarea`, never contenteditable: what posts is the text, mention tokens and
all, and nothing inside the field is styled. Behind `dsMention`, with the list
hung off the caret by `x-ds-anchor` and filtered by the command palette's
ranking (`shared/rank.js`).

## Anatomy

```
div[data-ds-mention][x-data=dsMention][x-modelable=dsValue]
├─ label[for=id]
├─ span (field shell, focus ring)
│  ├─ textarea | input[type=text]  [data-ds-mention-input]   name = `name`
│  │    role=combobox, aria-autocomplete=list, aria-expanded, aria-controls: bound to dsReady
│  │    aria-activedescendant: written while the list is open
│  └─ div[data-ds-mention-panel][x-show=dsOpen][x-ds-anchor at dsCaret]
│     ├─ div#id-list[role=listbox][aria-busy][data-ds-mention-list]
│     │  └─ div[role=option][data-ds-mention-option][data-value][data-ds-label][data-ds-keywords][data-ds-trigger]
│     │       ds::avatar xs (with src or initials), label, meta
│     └─ div[role=status] > p[data-ds-mention-empty] "No matches", p "Loading…" (with loading)
├─ span[data-ds-mention-ids]
│  └─ input[type=hidden][name={name}_mentions[]][value=id][data-ds-mention-text]   one per mention
├─ template[data-ds-mention-template]    the hidden input, cloned for a new mention
└─ p help | p error
```

- **Every row is rendered by PHP**, for every trigger, and the script hides
  the ones the query does not match. Ranked rows are ordered with CSS `order`,
  so the DOM stays as the host rendered it for a morph.
- **The ids post beside the text** as `{name}_mentions[]` (`post[body]` posts
  `post[body_mentions][]`; `mentionsName` overrides it). PHP renders an input
  for each row the value already mentions (`Support\Mention::found`, longest
  text first), so old input after a failed validation keeps its ids.

## Props

| Prop | Notes |
|---|---|
| `triggers` | `"@"` (default), or trigger ⇒ rows: `['@' => $people, '#' => $issues]`. Single characters; anything else throws |
| `items` | The rows for a single trigger: `id`, `label`, and optionally `meta`, `src`, `initials` (arrays or objects) |
| `insert` | What a row writes. `@` is the trigger typed, `label` and `id` the row's, replaced in one pass. `"@label"` (default) or `"@[label](id)"`, a token a server can parse back |
| `minChars` | Characters after the trigger before the list opens (default 0) |
| `max` | Rows shown at once (default 8) |
| `rows`, `multiline` | A textarea of `rows` (3), or with `multiline` false a text field |
| `atomic` | Backspace just after a mention deletes all of it |
| `loading` | `aria-busy` on the list and "Loading…" instead of "No matches" |
| `mentionsName` | Where the ids post instead of `{name}_mentions[]` |
| `listLabel`, `emptyText`, `loadingText` | The words |

## Behaviour (`dsMention`)

- **Opening.** Typing a trigger at the start of a word (after a space, a line
  break, an opening bracket or quote, or at the start) opens the list for that
  trigger, once `minChars` follow it. Only typing opens it: moving the caret
  into earlier text, or pasting, does not. An `@` inside `ada@example.com`
  does nothing.
- **Filtering.** What follows the trigger is ranked against each row's label,
  and its meta just below, as the command palette ranks: starts with, a word
  starts with, inside, letters in order. Best first, at most `max`.
- **Closing.** On a space typed when nothing matches (a name has spaces in it,
  so a space alone does not close), on the caret leaving the trigger's word,
  on Escape, on leaving the field and on choosing.
- **Keys while open:** Up and Down move the active row, wrapping; Enter or Tab
  writes it in; Escape closes and is stopped there, so a modal around the
  field stays open. **While closed every key is the field's own.**
- **Choosing** replaces the trigger and query with the `insert` text and a
  space (unless one follows already), through `execCommand('insertText')` so
  Undo takes it back, and `setRangeText` where that is missing. A hidden input
  with the row's id is added.
- **Editing.** A mention whose text an edit breaks ("@Maya Patel" becoming
  "@Maya Patxel") disables its hidden input, so it stops posting, and Undo
  brings it back. With `atomic`, Backspace just after a whole mention selects
  and deletes all of it.
- **Remote rows.** Each query dispatches `search` with `{ trigger, query }`
  from the field, bubbling, 200 ms after typing stops, the command palette's
  contract. From the field, not the root, because the host's attributes go
  on the field, so `x-on:search` written on the component hears it. The host re-renders `items` (and `loading`); rows that change
  while the list is open are filtered again.
- **Where the list goes.** Under the trigger character, not the moving caret,
  so it does not slide a letter's width with every key. Found with a mirror:
  a hidden element with the field's text styles and inner width, holding the
  same text, the character after the trigger in a span of its own
  (`caretBox` in `shared/caret.js`). It allows for wrapped lines, a scrolled
  field, a right-to-left field and mixed-direction text, and is kept inside
  the field's box. `x-ds-anchor` places the list against that box with `at`,
  flipping above when there is no room below.
- **No enter or leave animation**: the list opens and closes a few keystrokes
  apart, and a list still fading from the last close as the next opens was
  once left on screen.
- `dsValue` is the text; a value the host sets is shown, closes the list,
  posts the ids of the rows it mentions (found as the server finds them,
  `Support\Mention::found`) and stops posting the ones it no longer does,
  without events.

## Without the script

An ordinary textarea (or text field), named `name`, no combobox role. The
server's `{name}_mentions[]` inputs post for the value it was given.

## Accessibility

| | |
|---|---|
| Field | With the script, `role=combobox`, `aria-autocomplete=list`, `aria-expanded`, `aria-controls` the list; `aria-activedescendant` only while open |
| List | `role=listbox` named "Suggestions", `role=option` rows with `aria-selected` on the active one; focus never leaves the field |
| Empty, loading | In a `role=status` region |
| Rows | The label is the name; avatars are decorative |
| Focus | The field's shell ring; the active row draws the same ring inward on `aria-selected` (the listbox recipe's `activeOption`) |

`role=combobox` on a `textarea` is what this pattern needs and what the
brief asked for, but ARIA in HTML lists no role but `textbox` for a
textarea, so an HTML validator flags it. Screen readers announce it as an
editable combobox and read the active row.

## Do not

- **Do not trust the ids alone.** They post what the page held; check on the
  server that each is someone the author may mention, and parse the text if
  you used the token form.
- **Do not send thousands of rows.** Listen for `search` and render the ones
  that match.
- **Do not expect styled mentions inside the field.** It is plain text; render
  them on the way out.
