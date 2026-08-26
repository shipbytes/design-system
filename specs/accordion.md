# Accordion

Sections that expand one at a time. Covers `accordion` and `accordion-item`.

## The one piece of state in the system

Every other component takes its open state from the host. The accordion owns it,
and that is deliberate: **exclusivity is a relationship *between* the items**, so
no single item can enforce it, and pushing it out to the host means every
consumer writing the same toggle by hand.

What it owns is one key — which section is open. That is not application state.

`multiple` turns the key into an array; there is no separate flag, because
whether `open` is an array already carries it and one source of truth cannot
disagree with itself.

## Anatomy

```
┌─────────────────────────────────┐
│ Billing                      ⌄  │  ← <h3><button aria-expanded>
│   Your card ending 4242 …       │  ← role="region", labelled by the button
├─────────────────────────────────┤
│ Security                     ⌄  │
└─────────────────────────────────┘
```

The trigger sits **inside a heading**, so a screen reader can jump between
sections. Which level is right depends on the page and only the page knows, so
`as` takes it — defaulting to `h3` because an accordion is almost always inside a
section that already has one.

## Why not `<details>`

`<details>`/`<summary>` is genuinely good now, and it was the other candidate. It
gives keyboard operation, the expanded announcement and **find-in-page opening a
collapsed section** for free, and `name` makes an exclusive accordion natively.

This one is the ARIA disclosure pattern instead, for control over the animation
and for identical behaviour in every browser. The cost is real and worth naming:
**it needs Alpine, and find-in-page will not reveal a collapsed section.** If
neither matters to a future consumer, `<details>` is the better answer.

## The panel animates with a grid row

`grid-rows-[0fr]` → `grid-rows-[1fr]`, which is animatable in pure CSS, so there
is no `@alpinejs/collapse` dependency for a height transition.

The accessibility half is `invisible`, not `overflow-hidden`. **`visibility:
hidden` takes the content out of the tab order AND out of the accessibility
tree**; `overflow: hidden` takes it out of neither. A collapsed section whose
links are still tabbable is the classic broken accordion, and it looks perfect.

## It renders its state before Alpine boots

`accordion-item` reads the parent's `open` prop with Blade's `@aware`, so the
section that should be open is rendered open. Without that, every panel renders
collapsed and the right one springs open once the JS runs — a visible jump, and
the wrong markup entirely for anything that never runs it.

Same rule as [select.md](select.md): bind for what moves, render what does not.

## Accessibility

- Trigger: `<button>` inside a heading, with `aria-expanded` and `aria-controls`.
- Panel: `role="region"` labelled by its trigger, so it is a landmark the reader
  can return to.
- Collapsed panels are `visibility: hidden` — not focusable, not announced.
- `name` gives each item a stable key. Derived from the title when omitted; pass
  one explicitly when two sections share a title, or when the title comes from
  data that changes.

## Do not

- **Do not put an accordion inside an accordion.** Two levels of disclosure and the
  reader loses track of what is hidden.
- **Do not hide required form fields in a collapsed section.** The browser cannot
  focus a control inside a `visibility: hidden` panel, so submit fails with no
  visible reason — the same trap as [tabs.md](tabs.md).
- **Do not use it to hide primary content.** An accordion is for reference material
  the reader consults, not for the thing the page is about.
- **Do not use it where the reader needs to compare sections.** Exclusive means
  they can only ever see one.
