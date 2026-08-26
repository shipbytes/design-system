# Alert

A block that tells the reader something about **the page they are on** — a save
succeeded, a field is wrong, a plan limit is close.

Not a toast. A toast is transient, floats above the page and is dismissed by
time; an alert sits in the flow, moves with the content it is about, and stays
until the reason for it is gone. This system has no toast component yet.

## Anatomy

```
┌───────────────────────────────────────────┐
│ ⚠  Payment failed                    ✕   │  ← icon · title · optional dismiss
│    We could not charge the card ending    │  ← slot
│    4242. Update it to keep the plan.      │
└───────────────────────────────────────────┘
```

| Part | Token | Why |
|---|---|---|
| Fill | `<tone>-wash` | **Not the tint.** See below. |
| Edge | `<tone>/25` | The tone at 25%, so the border belongs to the wash rather than outlining it. |
| Text | `on-<tone>-tint` | The label colour proven against the tint, which is darker than the wash — so it passes here with room to spare. |
| Icon | `size-4.5` | 18px. Between the 16px body mark and the 20px control mark, aligned to the first line by `mt-0.5`. |
| Shape | `radius-control` | 8px. An alert is control-scale furniture, not a panel. |

## Wash, not tint

A badge and an alert take the same tone and **different strengths**, and this is
the rule that makes both work:

> Tint is for a small surface. Wash is for a large one.

The badge-strength fill reads as shouting at panel size — the same colour that
reads as a quiet annotation at 20px reads as an emergency across 600px. The area
does the amplifying, so the colour has to come down to compensate.

## Tones

`accent` (default) | `success` | `warning` | `danger`

Each carries a default icon, so the common case needs no `icon` prop:

| Tone | Default icon | Means |
|---|---|---|
| `accent` | `information-circle` | Something the reader should know |
| `success` | `check-circle` | Something they asked for worked |
| `warning` | `exclamation-triangle` | Something will go wrong if ignored |
| `danger` | `exclamation-circle` | Something already went wrong |

Note there is no `neutral`. An alert with no tone is a paragraph.

An unknown tone falls back to `accent` rather than rendering an unpainted box.

## Title

`title` gives the bold first line; without it the alert is a single line and the
slot is the whole message.

**One sentence does not need a title.** A title that restates the sentence below
it is two copies of the same message, and the reader has to read both to find out
they were the same.

## Dismissible

Off by default. On, it renders a close control.

**Only for alerts the reader may safely ignore.** A blocking error has no dismiss,
because dismissing it hides the reason the thing they asked for did not happen —
and the reader is then looking at a page that simply did not do what they said,
with no explanation anywhere.

The rule of thumb: if the alert is the *answer* to something the reader did, it is
not dismissible. If it is context they did not ask for, it may be.

## Accessibility

**`role` follows the tone, and this is the whole a11y contract of the component:**

| Tone | Role | Why |
|---|---|---|
| `danger` | `alert` | An assertive live region. It interrupts whatever the screen reader is currently saying. |
| everything else | `status` | A polite live region. It waits its turn. |

Interrupting is right for a failure and rude for a confirmation. A `role="alert"`
on every success message means a reader cannot finish a sentence while the page
is going well.

Consequences worth knowing:

- **A live region only announces content that arrives after it is in the DOM.**
  An alert rendered with the page is read in normal document order, not announced.
  That is correct for server-rendered validation summaries.
- **To announce, insert the element** — do not render it hidden and reveal it.
  Toggling `hidden` on an existing live region does not reliably announce.
- The icon is decorative and hidden; the tone is never the only carrier of
  meaning, because the message itself says what happened.
- The dismiss control has an `aria-label` of "Dismiss" — it is icon-only, so it
  needs one.

## Do not

- **Do not make a blocking error dismissible.** See above.
- **Do not stack alerts.** Three alerts is a page with no message. Combine them, or
  put the detail next to the field it belongs to.
- **Do not use one for field validation.** That is `input`'s `error` prop, which
  wires `aria-describedby` and `aria-invalid` to the control itself. An alert
  above the form makes the reader hunt for which field it meant.
- **Do not use `danger` for a warning.** `alert` interrupts; spend it on failures.
- **Do not put a form or a menu inside one.** A live region announces its whole
  contents when it changes.
- **Do not reach for it as a toast.** It has no timer, no stacking and no fixed
  position, and giving it those makes it something else.
