# Input

A Catalyst-style double layer. In the app this is a ~40-line class string pasted
at roughly 40 sites; it is one component.

## Why two layers

The visible field is built from two pseudo-elements rather than borders on the
control itself:

- **`::before`** — inset by 1px, carries the white fill and `shadow-raised`, with a
  radius of `calc(radius-control - 1px)` so it nests cleanly inside the outline.
- **`::after`** — sits at `inset: 0` and carries the focus ring, drawn
  **inside** via `ring-inset`.

Drawing the ring inward is the point: the box never changes size on focus, so
nothing reflows and adjacent fields do not shift. A plain `outline` would.

## Anatomy

```
Label                       ← body, medium, fg
┌──────────────────────────┐
│ [icon]  value / placeholder │   height = control token
└──────────────────────────┘
Helper text                 ← meta, fg-muted
```

Helper text is **replaced by** the error, never stacked with it. Two lines of
guidance under one field is one line too many.

## States

| State | Treatment |
|---|---|
| default | `border`, white fill, `shadow-raised` |
| hover | border to `fg/20` |
| focus | 2px inset `focus-ring`, at every width (it was `sm:` and up until batch 06, which left a phone-width field with no indicator but the caret); border unchanged |
| filled | identical to default — a filled field is not a state |
| error | border `danger`, message below in `danger` with a leading icon |
| disabled | `surface-subtle` fill, `fg-subtle` text, no shadow |
| readonly | `surface-subtle` fill, `fg-muted` text, no shadow, no hover border; focus ring kept |

**Readonly reads from the attribute**, not a prop, so it still reaches the
element: it stays focusable, selectable and submitted, which is the whole
difference from disabled. Its text is `fg-muted` rather than disabled's
`fg-subtle` because the value is there to be read; `fg-muted` on
`surface-subtle` is a checked AA pair. A native select ignores `readonly`, so
`as="select"` does not get the look either: it would claim a lock the browser
does not honour.

Dark drops the shadow entirely — the border carries the edge instead.

## Sizes

Same tokens as [button](button.md): `control-lg` below the `sm` breakpoint,
`control-md` above it. **16px text below `sm` is not a style choice** — iOS
zooms the page when a field under 16px receives focus.

## Types

`select` shares the shell exactly; only the caret differs, and it is
`pointer-events: none` so clicks reach the control. `textarea` keeps the border
and radius, grows its padding, and lets height run free.

Checkbox, radio and switch are separate specs. The rule between them: a
**switch commits immediately**, a **checkbox waits for Save**. Picking the wrong
one lies to the user about whether their change persisted.

## Reveal

`reveal` on `type="password"` adds a 44px-wide button (36px above `sm`) at the
trailing edge, inside the shell, and reserves that width as right padding so
the dots never run under it. Off every other type and off by default, and when
it is off the markup is byte for byte the 1.x field (`InputUnchangedTest`
holds it to a fixture).

| | |
|---|---|
| Role | `button`, `type="button"`, so Enter in the field still submits |
| Name | "Show password", constant; `aria-pressed` carries the state; `aria-controls` the field |
| Icon | `eye` while hidden, `eye-slash` while shown, swapped by CSS off `aria-pressed` |
| Keyboard | Tab reaches it after the field; Enter or Space toggles |
| Focus | A pointer press does not move focus out of the field, and the selection is kept across the type change |
| Submit | The field goes back to `type="password"` when its form submits, so the browser's save-password prompt sees a password field |

**The name does not change with the state.** APG's toggle-button pattern: a
name that flips to "Hide password" while `aria-pressed` is also true reads as
"Hide password, pressed", which answers the question twice and contradicts
itself on the way. The icon carries the visual change.

Needs Alpine and the package's script (`dsPassword`). Without them the button
throws a named error on init and the field stays a working password field.

## Clearable

`clearable` on a text-like `input` (search, text, email, url, tel) adds a ✕
button at the trailing edge, inside the shell, that empties the field. It is
shown only while the field has a value, by CSS: the field gets a placeholder (a
no-break space when the host gives none) and the button hides on
`:placeholder-shown`, so it is right before any script runs and after every
keystroke with nothing to listen to. The browser's own ✕ on a search field is
removed (theme.css), so there is one. Off by default, off for passwords,
disabled and read-only fields, and when it is off the markup is byte for byte
the 1.x field (`InputUnchangedTest`).

| | |
|---|---|
| Role | `button`, `type="button"` |
| Name | "Clear" and the field's label ("Clear Search"); `aria-controls` the field |
| Keyboard | Tab reaches it after the field; Enter or Space clears |
| Focus | Back in the field, so the reader types the next search at once |
| Events | `input` then `change` on the field, bubbling, so `wire:model`, `x-model` and a host's own listener all see the field emptied |

Needs Alpine and the package's script (`dsClear`). Without them the button is
there and does nothing; the field works.

Escape does not clear. A search field's Escape is the browser's own, and
inside a popover or a modal Escape closes it: taking the key here would make it
mean two things depending on where the field is.

## Expanding

`expanding` turns a search field into an icon button that grows into the
field: `resources/views/partials/input-expanding.blade.php`, included only
when the prop is set, so without it the markup is byte for byte the 1.x field
(`InputUnchangedTest` also renders `expanding` false).

```
div[data-ds-search][x-data=dsSearch]          push: w-fit; overlay: a fixed square, relative
├─ label.sr-only[for=id]
└─ span.group/search                           the width: w-11 (sm:w-9), w-64 open; overlay: absolute end-0
   ├─ button[tabindex=-1][aria-label][aria-controls][data-ds-search-button]
   └─ span (field shell, z above the button)
      ├─ input[type=search][enterkeyhint=search][data-ds-search-input]
      ├─ button (dsClear)                        with clearable
      └─ span (magnifier, pointer-events-none)
```

| | |
|---|---|
| Collapsed | The field is the square: start padding is the icon's, end padding 0, so no text area shows; `cursor-pointer` |
| Open | `has-[input:focus]` or `has-[input:not(:placeholder-shown)]`: w-64, capped at `100vw - 2rem`. Not `:focus-within`, which the button after Escape would hold open |
| Motion | `motion-safe:transition-[width]`, popover-enter tokens opening, popover-leave closing |
| Push / overlay | Push widens in the row; overlay keeps its square in the row and the box grows from the end over what is before it, `z-20`, so the page never shifts |
| Escape (dsSearch) | With text: clear, `input` and `change`. Empty: focus the button, which collapses it. Stopped only when it did something, so a second Escape from the button reaches a popover or modal |
| Button (dsSearch) | A press focuses the field; a printable key typed while it has focus goes into the field |
| shortcut (dsSearch) | `shared/shortcut.js` `parse`/`matches`, as the command palette; a bare key is ignored inside another editable field |
| Model | `x-model` and `wire:model` go on the native field, as on any input |

The field is the only Tab stop. The button, under it and out of the tab order,
is what a screen reader's browse mode finds and presses, and where Escape puts
focus, so the search collapses with focus still in it. `help` and `error` are
not drawn: an expanding field is a search, not a form field.

## Accessibility

- Every field has a real `<label for>`. Placeholder is not a label — it vanishes
  on input and fails contrast.
- Error text is tied to the field with `aria-describedby`, and the field carries
  `aria-invalid="true"`.
- The error must say what to do ("Title must be at least 3 characters"), not
  just what is wrong ("Invalid").
- **Known deviation:** the resting outline is ~1.1:1 against the surface, below
  the 3:1 WCAG 1.4.11 asks of a component boundary. Inherited from Catalyst,
  where affordance comes from the fill and shadow. The focus ring does pass, on
  every surface. Recorded in [color.md](color.md) so it stays a decision.

## Do not

- **Do not reveal by default.** Shoulder-surfing is the reason the field masks.
- **Do not make every field clearable.** It is for a field the reader empties
  often: a search, a filter. On a form field it is a button that destroys
  input, one Tab stop from the next field.
- **Do not use it for a PIN.** A short fixed-length code is
  [otp-input.md](otp-input.md), with `masked`.

## Porting note

The two-pseudo-element construction is pure CSS and carries across frameworks
unchanged. What does not carry is the binding — `wire:model.live.debounce.300ms`
on a search field and `wire:model.blur` on a date encode a per-field network
policy that has no equivalent outside Livewire, and has to be rebuilt as
explicit client state.
