# Getting started

Install the package, add three lines of CSS and one script include, and every
component works.

> **This page is the Blade package**, and the Blade package is the system. If you
> are not building in Blade, take `@shipbytes/design-tokens` from npm for the
> CSS and the values: `@shipbytes/react` is no longer maintained and
> `@shipbytes/vue` will not be built. See
> [which package do I need?](../README.md#which-package-do-i-need). A Blade
> project needs nothing from npm at all.

## 1. Install

```bash
composer require shipbytes/blade-ui
```

Laravel discovers the service provider automatically. There is nothing to
register.

**Requires** PHP 8.2+, Laravel 11/12/13, and **Tailwind CSS v4**.

## 2. Add the CSS

In `resources/css/app.css`:

```css
@import 'tailwindcss';
@import '../../vendor/shipbytes/blade-ui/dist/tokens.css';
@import '../../vendor/shipbytes/blade-ui/dist/theme.css';
@source '../../vendor/shipbytes/blade-ui/resources/views';
```

Three imports and one `@source`. There is **no npm install** — the composer
package ships the compiled CSS.

### The `@source` line is not optional

This is the single most likely thing to go wrong, and it fails **silently**.

Tailwind does not scan `vendor/`. Without that line, every class the components
emit is discarded as unused, and the whole system renders as unstyled HTML.
Nothing errors, because "unused" is not a failure — you just get a page of plain
black text on white and no clue why.

If your components render but look like nothing, this line is missing.

> `theme.css` brings its own `@custom-variant dark`, so you do not need to
> declare dark mode yourself. Three imports, not four.

### Fonts

The system's one typeface is **Inter** (`--font-sans`), and the package does
not ship the webfont: load it yourself, at weights 400, 500 and 600. In a
layout's `head`, for example:

```html
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet">
```

**A new Laravel 12 app overrides it.** The skeleton's `resources/css/app.css`
ends with this block:

```css
@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji',
        'Segoe UI Symbol', 'Noto Color Emoji';
}
```

Coming after the three lines above, it wins over the system's `--font-sans`,
and since nothing loads Instrument Sans either, every page silently falls back
to the system font. **Delete that block** (or move it above the imports if you
really want Instrument Sans, and load that font yourself).

## 3. Add the script

In your layout's `head`, **above** Alpine's script tag:

```blade
<head>
    …
    <x-ds::scripts />
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
</head>
```

The package ships one small script of its own: the behaviours that several
components share, registered with Alpine under a `ds` prefix. `ds::scripts`
writes a script tag pointing at a route the package registers, at a URL that
carries a hash of the file:

```html
<script src="https://your-app.test/blade-ui/blade-ui.0f3c9a1e5b7d2c48.js" data-ds-scripts></script>
```

The browser caches it for a year and never asks again, and a `composer update`
that changes the script changes the URL, so nobody keeps running the old one.
There is nothing to publish and no build step. A component that needs it says
so at the top of its page; put the include in the layout once and it is there
for all of them.

**The route needs nothing from you.** It sits outside the `web` middleware
group, so it starts no session and sets no cookie, and it works under
`php artisan route:cache` and in an app installed in a subdirectory. If
`/blade-ui` clashes with a path of yours, change the prefix after
`php artisan vendor:publish --tag=blade-ui-config`:

```php
// config/blade-ui.php
'script' => ['prefix' => 'assets/ds'],
```

**A page that cannot reach the route** (a static export, an HTML email
preview, a host whose routing will not take a package's) can carry the script
in the page instead, about 217 kB (55 kB compressed), uncached:

```blade
<x-ds::scripts inline />
```

**Above Alpine, not below it.** The script registers when Alpine announces it
is starting (`alpine:init`), which Alpine does once, before it sets up a single
element. Included after Alpine has started, it registers too late for anything
already on the page. The tag carries no `defer` or `async`, so the browser runs
it where it stands, before the deferred Alpine below it; do not add either.

**Loading it twice is harmless** — two layouts that both include it, or the
include plus the Vite import below. The second copy sees the first and does
nothing.

### With Livewire

Livewire 3 and 4 bring their own Alpine, so you do not load Alpine yourself.
Put `<x-ds::scripts />` in the `head` and nothing else changes: Livewire's
script starts Alpine once the page has loaded, and the behaviours are registered
by then.

### If you bundle Alpine yourself

When Alpine comes from your own JavaScript rather than a script tag, register
the behaviours as a plugin, before `Alpine.start()`:

```js
// resources/js/app.js
import Alpine from 'alpinejs';
import bladeUi from '../../vendor/shipbytes/blade-ui/dist/blade-ui.js';

Alpine.plugin(bladeUi);
Alpine.start();
```

The same goes for Livewire bundled through Vite with `@livewireScriptConfig`:

```js
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import bladeUi from '../../vendor/shipbytes/blade-ui/dist/blade-ui.js';

Alpine.plugin(bladeUi);
Livewire.start();
```

Then `<x-ds::scripts />` is not needed, and harmless if it stays.

### With a Content Security Policy

A CSP that allows scripts by nonce needs the nonce on the include as well,
linked or `inline`:

```blade
<x-ds::scripts :nonce="$cspNonce" />
```

If you already give Laravel's Vite helper a nonce (`Vite::useCspNonce()`), the
include takes that one and you pass nothing.

### What it registers

`dsClipboard` is yours to use in your own markup as well as through the
components. The others work against the markup their component renders,
found by its `data-ds-*` attributes, and are not meant to be written by hand:
use the component.

| Name | What it does |
|---|---|
| `dsClipboard` | Behind `ds::copy-button` (and so `ds::code-block`'s copy), and yours to use directly: `x-data="dsClipboard({ text: '…' })"`, or `{ target: 'id-or-selector' }` to copy an element's text or a field's value as it is at the moment of the press. `copy()` copies that, or `copy(value)` copies `value`, and resolves to `true` or `false`. `copied` is `true` for 1.5 s after a copy. No UI of its own. Falls back to the older copy command where the async clipboard API is missing, as on a plain-`http` page. |
| `dsOtp` | Behind `ds::otp-input`. Moves characters and focus between the boxes, spreads a paste, keeps the posted field joined, dispatches `complete`, and binds `wire:model` / `x-model` both ways. |
| `dsTagInput` | Behind `ds::tag-input`. Turns typed and pasted text into chips, refuses with a reason, announces adds and removes, and binds `wire:model` / `x-model` both ways. |
| `dsNumberField` | Behind `ds::number-field`. The step buttons: one press, press-and-hold, the disabled state at a bound. |
| `dsPassword` | Behind `ds::input`'s `reveal`. Toggles the field it `aria-controls` between hidden and shown, and hides it again on submit. |
| `dsPasswordStrength` | Behind `ds::password-strength`. Scores the input named by `for` and sets `data-level` and the words. |
| `dsToolbar` | Behind `ds::toolbar`. One Tab stop for the toolbar, with the arrow keys, Home and End moving between its buttons. |
| `dsToggleGroup` | Behind `ds::toggle-group`. Presses and releases toggles, keeps the hidden inputs and the ARIA state in step, binds `wire:model` / `x-model` both ways, and gives a group outside a toolbar its own arrow keys. |
| `x-ds-glide` | A directive, behind `ds::segmented-control`, `ds::toolbar` and `ds::toggle-group`: the highlight that slides to the chosen item, or with `.hover` follows the pointer and keyboard focus. |
| `dsPopover` | Behind `ds::popover`, and `ds::filter-bar`'s `collapsible` below `md`. Opens and closes it from its trigger, keeps the trigger's `aria-expanded` in step, moves focus in on open and back on Escape, and closes on Escape, a press outside, or focus leaving. Binds the host's `open` both ways through `dsOpen`. |
| `dsHoverCard` | Behind `ds::hover-card`. Opens the card after the pointer or keyboard focus rests on the trigger for 500 ms, keeps it open while the pointer crosses into it, closes it 300 ms after both are left or at once on Escape, and never opens it on touch. |
| `dsContextMenu` | Behind `ds::context-menu`. Opens its menu at the pointer on a right-click, below the focused element on Shift+F10 or the Menu key, and at the finger on a touch held for 500 ms; leaves the browser's menu alone on fields and links unless `native` is false; closes on Escape, Tab, a choice or a press outside, and gives focus back. Exposes `dsTarget`, the element that was pressed. |
| `dsTheme` | Behind `ds::theme-switcher`. Keeps the choice (light, dark, system) in localStorage, puts `dark` or `light` and `color-scheme` on `<html>`, follows `prefers-color-scheme` while on system, keeps every switcher on the page and in other tabs in step, and crossfades with a view transition where the browser has one and motion is allowed. |
| `dsDismiss` | Behind `ds::announcement-bar`'s `dismissible` and `dismissKey`. Collapses the bar on its ✕ or any `data-ds-dismiss` button in it, remembers the dismissal in localStorage under the key, dispatches `dismiss`, and repeats the pre-paint check for a page where the inline one was blocked. |
| `dsConfirm` | Behind `ds::confirm-button`. Swaps the button for the question, Cancel and the confirm control, moves focus to Cancel, puts it back on Cancel, Escape or the timeout, and lets only a click on the control through to your listeners and the form. Dispatches `confirm` and `cancel`. |
| `dsHold` | Behind `ds::hold-button`. Fills the button while it is held by pointer, Space or Enter, rewinds it when let go early, arms it for a second press from the keyboard or assistive technology, and on confirmation dispatches `confirm` and clicks the button once for real. |
| `dsConfirmDialog` | Behind `ds::confirm-popover`, inside its `dsPopover`. Enables Confirm once the typed text matches, lets only a confirming click through, closes the popover after it, and dispatches `confirm`, or `cancel` on any other close. |
| `x-ds-anchor` | A directive, behind `ds::popover`, `ds::confirm-popover`, `ds::hover-card`, `ds::context-menu`, `ds::dropdown`, `ds::select`, `ds::combobox`, `ds::date-picker` and `ds::tooltip`, and yours for an overlay of your own: `x-ds-anchor="{ open: …, reference: …, placement: 'bottom-start' }"`. Places the panel beside its reference with `position: fixed`, so an ancestor's `overflow: hidden` cannot clip it; flips to the other side when the preferred one lacks room, shifts to stay 8 px inside the viewport, and caps the height with a scroll when neither side fits. Follows scrolling and resizing while `open` is true. Writes the side it took as `data-side`. `at: { x, y, width, height }`, measured from the reference's corner, hangs it off that box instead: a point, for a menu at the pointer. |
| `dsRoll` | Behind `ds::counter` when its `value` is bound to Alpine state (`::value="count"`). Follows the expression, formats it as the server does, keeps the hidden text in step, and rolls the digits to each new value. |
| `dsClamp` | Behind `ds::expandable-text` and `ds::code-block`'s `expandable`. Holds the box to its lines, measures whether the content is taller (again on a resize, a content change and when the fonts arrive), shows the Read more / Show more button only then, and keeps its `aria-expanded` and words in step. |
| `dsClear` | Behind `ds::input`'s `clearable`. Empties the field it `aria-controls`, puts focus back in it, and dispatches `input` and `change` on it. |
| `dsSlider` | Behind `ds::slider`. Keeps the fill, `aria-valuetext` and the readouts in step with the native range inputs, formatted as the server formats them; with two thumbs, stops each `minGap` short of the other, moves the nearer one on a press on the track, and shows the tooltip while dragging; binds `wire:model` / `x-model` (a number, or the pair) both ways. |
| `dsTimePicker` | Behind `ds::time-picker`. Turns the native time field into a text field with a list of times (the ARIA combobox pattern), reads forgiving typing (`930`, `9.30pm`), shows times in 12 or 24 hours and posts `HH:MM` through a hidden input, marks a time it cannot take invalid without changing it, and binds `wire:model` / `x-model` both ways. |
| `dsInlineEdit` | Behind `ds::inline-edit`. Shows the text as a button and opens a field in its place; Enter, leaving, Save, Cancel and Escape as `submit` says; dispatches `save` with `{ value, previous }` and `cancel`; submits the form it draws for `action`; shows saving and errors from `data-ds-saving` and `data-ds-error` (or `dsSaving` and `dsError`); binds `wire:model` / `x-model` both ways. |
| `dsCommandPalette` | Behind `ds::command-palette`. Opens on its shortcut (`mod+k`: Cmd+K on a Mac, Ctrl+K elsewhere) and on an `open-command-palette` window event naming it, in the modal's layer; keeps focus in the field with `aria-activedescendant` on the active option; filters and ranks the options by label and keywords (CSS `order`, the DOM untouched); dispatches a debounced `search` with `{ query }`; follows a link or dispatches an item's action with `{ value }`; keeps the active option through a re-render of the options. |
| `dsSortable` | Behind `ds::sortable-list`. Drags rows by pointer under capture, the others sliding aside and a placeholder marking the slot, auto-scrolling the nearest scrolling box; picks up, moves and drops by keyboard on the handle, announcing each step; moves a row with its Move up / Move down buttons; dispatches `reorder` with `{ order, moved, from, to }`. |
| `dsSplit` | Behind `ds::split`. Drags a divider under capture with a shield over the page (so an iframe cannot take the pointer), moves it by the arrow keys (Shift for 10%), Home and End, folds a collapsible pane on Enter or a double-click, keeps each divider's `aria-valuenow` in step, and dispatches `resize` while dragging and `resized` after with `{ name, sizes }`. |
| `dsSwap` | Behind `ds::swap-button`. Swaps the button's face on a press, on a window event, or both; swaps it back after `revert` ms (0 keeps it, a toggle with `aria-pressed`); keeps `data-ds-swapped`, `aria-hidden` and `inert` on the faces in step; and announces the face now showing through a polite status region. |
| `dsTeamSwitcher` | Behind `ds::team-switcher`, inside its `dsPopover`. Shows the trigger and the listbox in place of the no-script select or links; filters and ranks the teams as the command palette does (CSS `order`, the DOM untouched); moves between them with the arrow keys, Home and End; on a choice follows the link, or sets the select, dispatches `change` with `{ id }` and, with `action`, submits the form. |
| `dsMoneyInput` | Behind `ds::money-input`. Groups the amount for the locale as it is typed with the caret kept, takes either decimal separator and pasted amounts with symbols, pads and checks `min`, `max` and `step` on leaving, and posts the amount as digits from a hidden input (integer minor units with `minor`). |
| `dsPhoneInput` | Behind `ds::phone-input`, with a `dsPopover` for the country. Shows the searchable country list in place of the no-script select, formats the number with the country's mask, chooses the country from a typed or pasted `+…`, drops the trunk 0 and posts E.164 from a hidden input. Checks length only. |
| `dsShortcutInput` | Behind `ds::shortcut-input`. Records the next key combination pressed on its button as `mod+shift+k`, refuses bare keys and the browser's reserved shortcuts, warns on `conflicts`, never takes Tab, and dispatches `change` with `{ value, previous }`. |
| `dsMention` | Behind `ds::mention-input`. Makes the textarea a combobox while a trigger (`@`) is being typed: the list under the trigger character (measured with a mirror, so wrapped, scrolled and right-to-left text all work), ranked as the command palette ranks, Up, Down, Enter, Tab and Escape while it is open and never while it is not; writes the chosen row in through Undo, posts its id from a hidden input and stops posting it when the text is broken; dispatches a debounced `search` with `{ trigger, query }` from the field; binds `wire:model` / `x-model` to the text. |
| `dsColour` | Behind `ds::color-picker`, with a `dsPopover` for the panel. Shows the swatch and panel in place of the browser's colour input; drags and keys the saturation and brightness area and the hue and opacity sliders; reads typed colours forgivingly (hex, `rgb()`, `hsl()`, the basic names) and leaves text that is not one as typed and invalid; keeps the presets, the contrast ratio and the posted string (in `format`) in step; offers the EyeDropper where there is one; binds `wire:model` / `x-model` to the posted string. |
| `dsSearch` | Behind `ds::input`'s `expanding`. Escape clears, then collapses with focus on the icon button; the button and a key typed on it go to the field; `shortcut` opens it from anywhere. |
| `dsCalendar` | Behind `ds::calendar` and `ds::date-picker`. Redraws the month grid the server drew (one day, a range or several; one to three months), with the ARIA date-grid keys (arrows, PageUp/PageDown a month, Shift for a year, Home/End the week); previews a half-made range under the pointer or focus and swaps the ends on an earlier second pick; keeps the posted inputs in step and dispatches `input` and `change` from them; dispatches `month-change` with `{ year, month }`; binds `wire:model` / `x-model` through `x-modelable`. In the date picker, also the popover, the presets, `minDays` / `maxDays` and the Apply / Cancel draft. |
| `dsAvatarUpload` | Behind `ds::avatar-upload`. Takes a photo from Change, a drop or a paste, checks its type and size, and opens the crop dialog: drag, pinch, the wheel, the slider and the keys move and zoom it, Rotate turns it. Apply draws the square crop to a canvas at `outputSize`, puts it in the real file input (so a form posts it and `wire:model` uploads it), dispatches `crop` with `{ blob, width, height }`, and shows it. Remove ticks `{name}_remove`. Nothing is uploaded by the script. |
| `dsTree` | Behind `ds::tree`. One Tab stop for the tree; the arrow keys, Home, End, type-ahead and `*`; opens and closes folders (dispatching `expand` and `collapse`), selects or checks rows with tri-state parents, keeps the posted inputs in step, and binds `wire:model` / `x-model` both ways. |
| `x-ds-roll` | A directive, behind `ds::counter` when its value comes from the server. Rolls the digits when a re-render (a Livewire morph) changes the number; with `.in`, rolls up from zero the first time the counter is half in view. Instant under reduced motion. |
| `x-ds-scroll-shadows` | A directive, behind `ds::scroll-area`. Where the browser cannot draw the edge shadows from CSS (no scroll-driven animations), marks each edge that has more content past it, on scroll and on resize. Does nothing where CSS can. |
| `x-ds-chart` | A directive, behind `ds::bar-chart`, `ds::line-chart`, `ds::donut-chart`, `ds::split-bar`, `ds::waffle-chart`, `ds::activity-rings`, `ds::slope-chart`, `ds::funnel-chart`, `ds::radar-chart` and `ds::treemap` when `interactive` (the default). Shows a readout (label, value, series) beside the mark under the pointer or keyboard focus; makes the marks reachable through one Tab stop per chart, the arrow keys moving along a series and across to the next, Home and End to the ends, Escape putting the readout away; and, on a chart with several series, turns the legend's entries into buttons that show and hide their series without re-scaling anything. |
| `x-ds-shortcut` | A directive, behind `ds::kbd`'s `shortcut` (and so `ds::shortcut-input`'s keys). Redraws a stored shortcut for a Mac, ⌘⇧K in place of Ctrl + Shift + K, with words for screen readers, and again when its `data-ds-shortcut` changes. An enhancement: without it the server's words form stands. |

### When it is missing

The browser console says so, by name:

```
dsClipboard is not registered: add <x-ds::scripts /> above the Alpine script,
or call Alpine.plugin(bladeUi) where Alpine is bundled.
```

The component renders, because the server rendered it, and does nothing when
pressed. In your own `x-data` the same cause reads as Alpine's
`dsClipboard is not defined`.

`x-ds-glide`, `x-ds-anchor`, `x-ds-roll`, `x-ds-chart` and
`x-ds-scroll-shadows` are the five registrations that are silent when missing, on purpose. `x-ds-glide` only adds the sliding highlight, and
`ds::segmented-control`, which uses it without any `ds…` behaviour, works and
posts the same without it: Alpine skips a directive it does not know, and the
chosen segment keeps the face CSS draws. `x-ds-anchor` keeps an overlay on
screen, and the dropdown, select, combobox and tooltip open at
their `placement` without it, as they did before 2.0: near the bottom of the
window or inside a box with `overflow: hidden` they run off the page or are
cut off, and nothing reports it. `x-ds-roll` only animates a counter whose
number the server already printed: without it the new number simply replaces
the old one. A counter bound to Alpine state goes through `dsRoll` instead,
which does report itself, because nothing else would write the number.
`x-ds-chart` only adds the readout, the keyboard and the legend toggles: the
chart itself is drawn by the server as an image named by its summary, which
is what every reader gets without it. `x-ds-scroll-shadows` only draws the
scroll area's edge shadows in a browser whose CSS cannot: the box scrolls the
same without it.

## 4. Use a component

```blade
<x-ds::button>Save changes</x-ds::button>
<x-ds::badge tone="success">Active</x-ds::badge>
<x-ds::input name="email" label="Email address" />
```

Every component lives under the `ds` namespace, so it can never collide with
your own components.

## Dark mode

Put a `dark` class on `<html>`. Nothing else changes — no second set of
classes, no per-component prop.

```html
<html class="dark">
```

How you decide to put it there is yours: a user setting, a cookie, or
`prefers-color-scheme` read in a small inline script.

Or let the package do it. `<x-ds::theme-script />` in the head, **before your
stylesheet**, applies the reader's stored choice (or the system's) before the
page paints, with no Alpine; [`ds::theme-switcher`](components/theme-switcher.md)
lets them choose light, dark or system, and remembers it:

```blade
<head>
    <x-ds::theme-script />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
…
<x-ds::theme-switcher />
```

## Alpine

**You do not need Livewire.** This package depends on `illuminate/support`,
`illuminate/view` and `blade-heroicons`, and nothing else. Every component works
in a plain Blade view.

Forty-three need [Alpine](https://alpinejs.dev) — a single 15 kB script, not a
framework: **modal, drawer, sheet, dropdown, select, combobox, date-picker,
accordion, file-upload, tooltip, sub-nav, user-menu, otp-input, tag-input, number-field,
password-strength, copy-button, toolbar, toggle-group, popover, hover-card,
context-menu, theme-switcher, confirm-button, hold-button, confirm-popover,
tree, expandable-text, slider, time-picker, inline-edit, command-palette,
sortable-list, split, swap-button, team-switcher, money-input, phone-input,
shortcut-input, mention-input, color-picker, calendar, avatar-upload**.
`sub-nav` only to open and close: without Alpine, the section holding the
current page is open and the rest are closed, as the server rendered them.
`tree` likewise draws its folders, selection and links from PHP and posts
what it shows; `expandable-text` shows all of its text. `slider`,
`time-picker` and `inline-edit` are each drawn on a native field that works
and posts without them: a single slider (whose fill stays put) and a range
whose thumbs can then cross, the browser's own time field, and a plain field
inside its form. `command-palette` stays closed and inert without them,
`sortable-list` is a plain list in the server's order that posts that order,
and `split` keeps its panes fixed at their first sizes. `swap-button` shows the
face the server drew and is an ordinary button that still submits or links;
`team-switcher` is a native select in a form with a submit button, or a list of
links when every team has one. `money-input` is a text field posting what
was typed, `phone-input` a select of dial codes and a tel field posting
`name[country]` and `name[number]` rather than E.164, and `shortcut-input` a
text field holding the string. `mention-input` is an ordinary textarea that
posts its text, and the ids of the mentions the server found in it.
`color-picker` is the browser's own colour input beside the text field,
posting `#rrggbb`; with `presetsOnly` it is native radios and needs neither.
A slider's `histogram` stays as the server drew it, and its `inputs` stay
hidden. `date-picker` is the browser's own date field (two for a range),
posting the same names; `calendar` is the month grid as the server drew it,
with native date fields under it that post the value; `avatar-upload` is a
plain file input, posting the original for the server to crop, and a Remove
checkbox. `user-menu` is a dropdown, and so needs Alpine alone.

Eight more use it for **optional props**, and work without them otherwise:
`checkbox`'s `indeterminate`, `nav-item`'s `collapsedWhen`, `toast`'s
`dismiss`, `input`'s `reveal`, `clearable` and `expanding` (which opens and
closes from CSS; Escape and its `shortcut` are the script's), `kbd`'s
`shortcut` (drawn for a Mac), `panel`'s `collapsible`,
`filter-bar`'s `collapsible`, and `announcement-bar`'s `dismissible` (and
`dismissKey`, which implies it). `code-block`'s copy button, on by default,
and its `expandable` need it too; without them the code still shows and
scrolls. Skip those props and you need no JavaScript at all.

`segmented-control` uses Alpine and the package's script only to slide its
highlight. Without them it is a plain radio group with the chosen segment
drawn by CSS.

`counter` uses Alpine and the package's script to roll its digits. Without
them it is the formatted number, and a server re-render still changes it. A
counter whose `value` is bound to Alpine state (`::value="count"`) needs both,
because only the script writes that number.

`bar-chart`, `line-chart`, `donut-chart`, `split-bar`, `waffle-chart`,
`activity-rings`, `slope-chart`, `funnel-chart`, `radar-chart` and `treemap`
use Alpine and the package's script for the readout on hover and focus,
keyboard access to every bar, point, segment, part, ring, stage or tile, and
the legend toggles. Without them each is a static image with a text
alternative written from the data, which is a complete chart. A treemap
whose tiles have links is a group of links either way, each its own Tab
stop.

`dropdown`, `select`, `combobox` and `tooltip` need Alpine, and
use the package's script to stay on screen: with it they flip away from the
window's edge and escape a box with `overflow: hidden`; without it they open
at their `placement`, as before 2.0.

`icon-swap` needs neither: it reads its button's state from CSS.

`scroll-area` needs neither: it scrolls natively and draws its edge shadows
from CSS. Where the browser has no scroll-driven animations, Alpine and the
package's script draw them instead; with neither, there are no shadows.

The pagination views' Livewire variants also use Alpine, for the scroll back to
the top on a page change, and get it from Livewire.

```html
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
```

Thirty-two of them, **otp-input, tag-input, number-field, password-strength,
copy-button, toolbar, toggle-group, popover, hover-card, context-menu,
theme-switcher, confirm-button, hold-button, confirm-popover, tree,
expandable-text, slider, time-picker, inline-edit, command-palette,
sortable-list, split, swap-button, team-switcher, money-input, phone-input,
shortcut-input, mention-input, color-picker, date-picker, calendar and
avatar-upload**, and
`input`'s `reveal` and `announcement-bar`'s dismissal, need Alpine **and** [`<x-ds::scripts />`](#3-add-the-script)
above it, and say so on their page.

**You do not need to add an `[x-cloak]` rule.** Sixteen components hide themselves
with `x-cloak` until Alpine boots, and Alpine does not ship the CSS rule that
makes the attribute mean anything — every project is normally expected to write
it by hand. Since it is *our* components that break without it, `theme.css`
carries it. If you already have your own copy, it is harmless.

Without the rule, every overlay on the page renders in full from first paint
until the script runs: on a page with several modals, all of them at once with
their backdrops. It is the `@source` failure again — nothing errors, and it is
worst on the slow connections you are least likely to be testing on.

**No plugins are needed.** The focus traps are written into the components
rather than delegated to `@alpinejs/focus`, because Alpine silently ignores a
directive it has no handler for — a missing plugin would leave you with a modal
that looks correct and traps nothing.

### The components hold no state

You declare the state; the component reads it. That is what keeps the same
markup usable from plain Blade, a Livewire component, or a Volt page.

```blade
<div x-data="{ confirmOpen: false }">
    <x-ds::button @click="confirmOpen = true">Delete</x-ds::button>

    <x-ds::modal open="confirmOpen" title="Delete report?" size="sm">
        This cannot be undone.
        <x-slot:footer>
            <x-ds::button variant="ghost" @click="confirmOpen = false">Cancel</x-ds::button>
            <x-ds::button variant="danger">Delete</x-ds::button>
        </x-slot:footer>
    </x-ds::modal>
</div>
```

`dropdown` and `tooltip` are the exceptions: leave `open` off and they scope
their own state, so a menu on every row of a table needs no wrapper.

## Driving components from client-side state

Two rules that are not obvious from the prop tables, and that both fail
**silently** — HTTP 200, nothing in the log, a component that looks right until
you interact with it. If you are using this package with plain Alpine rather
than Livewire, read both. Livewire re-renders on the server and sidesteps them
entirely.

### `open` is a reference, not a condition

`modal`, `drawer` and `sheet` do not just *read* `open`. They **write** to it —
the close button, the backdrop and Escape all set it to `false`. So it has to be
something JavaScript can assign to.

```blade
{{-- Broken: opens, and then nothing closes it --}}
<div x-data="{ side: null }">
    <x-ds::button @click="side = 'right'">Open</x-ds::button>
    <x-ds::drawer open="side === 'right'" title="Filters">…</x-ds::drawer>
</div>
```

That compiles to `side === 'right' = false`. Reading the expression is fine, so
the drawer **opens correctly**, and then the ✕, the backdrop and Escape all do
nothing at all. The only evidence is `Uncaught SyntaxError: Invalid left-hand
side in assignment` in the browser console.

```blade
{{-- Works: one property per panel --}}
<div x-data="{ show: { right: false, left: false } }">
    <x-ds::button @click="show.right = true">Open</x-ds::button>
    <x-ds::drawer open="show.right" title="Filters">…</x-ds::drawer>
</div>
```

Since v1.1 the three components **refuse** a non-assignable `open` with an
exception at render time, naming the component and the expression, rather than
letting it through to the console.

### `tone`, `variant` and `size` resolve to classes on the server

Every prop that selects a look is a lookup of literal class strings in PHP, done
once when the view renders. That is not an implementation detail to work around
— it is what lets Tailwind's scanner see the classes at all, and it is why the
set of values is closed. See [the trap it prevents](../CLAUDE.md).

The consequence is the part worth knowing: **binding one of those props to
Alpine state does nothing.**

```blade
{{-- Renders every toast neutral, whatever item.tone says --}}
<template x-for="item in toasts">
    <x-ds::toast ::tone="item.tone" title="Saved" />
</template>
```

`::tone` sets a `tone` *attribute* on the rendered element. Nothing reads it —
the classes were chosen before the browser ever saw the markup. Affected props:

| Component | Props |
|---|---|
| `toast` | `tone` |
| `badge` | `tone`, `variant` |
| `alert` | `tone` |
| `sheet-item` | `tone` |
| `button` | `variant`, `size` |
| `tab` | `active` |
| `modal`, `drawer` | `size` |
| `progress` | `value`, `tone`, `size`, `variant` — bind `--ds-progress` instead, see [progress](components/progress.md#driving-it-live) |
| `usage-meter` | all of them; re-render it from the server |
| `step` | `status` |

Two ways out.

**Re-render on the server** when the state lives there anyway — a Livewire
component, or a fresh page. Nothing to do; it already works.

**Bind the classes yourself**, with Alpine's **object** syntax:

```blade
<x-ds::tab
    controls="p-overview"
    :active="true"
    ::class="{
        'border-fg text-fg': tab === 'overview',
        'border-transparent text-fg-muted hover:border-border-strong hover:text-fg': tab !== 'overview',
    }"
    ::aria-selected="tab === 'overview'"
    ::tabindex="tab === 'overview' ? 0 : -1"
    @click="tab = 'overview'"
>Overview</x-ds::tab>
```

**The object form is not optional here.** Alpine's *string* form of `:class` only
**adds** classes — it never removes one that was already on the element. The
component server-rendered `border-transparent`, so the element ends up carrying
both and the underline is decided by whichever rule Tailwind emitted last:

```
class="… border-b-2 border-transparent text-fg-muted border-fg text-fg"
```

The object form removes a class whose value is falsy even when the server put it
there, which is the whole difference. Keep the PHP prop as well as the binding —
`:active="true"` above — so the first paint is right before Alpine boots, and
the binding takes over from there. That is the same rule the components follow
internally: bind for what moves, render what does not.

A worked example, arrow keys and all, is in [specs/tabs.md](../specs/tabs.md).

### Where this bites hardest: `toast`

A toast list is *inherently* dynamic, so it is the case where reaching for
`::tone` is most natural and most disappointing. Until a tone-per-item toast is
possible, the workaround is one `<template x-for>` per tone over a filtered
list:

```blade
@foreach (['success', 'danger'] as $tone)
    <template x-for="item in toasts.filter(t => t.tone === '{{ $tone }}')" :key="item.id">
        <x-ds::toast tone="{{ $tone }}" ::title="item.title" x-text="item.body" />
    </template>
@endforeach
```

That is correct, and it has a real cost: toasts group by tone rather than by
arrival order. If arrival order matters more than tone, render the region from
the server instead. See [toast.md](components/toast.md#a-dynamic-list-of-toasts).

### Livewire, if you happen to use it

Nothing changes. The components hold no state, so they work the same inside a
Livewire component as in a plain view — `wire:model` on a field, `wire:click` on
a button, `@entangle` for a modal's open state. Two small courtesies are built
in: `panel` passes `wire:navigate` through to its header link, and `stat-tile`
re-runs its count-up after a Livewire page swap. For pagination inside a
component there are Livewire variants of the two views; see
[pagination](components/pagination.md#with-livewire).

Neither costs anything when Livewire is not installed.

## Pagination

Point Laravel's paginator at the two views, once, in a service provider:

```php
use Illuminate\Pagination\Paginator;

public function boot(): void
{
    Paginator::defaultView('ds::pagination');
    Paginator::defaultSimpleView('ds::simple-pagination');
}
```

Inside a Livewire component that uses `WithPagination`, return
`ds::pagination-livewire` from its `paginationView()` instead: Livewire replaces
the default view while it renders, and the plain view's links would reload the
page. See [pagination](components/pagination.md#with-livewire).

## Passing your own classes

Every component merges the attributes you give it, so layout stays yours:

```blade
<x-ds::button class="w-full">Save</x-ds::button>
<x-ds::panel class="mt-6">…</x-ds::panel>
```

Use this for **layout** — width, margin, grid placement. Reach for a prop for
anything about what the component *is*: `tone="danger"`, not
`class="bg-red-500"`. A class that changes meaning is an escape hatch that
becomes the norm.

## Upgrading

Tags are what `^1.0` resolves against:

```bash
composer update shipbytes/blade-ui
```

### From 1.x to 2.0

- **`ds::date-picker` needs the package's script.** Put
  [`<x-ds::scripts />`](#3-add-the-script) in the layout, above Alpine. Its
  calendar is now the one `ds::calendar` draws, rendered by the server, so it
  is right before any script runs (the day marked today is the server's, not
  the browser's). Without the script it is the browser's own date field, or
  two for a range, and posts the same names: `{name}`, or `{name}_start` and
  `{name}_end`, as `Y-m-d`.
- **Its keys follow the ARIA date grid:** Home and End go to the start and
  end of the **week** (they went to the ends of the month), and
  Shift+PageUp / Shift+PageDown move a **year**.
- **A second pick before a range's start swaps the ends**, where 1.x started
  the range again from there.
- `wire:model` and `x-model` on the date picker now bind the value (`Y-m-d`,
  or `[from, to]`).

## Troubleshooting

| What you see | Almost certainly |
|---|---|
| Everything renders unstyled | The `@source` line is missing. See above. |
| An overlay flashes on page load | `theme.css` is not imported, or is imported after your own rules override it. The `[x-cloak]` rule ships in it. |
| A dialog opens but the ✕, the backdrop and Escape all do nothing | `open` was given a comparison rather than a reference. See [Driving components from client-side state](#driving-components-from-client-side-state). |
| A `tone` or `variant` never changes when your Alpine state does | Expected — those resolve to classes on the server. Same section. |
| An empty state renders without its button | The button went in the default slot. It belongs in `<x-slot:action>`. |
| A modal or dropdown does nothing | Alpine is not loaded, or there is no `x-data` scope above it. |
| The console says `dsSomething is not registered` | `<x-ds::scripts />` is missing from the layout, or sits below Alpine's script. See [Add the script](#3-add-the-script). |
| The console says `dsSomething is not defined` | The same, reached from your own `x-data`. |
| An icon renders enormous | You passed a `size` outside the generated range — see [icon.md](components/icon.md). |
| Dark mode does nothing | The `dark` class is on `<body>` rather than `<html>`, or `theme.css` is imported before `tailwindcss`. |
| `rounded-lg` changed size in your app | Not us — this system deliberately never redefines a Tailwind default. Check your own theme. |
