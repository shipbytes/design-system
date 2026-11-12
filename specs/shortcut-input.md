# Shortcut input

A keyboard shortcut, set by pressing it: a settings field for "Open search"
that holds `mod+shift+k`. A text field on the field recipe and a recorder
button, behind `dsShortcutInput`; the keys drawn by ds::kbd's `shortcut`.

## Anatomy

```
div[data-ds-shortcut-input][x-data=dsShortcutInput][x-modelable=dsValue]
├─ label#id-label[for=id]
├─ span (field shell, focus ring)
│  ├─ input[type=text][data-ds-shortcut-text]       name, value; x-bind:hidden=dsReady
│  ├─ button[data-ds-shortcut-record][hidden until dsReady]
│  │    aria-labelledby = id-label id-shown, aria-describedby = id-how (+ help/error)
│  │  └─ span#id-shown
│  │     ├─ span[data-ds-shortcut-placeholder]      "Record shortcut"
│  │     ├─ span[data-ds-shortcut-prompt][hidden]   "Press keys…"
│  │     └─ ds::kbd shortcut=value [data-ds-shortcut-keys]
│  └─ button[data-ds-shortcut-clear]                 while there is a value
├─ span#id-how[hidden]                               how to record, the description
├─ span.sr-only[role=status][data-ds-shortcut-live]
├─ p[data-ds-shortcut-note][hidden]                  refused, or "Already used by …"
└─ p help | p error
```

- **The text field holds the value and the name in both modes.** Hidden with
  the script, it still posts, and a morph keeps it. No name moves.
- **The value** is the normalised string: modifiers in the order mod, ctrl,
  meta, alt, shift, then one key, lower case. "mod" is Cmd on a Mac and Ctrl
  elsewhere (`portable`, on by default). `Support\Shortcut::normalise()` and
  `shared/shortcut.js` agree on it.

## States

| State | Treatment |
|---|---|
| no script | The text field: type `mod+shift+k` |
| empty | The placeholder, muted |
| set | The keys as caps; the clear button |
| recording | "Press keys…" in the accent colour, `data-ds-recording` (accent border); the scope's `dsRecording` |
| refused | Still recording; the note and the live region say why |
| conflict | Set, with the note "Already used by …" |
| disabled | Both buttons disabled |

## Behaviour (`dsShortcutInput`)

- Click, Enter or Space on the recorder starts recording (a click while
  recording cancels). The next keydown is taken, `preventDefault` and
  `stopPropagation`, so the page's shortcuts and a dialog's Escape do not fire.
- `record(event, { portable, by })` (shared): a modifier alone returns null
  and recording goes on. `by=key` writes `event.key` ("?" without the Shift
  that typed it); `by=code` writes the key's place as the US character, so
  the key right of "." is "/" on any layout.
- Refused, with a message, recording going on:
  - no Ctrl, Cmd or Alt, unless `allowBare` (Shift alone types, so it does not
    count)
  - a `reserved` shortcut, by default `Support\Shortcut::RESERVED` (mod+w,
    mod+t, mod+n, mod+q, mod+shift+t, mod+shift+n, mod+shift+w): the browser
    takes them before the page sees them
- `conflicts` (combo ⇒ label): set anyway, with "Already used by …". Compared
  with `same()`, so "mod+j" matches "ctrl+j" off a Mac.
- Escape cancels; Backspace or Delete with no modifier clears; **Tab is never
  taken**: it cancels and moves focus on. Blur cancels. Not recording,
  Backspace or Delete on the recorder clears.
- A change sets the text field and dispatches `input`, then `change` with
  `detail = { value, previous }`, from it, bubbling. A plain `Event` carrying
  `detail`: a CustomEvent's detail would be taken as the value by x-model.
- `required`: the text field's `invalid` is cancelled (a hidden field cannot
  show the browser's bubble), its message goes in the note and focus to the
  recorder.
- No global registry: conflicts are what the host passes.

## Display (`x-ds-shortcut`, ds::kbd `shortcut`)

- The server draws words off a Mac: Ctrl + Shift + K (`Support\Shortcut::caps`
  with mac false). The directive, an enhancement registered without a scope,
  redraws on a Mac as ⌘⇧K with no separator, and again whenever
  `data-ds-shortcut` changes (a MutationObserver), which is how the recorder
  updates its keys.
- The caps are `aria-hidden`; an sr-only span holds the words, always words:
  "Command+Shift+K", "Control+Shift+K".
- `caps()` is exported from `shared/shortcut.js` and mirrored by
  `Support\Shortcut::caps()`, `keys()` and `words()`, so the command palette
  and ds::kbd's `keys` can draw a stored shortcut the same way.

## Accessibility

| | |
|---|---|
| Recorder | A `button`: Tab reaches it, Enter or Space activate it; name = label + what it shows ("Open search Control+Shift+K", "Open search Record shortcut") |
| Description | "Press Enter to record, then the keys. Escape cancels; Backspace clears." plus help or error |
| Live region | `role=status`: "Recording…", "Shortcut set to Control+J. Already used by Jump to.", refusals, "Recording cancelled.", "Shortcut cleared." |
| Focus | The shell's inward ring around the recorder; the clear button's own ring; focus never trapped (Tab passes) |
| No script | A labelled text field holding the string |

## Do not

- **Do not take Tab.** A recorder that swallows Tab traps keyboard users.
- **Do not offer the browser's own shortcuts.** They are reserved because the
  page never gets them.
- **Do not show symbols to assistive technology.** "⌘" is read inconsistently;
  the words are what is said.
