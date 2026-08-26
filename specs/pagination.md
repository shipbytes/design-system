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

That is also why this is covered by a **test** rather than by the screenshot
harness: the harness can only photograph what a running page renders, and no
page in development renders pagination at all.

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

## Do not

- Colour the current page. It collides with the accent, and the accent is
  already spoken for by links.
- Hide the control row when there is one page. The view renders nothing at all
  in that case; wrapping the call in `@if ($x->hasPages())` at the call site is
  redundant.
