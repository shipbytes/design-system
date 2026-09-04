# Shipbytes Design System

A portable design system for Blade, React, Vue or plain HTML projects.

**41 Blade components**, a spec and a documentation page for each, design tokens
with light and dark values, and three test suites — including one that drives
every interactive component in a real browser.

It was extracted from a production dashboard rather than designed in the
abstract, which is why the specs argue from measured usage — "zinc-500 was the
most-used class, 850 times" — instead of taste. Those numbers are the evidence
for each rule; the app they came from is not otherwise relevant.

## Which package do I need?

The system ships as separate packages on separate registries. Pick by what you
are building in, not by name:

| You are building | Install | Where |
|---|---|---|
| **Laravel + Blade** (with or without Livewire) | `shipbytes/blade-ui` | Composer |
| **React** | `@shipbytes/react` | `packages/react/`, branch `react` (not on npm yet) |
| **Vue** | `@shipbytes/vue` | npm |
| **Anything else** — plain HTML, Svelte, a chart library | `@shipbytes/design-tokens` | npm |

**A Blade project needs nothing from npm.** The Composer package ships the
compiled CSS; the install is one `composer require` and three lines of CSS. Add
`@shipbytes/design-tokens` only if your own JavaScript needs the token *values*
— a chart palette reading `tokens.js` rather than repeating hex literals.

**Laravel with Inertia + React?** Take `@shipbytes/react` and skip `blade-ui` —
41 Blade components you cannot render are not useful. If you have both, Blade
marketing pages and a React app behind login, take both: they share
`dist/tokens.css`, so the two buttons are the same button. That case is the
reason this is token-first rather than component-first.

> `@shipbytes/react` **is built** — it lives at
> [`packages/react/`](packages/react) on branch `react`, ported from the same
> specs against the same tokens. It is not on npm yet, so consume it with a
> `file:` link or a git dependency, and see its own README for what is ported.
> `@shipbytes/vue` is not built. Everything below is the Blade package.

## Start here

| | |
|---|---|
| **[docs/getting-started.md](docs/getting-started.md)** | Install, three lines of CSS, dark mode, Alpine |
| **[docs/](docs/)** | A page per component: screenshot, props, examples |
| **[specs/](specs/)** | Why each component works the way it does |
| `dist/gallery.html` | Every component on one page, both themes |

## Why this is token-first

The obvious approach — share components — does not survive contact with a real
app. The source dashboard's markup was deeply bound to Livewire: roughly 1,100
directives, and the worst of it structural rather than cosmetic (`$wire.$watch`
driving autosave, a `morph.updated` hook recalculating layout, 33 `wire:ignore`
islands, and `wire:loading` as the *only* representation of loading state —
there was no `isLoading` variable to port).

Any behaviour layer has to be rewritten per framework regardless. What genuinely
travels is:

| Layer | Portability |
|---|---|
| **Tokens** | Perfect. One CSS file every framework consumes. |
| **Recipes** — the class strings per variant/size/state | Near-perfect. |
| **Specs** — anatomy, variants, states, a11y contract | As documentation. |

Implementations are thin leaves on top of that, not the shared thing.

## Layout

One repository ships two packages: the composer package
`shipbytes/blade-ui` and the npm package `@shipbytes/design-tokens`.

```
composer.json     shipbytes/blade-ui  — lives at the ROOT, because that is
package.json      @shipbytes/design-tokens   where Composer looks for it

src/              the service provider
config/           blade-ui.php (icon aliases, default icon size)
resources/views/  the Blade components, and the pagination views

tokens/           DTCG source — the only files you edit by hand
dist/             generated; do not edit
specs/            component contracts, written alongside each implementation
icons/            which Heroicons the system uses, and their v2 names
scripts/          build and test
```

## Install in a new Laravel project

Every step below was run against a fresh `laravel/laravel` (Laravel 12, which
already ships Tailwind v4) and verified in a browser, light and dark.

**You do not need npm for a Blade project.** The composer package ships the
compiled CSS. The npm package exists for React, Vue and plain-JS consumers.

### 1. Install

```bash
composer require shipbytes/blade-ui
```

The service provider is auto-discovered. `blade-ui-kit/blade-heroicons` comes
along as a dependency — nothing else to install.

> **Verified end to end.** `composer require` from GitHub into a `laravel new`
> app, three lines of CSS, built with the app's own Vite: 41 components render
> styled in both themes, with no JavaScript errors and nothing else configured.

### 2. Three lines in `resources/css/app.css`

```css
@import 'tailwindcss';

@import '../../vendor/shipbytes/blade-ui/dist/tokens.css';
@import '../../vendor/shipbytes/blade-ui/dist/theme.css';
@source '../../vendor/shipbytes/blade-ui/resources/views';
```

**The `@source` line is not optional.** Tailwind only scans `resources/`, so
without it every class the components emit is discarded as unused and they
render completely unstyled — with no error, because "unused" is not a failure.
This is the single most likely thing to go wrong.

`theme.css` brings its own `@custom-variant dark`, so `dark:` is bound to a
class rather than the OS preference and a theme toggle can override the system
setting. Nothing to add for that.

### 3. Use the components

```blade
<x-ds::button>Save</x-ds::button>
<x-ds::button variant="secondary">Cancel</x-ds::button>
<x-ds::badge tone="success">Active</x-ds::badge>

<x-ds::panel title="Recent activity">
    <x-ds::panel-row>Signed in</x-ds::panel-row>
</x-ds::panel>

<x-ds::input name="email" label="Email address" />
```

Dark mode is a `dark` class on `<html>`. Nothing else changes.

### Documentation

**[docs/](https://github.com/shipbytes/design-system/tree/master/docs)** — a page per component: a screenshot in both themes, the full
prop table, examples, and the mistakes worth avoiding. Start at
[docs/getting-started.md](docs/getting-started.md).

`dist/gallery.html` shows every component at once, rendered from the real
source, in both themes.

### What you get

Forty-one components, plus a pagination view.

**Layout & content** — `panel` `panel-row` `table` `table-cell` `table-row`
`stat-tile` `empty-state` `skeleton` `accordion` `accordion-item`
**Forms** — `input` `select` `combobox` `checkbox` `radio` `radio-group`
`switch` `date-picker` `file-upload` `button`
**Navigation** — `nav-item` `bottom-nav` `bottom-nav-item` `tabs` `tab`
`tab-panel` `breadcrumb` `breadcrumb-item`
**Overlays** — `modal` `drawer` `sheet` `sheet-item` `dropdown` `dropdown-item`
`tooltip` `toast` `toast-region`
**Marks** — `icon` `avatar` `badge` `alert`

Wire pagination up in a service provider:

```php
Paginator::defaultView('ds::pagination');
Paginator::defaultSimpleView('ds::simple-pagination');
```

**No Livewire required** — the package depends on `illuminate/support`,
`illuminate/view` and `blade-heroicons`, and nothing else. It works the same in a
plain Blade view or inside a Livewire component.

Ten need [Alpine](https://alpinejs.dev) — `modal`, `drawer`, `sheet`, `dropdown`,
`select`, `combobox`, `date-picker`, `accordion`, `file-upload` and `tooltip` —
plus one optional prop each on `checkbox`, `nav-item` and `toast`. Everything else is plain Blade and CSS.

They hold no state of their own — the host declares it and the component reads
it, which is what keeps the same markup usable from plain Blade, Livewire or
Volt:

```blade
<div x-data="{ confirmOpen: false }">
    <x-ds::button @click="confirmOpen = true">Delete</x-ds::button>
    <x-ds::modal open="confirmOpen" title="Delete report?" size="sm">…</x-ds::modal>
</div>
```

`dropdown` and `tooltip` scope their own state, so a menu or a tip per table row
needs no wrapper. `select` posts through a hidden input, so it submits in a plain
form exactly like a `<select>` — and it renders its selected state from PHP, so
there is no flash of the wrong option before Alpine boots.

A textarea and a native select are `input`'s `as="textarea"` and `as="select"`.
The native select is still the better choice on anything phone-first: it gets
typeahead and the platform's own scrolling for free. `x-ds::select` exists for
where the unstyleable native popup is the problem.

**Not built, on purpose:** server-side filtering for the combobox, an upload
transport for the file field, and a combined date-and-time picker. The reasoning
for each is in CLAUDE.md under Known gaps.

## Utilities the theme adds

Named after the job, not the colour:

```html
<div class="bg-surface text-fg border-border rounded-control shadow-raised">
  <p class="text-fg-muted text-body">Labels and meta</p>
  <button class="bg-surface-inverse text-on-inverse h-control-md rounded-control">Save</button>
  <span class="bg-success-tint text-on-success-tint rounded-full">Verified</span>
</div>
```

Radius and shadow are deliberately `control`/`panel`/`chip` and
`raised`/`float`/`overlay` rather than `sm`/`md`/`lg`. Reusing Tailwind's own
keys would not add names — it would **overwrite** them, silently resizing every
`rounded-*` already in the host app.

## Other consumers

### Without Tailwind

Import `dist/tokens.css` and use the custom properties directly. Everything is
prefixed `--ds-` so it cannot collide with a host application's variables:

```css
.card {
  background: var(--ds-surface);
  color: var(--ds-fg);
  border: 1px solid var(--ds-border);
  border-radius: var(--ds-radius-control);
  box-shadow: var(--ds-shadow-raised);
}
```

### In JavaScript

> **Not published yet.** `@shipbytes/design-tokens` is not on the npm registry,
> so this import does not resolve today. Until it is, consume `dist/tokens.js`
> from a git dependency or a checkout. Blade projects are unaffected — the
> composer package ships `dist/`.

```js
import { tokens } from '@shipbytes/design-tokens';
tokens.semantic.accent;  // { light: 'oklch(54.6% …)', dark: 'oklch(70.7% …)' }
```

Chart palettes should read from here rather than repeating literals — that is
exactly how six separate admin chart palettes drifted apart in the first place.

## Working on the design system itself

```bash
npm install          # dev dependencies (Tailwind CLI, heroicons, Playwright)
composer install     # dev dependencies (testbench)
npm run build        # tokens/ -> dist/
npm test             # tokens, then render, then behaviour
npm run build:docs   # regenerate docs/images/
```

**No host application needed.** `scripts/render.php` boots Laravel through
testbench, so the gallery, the documentation screenshots and the behaviour tests
all render from a clean clone.

`npm test` is three suites:

**Tokens** — structure, WCAG contrast in both themes, theme collisions.

**Render** — boots Laravel through testbench, renders every component, and
asserts that **every class a component emits has a rule** in CSS compiled the way
a consumer compiles it: from the Blade source, not from the rendered output. A
class composed at runtime never appears in the source, so it gets no rule, and
the element renders unstyled with nothing reporting it.

**Behaviour** — drives every interactive component in a real browser with real
Alpine: focus traps, arrow keys, and what the form actually posts. It skips with
a warning if there is no Chromium (`npx playwright install chromium`), so a clean
clone still goes green — read the warning.

`tokens/` is the only thing edited by hand; `dist/` is generated.

## Dark mode

Driven by a `dark` class on the root element, not the OS preference, so a theme
toggle can override the system setting. `prefers-color-scheme` still applies
when nothing has been chosen explicitly; `class="light"` opts a subtree back out.

Dark values live beside their light counterpart in the same token, under
`$extensions["shipbytes.dark"]`, so the two cannot drift apart. `npm test`
fails if any semantic token defines only one theme.

## Contrast

`npm test` checks every foreground/background pair the system actually pairs,
in both themes, against WCAG AA. Three pairs are deliberate, documented
advisories rather than failures — see the notes in `scripts/test-tokens.mjs`
and the rules in [specs/color.md](specs/color.md). The important one: the
Catalyst input outline sits at ~1.1:1 and leans on its fill, shadow and a
passing focus ring for affordance. That is a decision, not an oversight.

## Icons

The dashboard inlines 694 raw `<svg>` blocks with no icon package. They resolve
to 105 distinct Heroicons — but to a **mix of v1 and v2**, and v2 renamed 33 of
the names the app uses. `icons/icons.json` carries the resolved set with a
`v2Name` for every entry, derived by normalising path geometry rather than
matching strings (`npm run build:icons`). Standardise on v2; 12 icons keep their
name but were redrawn, which is accepted.

## Relationship to shipbytes/laravel-ui-kit

Deliberately none, for now. The kit ships auth pages and an admin shell for
Laravel and is already on Tailwind v4 with its own indigo palette. It can adopt
`dist/theme.css` later as a drop-in; until then the two will drift, which was an
accepted trade-off.
