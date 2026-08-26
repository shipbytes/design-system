# Breadcrumb

Where this page sits. Covers `breadcrumb` and `breadcrumb-item`.

## Anatomy

```
Home  ›  Reports  ›  Q3 revenue
                     └ current: text, not a link
```

An **ordered list** inside a `<nav>`. Ordered because the order *is* the meaning
— this is a path, not a row of links that happen to be next to each other, and
a screen reader announcing "list, 3 items" with positions is the whole point.

| Part | Token | Why |
|---|---|---|
| Link | `fg-muted` → `fg` on hover | Quiet. The breadcrumb is orientation, not navigation you are meant to look at. |
| Current | `fg` + medium | The one you are on. |
| Separator | `fg-subtle` | Allowed: it repeats structure the list already carries. |
| Type | `text-meta` | 12px. A breadcrumb at body size competes with the page title under it. |

## The separator belongs to the item after it

Every item draws a leading chevron, and the **parent hides the first one** with
`[&>li:first-child>svg]:hidden`.

The alternative is telling each item whether it is first. A component that has to
know its own index cannot be moved, wrapped in an `@if`, or produced by a loop
without the caller doing bookkeeping the markup should be doing itself — and the
first time someone reorders two crumbs, there are two chevrons at the front.

## Current page

`current` renders a `<span>`, never an `<a>`. **A link to the page you are on is
a promise of somewhere to go that goes nowhere**, and it takes a tab stop to do
it. An item with no `href` is treated the same way.

It carries `aria-current="page"`. Weight alone does not carry to a screen reader.

## Accessibility

- `<nav aria-label="Breadcrumb">`. A page usually has several `<nav>` landmarks;
  unlabelled ones are indistinguishable in a landmark list.
- The separators are `aria-hidden`. "Home chevron right Reports chevron right"
  is noise on top of a list that already says it.
- Items truncate rather than wrap. A crumb trail that wraps to three lines has
  stopped being a one-line orientation aid.

## Do not

- **Do not put the current page's actions in it.** It says where you are, not
  what you can do.
- **Do not build it from the URL segments.** `/reports/2` is not "2"; the crumb
  needs the record's name, and the route cannot supply it.
- **Do not collapse the middle with an ellipsis** unless the collapsed items are
  reachable some other way. A hidden crumb is a lost exit.
- **Do not use it as a substitute for a back link** on a phone, where it wraps
  and takes three tab stops to do one job.
