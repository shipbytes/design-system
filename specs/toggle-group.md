# Toggle group

Buttons that hold a pressed state, one of (`single`) or any of (`multiple`),
optionally posting through hidden inputs. Needs Alpine and the package's
script (`dsToggleGroup`, `x-ds-glide.hover`). Covers `toggle`.

## Anatomy

```
╭───────────────────╮
│ [≡]  ≡   ≡        │   ← div[role=radiogroup|group]: muted tray
╰───────────────────╯
  └ button[role=radio aria-checked] > icon + words + input[type=hidden]
```

- **Group**: `div[data-ds-toggle-group]`, `role="radiogroup"` (single) or
  `role="group"` (multiple), `aria-label`, `x-data="dsToggleGroup({type})"`,
  `x-modelable="dsValue"` beside the host's binding, `x-ds-glide.hover`. The
  muted tray; inside a toolbar, no tray of its own (`in-data-ds-toolbar:`).
- **Highlight**: as in toolbar.
- **Toggle**: `button[type=button][data-ds-toggle][data-ds-value]`, also a
  toolbar item and a glide item. Single: `role="radio"` and `aria-checked`.
  Multiple: `aria-pressed`. Holds `input[type=hidden][data-ds-toggle-input]`
  named `name` (single) or `name[]` (multiple), `disabled` unless the toggle
  is pressed and enabled.

## States

| State | Treatment |
|---|---|
| off | `text-fg-body` |
| on | `bg-fg/10 text-fg` (`aria-pressed:` / `aria-checked:`) |
| hover, focus, pressing | as in toolbar: the highlight |
| disabled | `text-fg-muted`, skipped, input disabled |

## Behaviour

- **Single**: pressing an off toggle turns it on and the other off; pressing
  the on toggle does nothing. Standalone, the arrows move AND press, as radios
  do, and the tab stop is the pressed toggle.
- **Multiple**: pressing flips one toggle. The arrows only move focus.
- Every press updates the ARIA state and the hidden input, writes `dsValue`
  (a string, or an array in DOM order), and dispatches `input` and `change`
  from the toggle.
- The host setting the bound value presses and releases toggles to match,
  without events.
- **Standalone** the group runs its own roving tabindex (both arrow axes,
  wrapping, skipping disabled, Home/End). **Inside a toolbar** it does not; its
  toggles are the toolbar's items.

## Sizes

`sm` (32px) and `md` (36px), as toolbar. Unset inside a toolbar, the
toolbar's.

## Choices and why

- **`type` defaults to `single`,** the more common case (alignment, view), and
  the one that takes the stricter semantics.
- **Single cannot be emptied by pressing again.** That is how radios behave,
  and what `role=radio` promises.
- **Both arrow axes standalone,** since the group lays out either way and has
  no orientation prop; a toolbar's orientation governs once inside one.
- **State from PHP.** `pressed` sets the ARIA attribute and the hidden input's
  `disabled` before any script, so a page without the script posts what it
  shows.
- **Hidden inputs, not checkboxes.** The toggle is a button so it can carry
  `aria-pressed` and be a toolbar item; the input under it is only what posts.

## Accessibility

| | |
|---|---|
| Role | single: `radiogroup` of `radio`; multiple: `group` of buttons with `aria-pressed` |
| Name | `label`; each toggle its label (sr-only with `iconOnly`) |
| State | `aria-checked` / `aria-pressed`; `disabled` |
| Keyboard | Standalone: one Tab stop, arrows, Home/End, Space/Enter. In a toolbar: the toolbar's |
| Focus | Ring on the toggle, highlight under it |
| Not colour alone | The filled face |
| Motion | Highlight only, `motion-safe` |

## Do not

- **Do not use for actions.** Use buttons.
- **Do not leave `pressed` to the script.** The first paint must be right.
- **Do not add `role` or `aria-pressed` by hand** on a toggle; `type` decides.
