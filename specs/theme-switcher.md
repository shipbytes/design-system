# Theme switcher

Light, dark, or whatever the system says: the reader's choice, kept in
localStorage and applied to `<html>`. Needs Alpine and the package's script
(`dsTheme`). Its partner, `<x-ds::theme-script />`, applies the stored
choice before the first paint and needs neither.

## Anatomy

```
segmented                                   button
Theme                                       ╭───╮
╭──────────────────────────────────╮        │ 🖥 │  ← ds::button, ghost, iconOnly,
│ ☀ Light │ ☾ Dark │ 🖥 System ▓▓ │        ╰───╯    aria-label "Theme: System"
╰──────────────────────────────────╯
  ds::segmented-control, three radios
```

- **Root**: `div[data-ds-theme-switcher][data-variant]`,
  `x-data="dsTheme({ storageKey, transition })"`, `inline-block`.
- **segmented**: a `ds::segmented-control` named by `label`, three
  `ds::segment` radios (light, dark, system, each with its icon), bound with
  `x-model="dsTheme"`. The radios' `name` is random per switcher unless given.
- **button**: a `ds::button variant="ghost" iconOnly`, its three icons in
  `contents` spans toggled by `x-show`, named `"{label}: {choice}"`, pressed
  to `dsCycle()`.
- **ds::theme-script**: one inline `<script>` (a class component), nonce from
  the prop or from Laravel's Vite helper.

## Variants

Two of the three candidates, and a third shape for inside a menu.

| Variant | For | Why |
|---|---|---|
| `segmented` (default) | settings pages, an account menu with room | All three choices visible, the current one plain to see, any of them one press away. It is radios, so it is the segmented control, with its keys and its focus ring |
| `button` | a header, a toolbar | One square. It steps light → dark → system → light; the icon shows the current choice and the name says it |
| `menu` | a row in a menu: ds::user-menu's `theme` slot | A `role=group` named by `label`, the label shown aria-hidden, then three `button[role=menuitemradio][tabindex=-1]` icon buttons with `aria-label` and `aria-checked`. The menu's arrow keys stop on each; a press sets the theme and closes the menu like any item |

**Not a dropdown.** It hides three short choices behind a press, costs two
presses where the others cost one, and puts a menu, with a menu's keyboard
model, round what is one value of three.

## States

| State | Treatment |
|---|---|
| before Alpine | the control draws "system" (the server cannot read localStorage); the page is already right, from the theme script |
| light, dark, system | the segment checked, or the icon and name of the button |
| changing | the page crossfades, `duration-dialog-enter ease-enter`, where `startViewTransition` exists and motion is allowed; otherwise at once |

## Behaviour

Choosing (a segment, or a press of the button):

1. stores it: `localStorage[storageKey]` is `light` or `dark`; `system`
   removes the key. Storage that refuses is ignored; the page still changes.
2. paints it: exactly one of `dark` and `light` on `<html>`, and
   `style.colorScheme` to match. `light` as well as `dark` because the
   tokens follow the system's preference when the root has neither. For
   `system`, which one is read from `prefers-color-scheme`.
3. moves every other switcher on the page with the same key to the same
   choice, and other tabs follow through the `storage` event.

On `system`, a change of `prefers-color-scheme` repaints the page as it
happens. A chosen `light` or `dark` ignores it.

The first switcher to start on a page reads the stored choice and, if the
root has no theme class yet (no theme script on the page), paints it. A later
switcher on another key reads its own key and leaves the page alone until it
is used.

The change is wrapped in `document.startViewTransition()` when the browser
has it, `prefers-reduced-motion` is not `reduce`, `transition` is on, and the
page would actually change. `data-ds-theme-changing` is on the root for the
length of it; theme.css times `::view-transition-old/new(root)` to the
dialog's enter duration and curve under that attribute and turns the
animation off under reduced motion. The browser's own crossfade, nothing
drawn.

**The theme script** reads the same key with the same rules, inside
`try`, and sets the same class and colour scheme. It must run before the
stylesheet: in the head, above the CSS.

## Choices and why

- **`system` is the absence of a choice, not a stored value.** A reader who
  never chose and one who went back are the same reader.
- **The class on `<html>`, not a media query alone.** The `dark:` variant is
  class-based, and a chosen theme has to beat the system's.
- **A separate pre-paint script, with no Alpine.** Alpine runs after the
  first paint; a stored dark theme applied only by it flashes light on
  every load.
- **A random radio name.** Two segmented switchers on one page with one name
  would be one radio group, and checking one would uncheck the other.
- **Crossfade only.** No circle wipes from the pointer: a theme change is not
  an event worth staging.

## Limits

- The control draws "system" until Alpine starts. The page itself is right
  from the first paint with the theme script; without it, the page is in the
  system's theme until Alpine starts.
- One theme per document. Two keys on one page give two independent choices
  for one `<html>`; the last one used wins.
- The radios have a name, so a segmented switcher inside a `<form>` posts its
  value with it.

## Accessibility

| | |
|---|---|
| Role | segmented: a `fieldset` of radios, its `legend` the `label`. button: a `button` |
| Name | segmented: `label` ("Theme"). button: `"{label}: {choice}"`, kept current |
| State | segmented: the checked radio. button: in the name |
| Keyboard | segmented: Tab to the group, arrows to choose. button: Enter or Space steps |
| Focus | the system ring: the segment's label with `has-focus-visible:`, the button's own |
| Motion | the page crossfade is skipped under reduced motion |

## Do not

- **Do not leave out the theme script.** Without it a stored dark theme
  flashes light on every page load.
- **Do not put the theme script after the stylesheet.** It has to run before
  anything paints.
- **Do not use two storage keys on one page** for one theme.
