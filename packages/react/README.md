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

## Port order

Driven by the ERP milestones in `/var/www/jalaqua-erp/docs/BUILD_PLAN.md` §4.10.
M0 (this batch): button, input, panel, alert, icon, skeleton, badge.
