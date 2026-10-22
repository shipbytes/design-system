# Pagination

A **view**, not a component. Laravel renders pagination through
`$paginator->links()`, which resolves a view name and passes `$paginator` and
`$elements` in — so the seam the framework offers is
`Paginator::defaultView('ds::pagination')`, and one call restyles every
paginated screen at once.

## Why it exists

The framework's stock Tailwind view is built on `gray` and a `blue-300` focus
border — a second neutral ramp and an off-system accent — and ships its own
hand-written `dark:` classes that assume a palette this system does not use. In
the source app it was rendering on 19 screens and nobody had noticed, because
local seed data never fills a second page.

That is also why it is covered by **specimens** that build a real paginator
(`pagination/links`, `pagination/livewire` in `tests/Specimens.php`): no page in
development renders pagination at all, so nothing else would.

## Anatomy

```
sm and up
┌──────────────────────────────────────────────────────────────┐
│ Showing 46 to 60 of 237 results        ‹  1 2 3 [4] 5 … 16 › │
└──────────────────────────────────────────────────────────────┘

below sm
┌──────────────────────────────────────────────────────────────┐
│ « Previous              4 / 16              Next »           │
└──────────────────────────────────────────────────────────────┘
```

## The current page

**A raised card, never a colour** — `border-strong` + `surface` +
`shadow-raised`, the same treatment as the active nav item.

"You are here" means the same thing in both places, so it should look the same
in both places. It also survives dark mode as a change of elevation, where a
fill would have to be redefined against a dark ground.

## Details that are load-bearing

- **Page cells are square** (`size-control-md`) and `tabular-nums`. Sized to
  content, a three-digit page is wider than a one-digit page and the whole row
  jitters as the reader walks through it.
- **Disabled arrows stay visible**, faded rather than removed. A row that drops
  its first control on page 1 shifts every other control left, so the "next"
  arrow moves the moment you use it.
- **The mobile row carries the position.** The stock view is previous/next
  only, which leaves a phone with no way to tell page 2 from page 20. It is the
  one place this view adds information rather than restyling it.
- **Every numbered link keeps `aria-label="Go to page N"`** and the ellipsis is
  `aria-hidden`. Without them a screen reader gets a list of bare digits.

## Simple pagination

`simple-pagination` is previous/next only and shows **no position**. A simple
paginator never runs a COUNT query — that is the entire point of it — so it
knows neither the total nor the last page, and there is nothing truthful to
show.

## Livewire mode

Livewire's `WithPagination` swaps `Paginator::$defaultView` for its own view
while a component renders, and a plain link would reload the page and drop the
component's state. So each view takes an optional `$livewire` (default `false`)
and two thin views, `ds::pagination-livewire` and
`ds::simple-pagination-livewire`, `@include` the base with it set. A component
opts in through `paginationView()` / `paginationSimpleView()`.

In that mode every control that was an `a` becomes a `button type="button"`
with `wire:click` calling `previousPage('<pageName>')`, `nextPage(...)` or
`gotoPage(N, '<pageName>')`, `wire:loading.attr="disabled"`, and a `wire:key`
per control. Same classes, same disabled spans, same ARIA, so the two modes
look identical. It also honours Livewire's `scrollTo` view data (default
`body`, `false` to disable), because a host moving from Livewire's own views
would otherwise lose the scroll-to-top without noticing.

This is the one place the package emits `wire:` directives. It is opt-in and
the attributes are inert without Livewire, so the package still depends on
nothing but Blade.

## Do not

- Colour the current page. It collides with the accent, and the accent is
  already spoken for by links.
- Hide the control row when there is one page. The view renders nothing at all
  in that case; wrapping the call in `@if ($x->hasPages())` at the call site is
  redundant.
