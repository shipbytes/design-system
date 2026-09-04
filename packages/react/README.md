# @shipbytes/react

The React port of the Shipbytes design system.

## What this package is

The same components as `resources/views/components/*.blade.php`, built from the
same [specs](../../specs) and the same [tokens](../../tokens). The class recipes
are copied **verbatim** into `cva` recipes — including the odd-looking
`px-[calc(--spacing(3.5)-1px)]` arithmetic, which is what makes a filled button
and an outlined one land on the same pixel.

What is not copied is behaviour. Alpine's `x-show` / `x-cloak` and Livewire's
`wire:model` have no React equivalent and are rebuilt as explicit state.

## How it is consumed

It exports TypeScript **source**, not a build:

```json
"exports": { ".": "./src/index.ts" }
```

The consumer's bundler compiles it. That keeps the edit loop to one step while
the port is in progress. The consuming app must also tell Tailwind to scan this
source, or none of the component classes are generated:

```css
@source "../../../shipbytes-design-system/packages/react/src";
```

If linking ever misbehaves, the fallback is a `tsup` build to `dist/` with
`exports` and the `@source` line pointed there.

## Adding a component

1. Read `specs/<name>.md` first. It is the contract, and it records mistakes
   already made once.
2. Copy the class recipe out of `resources/views/components/<name>.blade.php`
   into a `cva` recipe. Do not retype it from the rendered output.
3. Behaviour with Radix where a primitive exists; `useId()` for ids.
4. A test per component covering the spec's accessibility section.

## Icons: the registry

`Icon` takes a kebab-case `name` and looks it up at runtime, which used to mean
importing all four `@heroicons/react` namespaces so that any name could resolve.
A namespace import is a use of every export, so nothing tree-shook and an
application drawing nine icons shipped about a megabyte of them.

So the application declares what it uses, and only that reaches the bundle:

```tsx
import { ArrowRightIcon, TrashIcon } from '@heroicons/react/24/outline'
import { createIconRegistry, IconProvider } from '@shipbytes/react'

const icons = createIconRegistry({ outline: { ArrowRightIcon, TrashIcon } })

<IconProvider registry={icons}>…</IconProvider>
```

Object shorthand keys the registry by the heroicons export name, so each icon is
written twice and never spelled a third way.

The handful of icons **this package's own components** draw — an alert's tone
icon, an input's error mark, a select's chevron, a dismiss cross — are named
imports in `src/icon/builtin.ts` and always resolve. They were never the
application's to declare. The injected registry is checked first, so an
application can still override one.

A tool or a playground that genuinely wants everything can pass a namespace and
get the old behaviour back, at the old size — a deliberate choice rather than a
default.

## Port order

Driven by the ERP milestones in `/var/www/jalaqua-erp/docs/BUILD_PLAN.md` §4.10.

| Batch | Components |
|---|---|
| M0 | button, input, panel, alert, icon, skeleton, badge |
| M1 | tabs, dropdown (+ items), modal, toast + toast-region; the icon registry above |

### Notes from the M1 batch

- **Radix keeps promises the Blade version has to delegate.** `specs/tabs.md`
  hands the host twenty lines of Alpine for the arrow keys and warns that two
  earlier versions of that example were wrong; `specs/dropdown.md` sets the
  trigger's `aria-expanded` by querying whatever the slot turned out to contain.
  Radix Tabs and DropdownMenu own both, so the contract is kept by the component
  rather than by every consumer.
- **Overlay motion is animations, not transitions** — and that is why it took a
  token change. Alpine's `x-transition` keeps the element in the DOM and swaps
  classes over two frames; Radix keeps a closing dialog mounted, waits for
  `animationend`, and only then unmounts it. An element being removed never
  completes a *transition*, so the spec's utility classes would have given a
  working enter and a leave nobody ever sees, with nothing in the console. The
  theme now carries four named animations (`overlay-in/out`, `dialog-in/out`)
  built from `tokens/motion.json`, and Modal drives them off Radix's
  `data-state`.

  The same mechanism has a second consequence: **the panel has to be a direct
  child of `Dialog.Portal`.** The portal wraps each of its children in its own
  Presence, so the `fixed inset-0 flex` wrapper the Blade version centres with
  unmounts the panel before its leave can run. The panel centres itself
  instead — `fixed`, `top-1/2 left-1/2`, translated back by half — and
  `calc(100% - 2rem)` carries over what the wrapper's `p-4` was doing.
- **Dropdown gains collision detection**, which the Blade version explicitly
  does not have. `placement` becomes a preference rather than a commitment.
- **jsdom has no Pointer Events API.** Radix's menus call `hasPointerCapture`,
  and an undefined method there does not throw — the interaction never completes
  and the test times out five seconds later pointing at the `it(...)` line. The
  stubs are in `vitest.setup.ts`.
