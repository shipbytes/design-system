# Tag input

Free text committed as removable chips. Different from
[combobox.md](combobox.md) `multiple`, which chooses from a known list: here the
values are whatever the person types.

## Anatomy

```
Invite by email                                  ← label, names the chip list
┌─────────────────────────────────────────────┐
│ (maya@example.com ✕) (li@example.com ✕) Add… │  ← chips and the text box share one frame
└─────────────────────────────────────────────┘
maya@ is not an email address.                   ← the note, in the help line's place
```

- **The frame** is built from the field recipe's pieces (padding, text, border,
  tint) on a wrapping flex row, inside the recipe's shell, so it is the input's
  height, border, focus ring and disabled look.
- **Chips** are the combobox chips: same classes, same remove button.
- **The chip list** is a `ul role="list"` laid out as `contents`, so its chips
  flow in the frame's row beside the text box rather than on a line of their
  own. Hidden when empty.
- **Each chip posts** a hidden `name[]` input. PHP renders `value` as chips, so
  the list posts without the script.
- **A `template`** holds one empty chip, cloned by the script, so every class is
  a literal in the Blade file.

## States

| State | Treatment |
|---|---|
| default, hover, focus | As ds::input; the ring when focus is anywhere in the frame |
| a note showing | The note (danger, with the icon) replaces help; help returns when it clears |
| error | The error border, and the message replaces help |
| disabled | Flat shell; the text box, the remove buttons and the hidden inputs disabled |

## Behaviour (`dsTagInput`)

| Input | Result |
|---|---|
| Enter, with text | Commits the text. With no text, Enter is left alone and submits the form |
| A separator character (default `,`) | Commits what is before it |
| Leaving the frame | Commits what is typed |
| Paste | Splits on the separators and on line breaks; commits each |
| Backspace in an empty box, or Left at its start | Focuses the last chip's remove button |
| Backspace or Delete on a chip | Removes it; focus returns to the text box |
| Left / Right on a chip | Previous / next chip; Right past the last returns to the text box |
| The ✕ | Removes the chip; focus returns to the text box |

**Refusals stay in the box.** Over `max`, invalid under `validate`, or a
duplicate under `unique` (compared without case): the entry is left in the text
box and the note says why. In a paste, the refused entries are left, joined, and
the accepted ones are added. Nothing typed disappears silently.

`validate` takes `'email'` or null and throws on anything else. The email check
is the browser's own, from an `<input type="email">` the script keeps off-page.
A pattern prop was rejected: a regular expression has to mean the same in PHP
and JavaScript, and a refusal needs a message a person can read.

`separators` is `'Enter'` plus any single characters, and throws on anything
longer. A phone keyboard sends a comma as text rather than as a key, so
separators are also caught in the `input` event.

## Accessibility

| | |
|---|---|
| Chip list | `role="list"` labelled by the `label`, so its size is announced |
| Text box | The labelled field; described by the note and the help or error |
| Remove | A `button` named "Remove {tag}" |
| Announcements | A polite, visually hidden live region: "Added {tag}.", "Removed {tag}." |
| The note | Its own polite live region, so a refusal is read when it appears |
| Keyboard | Tab passes each chip's ✕ in order, then the text box; see the table above |
| Focus | Removing a chip returns focus to the text box, never to `body` |
| Phone | With `validate="email"`: `inputmode="email"`, no autocapitalise, no spellcheck |

## Binding

`wire:model*` and `x-model*` on the tag land on the root, beside
`x-modelable="dsValue"`, Alpine's core directive for a component that is a
form control. `dsValue` is the scope's one exposed name: an array of the chips'
values, set by the behaviour after every add and remove, and watched, so a list
the host sets is rendered as chips (from the template, blanks dropped, without
an announcement or the `max`/`unique`/`validate` checks) and handed back
normalised. The prefix matters: the host's expression on the root is read in
this scope, and an unprefixed name would shadow a host property of the same
name. On first start the host's value wins over the server-rendered `value`,
which is how `x-model` treats any field. The hidden inputs still post.

## Do not

- **Do not use it to pick from known values.** That is a combobox.
- **Do not let refusals vanish.** The note is the contract.
- **Do not rely on `validate`.** Validate `name.*` on the server.
