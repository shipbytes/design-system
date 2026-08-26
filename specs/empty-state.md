# Empty state

What a region says when it has nothing in it. The table with no rows, the search
with no matches, the brand-new account.

## Anatomy

```
        ╭────╮
        │ 📄 │      ← optional mark, 48px tile
        ╰────╯
     No reports yet          ← title: text-section, semibold
Reports appear here once     ← description: text-body, fg-muted, max-w-sm
   a project has run.
       [ New report ]        ← action slot
```

| Part | Token | Why |
|---|---|---|
| Frame | `border-dashed` + `border-strong` | Dashed says *provisional*: something belongs here and does not exist yet. A solid border says this is a finished panel. |
| Title | `text-section` | Not `heading`. This sits inside a page that already has one, and a second display-weight line competes with it. |
| Description | `fg-muted`, `max-w-sm` | The measure matters — a sentence stretched across a table's full width is unreadable, and an empty state usually replaces something wide. |
| Mark | tone tint | 48px circle. |

## Tone

`neutral` (default) | `accent` | `success` | `warning` | `danger`

**An empty state is not an error.** `neutral` is the default because most
emptiness is simply the beginning — nothing has gone wrong, and a coloured tile
on a brand-new account tells the reader it has.

Spend a tone when the emptiness has a *cause*: `danger` for an import that
failed, `accent` for a search that matched nothing, `warning` for something that
emptied because it expired.

## `bare`

Drops the border and the padding, for an empty state that already sits inside a
`panel` or a `table`. Two nested frames is a box in a box, and the dashed edge
starts to look like a drop target.

## Writing one

The parts are load-bearing and mostly a writing problem:

- **The title says what is not here**, in the reader's words. "No reports yet",
  not "Empty" and not "No data".
- **The description says why, or what to do.** One sentence. If there is nothing
  useful to say, leave it out rather than restating the title.
- **The action is the thing that ends the emptiness.** An empty state with no way
  out of it is a dead end — and if the reader genuinely cannot act (no
  permission, nothing to create), say *that* in the description.

"No results" with no way to widen the search is the most common broken one.

## Accessibility

- Nothing here is a live region. If a region becomes empty *in response to
  something the reader did* — a filter, a search — the host announces it. The
  component cannot know a change happened.
- The mark is decorative and hidden: the title says the same thing in words, and
  an icon announced first delays the sentence that matters.
- The title is a `<p>`, not a heading. It is not a section of the document, and
  injecting an `<h3>` into an unknown outline breaks the heading order of the
  page it lands in.

## Do not

- **Do not use it for a loading state.** That is [skeleton.md](skeleton.md) —
  "nothing here" and "not here yet" are opposite claims, and showing the wrong
  one makes a slow page look like a broken one.
- **Do not use it for an error with a retry.** That is an [alert](alert.md); the
  dashed frame says "nothing belongs here yet", which is untrue.
- **Do not put more than one action in it**, plus at most one quiet secondary.
  Three buttons is a decision, and the reader came here to find something.
- **Do not illustrate it.** The 48px mark is the budget. A spot illustration is
  an asset that has to survive both themes and every rebrand.
