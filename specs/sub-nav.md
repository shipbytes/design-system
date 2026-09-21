# Sub-nav

A nav item that holds the pages belonging to it. The rail that asked for it had
twenty-three entries at one level, four of which were pages *inside* a fifth —
`/gate`, `/gate/handovers`, `/gate/guards`, `/gate/roll-calls` drawn as equals,
and `/gate/persons` filed under a different section entirely. Nothing was wrong
with the styling. There were about eight things with parts inside them and one
level to say it in.

This spec was **named by [nav-item.md](nav-item.md) before it was written.**
Under its *Do not*:

> Add a second active-ish state for "parent of the current page". If a section
> needs to show it contains the current page, that is what the sub-nav is for.

So the contract has referred to this component since it was frozen. Everything
below is built to keep that sentence true.

Covers `sub-nav` and `sub-nav-item`.

## Anatomy

```
  ┌─ the whole row is one card when /gate is current ────┐
  │                                                      │
  ┌──────────────────────────────┬──────┐
  │ [ic]  Gate                   │  ›   │   ← 228×36 link │ 36×36 arrow
  └──────────────────────────────┴──────┘
   └── goes to /gate ────────────┘└ opens the list, never navigates

  open:
  ┌──────────────────────────────┬──────┐
  │ [ic]  Gate                   │  ⌄   │
  └──────────────────────────────┴──────┘
  ┌──────────────────────────────────────┐
  │        Shift handovers               │   ← sub-nav-item, no icon
  │        Guards on gates               │
  │      ┌────────────────────────────┐  │
  │      │ Roll calls                 │  │   ← current: the raised card
  │      └────────────────────────────┘  │
  │        Person in                     │
  └──────────────────────────────────────┘
   ╰─ 40px ─╯
```

Children align to the **parent's label**, not to its icon: `px-2` plus a 20px
icon plus `gap-3` puts the label at 40px, and that is the indent. A child
indented to the icon would start a second column that nothing else in the rail
uses.

| Part | Token | Why |
|---|---|---|
| Parent row | the nav item's own recipe | It *is* a nav item. Resting, hover and current appearance come from one place in each implementation — **not a second copy that drifts**. |
| Arrow | `fg-subtle`, `radius-control`, `surface-subtle` on hover | `fg-subtle` is allowed because the label beside it says the same thing — the redundant-information exception in [color.md](color.md). |
| Child row | `text-section` at normal weight, `fg-subtle` → `fg-body` on hover | The parent is `medium`; the child is the same size at normal. **Weight and indent separate them, never a smaller type size** — 13px is not in the scale, and a rail that invents one drifts from every other surface. |
| Current child | `border-strong` + `surface` + `shadow-raised` | The same card the active nav item and the current pagination cell draw. See below. |
| Pop-out panel | `surface` + `border` + `shadow-float`, `min-w-52` | It floats over the page and does not block it, so `float` and not `overlay` — the same reasoning as [dropdown.md](dropdown.md). |

## One row, two targets

**The label and icon navigate; the arrow, and only the arrow, opens the list.**

Both were considered. One press doing both means the reader cannot reach the
parent's own page without also opening five rows they did not ask for, and
cannot open the list to look without leaving the page they are on. Two targets
cost one extra control and answer both.

The consequence is structural and it is the thing an implementation gets wrong
first: **a link and a button are siblings, never nested.** A `<button>` inside
an `<a>` is invalid, and the browser gives the inner control no reliable
keyboard behaviour. So the row is a container holding two controls side by side,
and the container — not the link — draws the current card, so that the card
wraps both targets as one row.

### The arrow is 44px where a finger has to find it

The arrow's drawn box is 36×36 and its glyph is smaller still. **Where the
pointer is coarse, its pressable box is at least 44×44 and the row grows to
match.** The first consumer's rail runs on a touch screen at a gate, operated by
someone wearing gloves in a hurry; a 36px target beside a 228px one is a
mis-press waiting to happen, and the mis-press opens a list under the thumb
rather than going where they were looking.

**The arrow's hit area never grows leftward into the label, and a gap of at
least 8px between the two belongs to the link.** Every pixel of doubt goes to
the link, because a mis-press there lands on a page the reader asked for and a
mis-press on the arrow changes the shape of the rail under their hand.

The arrow also takes its own hover fill, and hovering it does **not** light the
whole row. Two targets that highlight as one are one target as far as the reader
can tell.

## Three states, and there is no fourth

```
closed                    parent is current         open, current child
┌────────────────────┐    ┏━━━━━━━━━━━━━━━━━━━━┓    ┌────────────────────┐
│ [ic] Gate       ›  │    ┃ [ic] Gate       ›  ┃    │ [ic] Gate       ⌄  │
└────────────────────┘    ┗━━━━━━━━━━━━━━━━━━━━┛    │      Shift hand…   │
                                                    │    ┏━━━━━━━━━━━━┓  │
                                                    │    ┃ Roll calls ┃  │
                                                    │    ┗━━━━━━━━━━━━┛  │
                                                    └────────────────────┘
```

**There is deliberately no "this section contains the page you are on" state.**
That is the state [nav-item.md](nav-item.md) forbids, and it is forbidden for a
reason that has not changed: a second active-ish treatment has to be
distinguishable from the active one and from the resting one at a glance, in two
themes, and it ends up competing with the thing it is a weaker version of.

**The behaviour carries it instead:** going to a page inside a closed list opens
that list, and the current child's card then says where the reader is. The
parent never has to.

### "You are here" is a raised card, in exactly one place

`border-strong` + `surface` + `shadow-raised` — the active nav item's card, the
current pagination cell's card, and now the current child's. The rail draws
**one** of them at a time, and where it sits is the whole answer to "where am
I": on a top-level row, or indented inside an open list.

This is a deliberate departure from a tint. [nav-item.md](nav-item.md) and
[pagination.md](pagination.md) state the same rule in almost the same words —
*"a raised card, never a colour"*, *"colour the current page … collides with the
accent, and the accent is already spoken for by links"* — and a rail whose
current child is tinted while its current parent is carded teaches two shapes
for one fact. A card also survives dark mode as a change of elevation, where a
fill has to be redefined against a dark ground.

The child's card is smaller and indented, and it carries no border-radius or
padding change of its own: it is the same recipe at child scale.

### A list the reader closed on the page they are standing on

Permitted, and it shows **no current mark anywhere in the rail** — the card is
on a hidden row. That is the price of refusing the fourth state, it is paid only
when the reader closes a list themselves, and reopening it is one press.

## What is open, and who remembers

**Open state is passed in. There is no uncontrolled mode.** Same rule as the
nav item's collapse condition, and for three reasons rather than one:

1. **The component does not know the route.** "Going to a page inside a closed
   list opens it" needs the route, and the component is told only whether each
   child is current — which is a fact about *now*, not about a change.
2. **It has to stay open afterwards.** An open list stays open while the reader
   moves about, including to pages outside it. That is history, not a derivation
   from the current page, and a presentation component that kept it would be
   holding application state.
3. **The memory is per browser, under a key the shell owns.** A component that
   wrote storage itself would pick the key, and two rails on one page would
   collide in it.

So: **opening on navigation, and remembering what is open, belong to the host**
— the same shell that already owns whether the rail is collapsed. What this
component owes in return is that **it never closes itself.** It reports a press
on the arrow and draws what it is told.

**Lists are not exclusive.** Several may be open at once, and opening one does
not close another. Unlike an [accordion](accordion.md), a rail is a map rather
than a panel of reference sections — exclusivity there means the reader cannot
see two neighbouring sections at once, which is the whole thing a map is for.

## The list animates open, and closes out of the tab order

`grid-rows-[0fr]` → `grid-rows-[1fr]`, as in [accordion.md](accordion.md), so
the height transition is pure CSS.

**A closed list is `visibility: hidden`, never `overflow: hidden`.**
`visibility: hidden` takes the children out of the tab order *and* out of the
accessibility tree; `overflow: hidden` takes them out of neither, and a rail
whose hidden rows are still tabbable is the classic broken disclosure — it looks
perfect and walks a keyboard user through five links that are not on screen.

## Counts

A parent row takes a count exactly as a [nav item](nav-item.md) does, and so
does a child. Every rule there holds: zero draws nothing, past the cap it reads
`99+`, and only a queue that reaches nought earns one at all.

**A parent's count is its own page's count and is never a sum of its
children's.** A component cannot know whether the parent's twelve and the
child's twelve are the same twelve, and a number that appears when a list is
shut and vanishes when it opens reads as the count having moved.

The consequence is worth stating rather than discovering: **a count on a child
in a closed list cannot be seen.** If something must be watched from anywhere in
the app, it is a top-level entry, not a child.

## Collapsed to icons: the list becomes a pop-out

When the rail is collapsed there is no label left to press, so the row stops
being a link and **becomes one control**: pressing the icon opens a panel beside
it, and **the parent's own page is the panel's first row** — named in full
("Gate dashboard"), because otherwise it is unreachable.

```
┌────┐
│[ic]│──┐  ┌─────────────────────┐
│  • │  └──│ GATE                │  ← the section's name, as an overline
└────┘     │ Gate dashboard      │  ← the parent's own page, first
           │ Shift handovers     │
           │ ┏━━━━━━━━━━━━━━━━━┓ │
           │ ┃ Roll calls      ┃ │
           │ ┗━━━━━━━━━━━━━━━━━┛ │
           └─────────────────────┘
```

The rows inside are the same `sub-nav-item` the open list draws, at the same
indent-free width; the panel is the open list, moved.

**The one mark that is a deliberate exception.** A collapsed icon whose list
holds the current page carries a small dot at its bottom-right, in
`surface-inverse` — the same ink as the collapsed count pip, and for the same
reason: a mark this size in a light tint disappears, and the accent is spoken
for by links. This is the "contains the current page" signal that the expanded
rail refuses, and it is allowed here for the single reason that the alternative
is impossible — the list is not on screen, so there is nowhere to put the card.
It is drawn **only** when the current page is a child; when the parent's own
page is current, the icon draws the card and the dot would be saying it twice.
Bottom-right, so it never collides with a count pip on the top-right.

A collapsed parent icon shows **no tooltip.** The panel's overline already names
the section, and a tip and a panel opening from the same press land on top of
each other.

### Opening on hover is optional, and off

The panel opens on press — pointer, `Enter` or `Space` — always. A shell may
also ask for hover, with a short delay in and a longer delay out, and it is
**off by default**: a rail that already peeks on hover would fight it, and a
touch screen has no hover at all, so a component that depended on it would have
no way to open on the one device that most needs the pop-out.

### The pop-out is this component's own panel, not a dropdown

[dropdown.md](dropdown.md) says, under *Do not*: *"Use it for navigation between
pages of the app. That is `nav-item`; a menu hides the destinations, and primary
navigation should not be hidden."* A rail pop-out is navigation between pages of
the app, and honouring that sentence is cheaper than amending it:

- `role="menu"` is a promise of arrow-key roving that replaces Tab, and these
  rows are **destinations, not verbs**. A list of links announced as menu items
  is the wrong thing announced.
- A menu item has two tones, `neutral` and `danger`, and no notion of a current
  page. Giving it one means a tone that is not a claim about what the item *is*
  — which is the rule that keeps that set at two.
- The panel wants to open on hover as well as on press, which a menu anchored to
  one trigger does not do.

So the sub-nav draws the panel itself. **Dropdown needs no change, and neither
does `dropdown.md`** — in particular it does **not** need a right-hand placement
added, because the anchoring this panel uses (a portal out of the rail, fixed
positioning, flip and shift near an edge) already accepts one.

What it *does* take from [dropdown.md](dropdown.md), verbatim rather than
redesigned: focus enters the panel's first row on open and **returns to the icon
on close**; `Escape` closes; `Tab` closes and moves on rather than leaving the
panel hanging; an outside press closes it by naming its own nodes, never with a
full-screen backdrop that swallows the next click anywhere on the page.

### Where the panel comes from

The overlay has to leave the rail: a rail is a scroll container, and an
absolutely positioned panel is clipped by it. That is known gap 1, closed in the
React port and open in Blade — so **the expanded nesting is available in every
implementation and the collapsed pop-out is not.** An implementation without a
portalled, collision-aware overlay ships the sub-nav without the pop-out: a
collapsed parent icon is then a plain link to its own page, and the children are
reached from the page it opens. That is a smaller component, not a broken one.

## Accessibility

- The children are a **list** — `<ul>`/`<li>`/`<a>` — nested inside the parent's
  own list item, so assistive technology announces "list, 5 items" and the
  nesting is in the markup rather than in the indent.
- The arrow is a `<button type="button">` carrying `aria-expanded` and
  `aria-controls` naming the child list.
- **The arrow's accessible name names its section** — "Gate pages", not
  "Expand". A rail has one arrow per section, and a column of identical "Expand"
  buttons is a list of controls a screen reader user cannot tell apart.
- The current child carries `aria-current="page"`. Weight, fill and elevation
  carry nothing to a screen reader — the same omission
  [nav-item.md](nav-item.md) and [breadcrumb.md](breadcrumb.md) both record.
- Closed children are `visibility: hidden`: not focusable, not announced.
- Collapsed, the icon control carries `aria-expanded` and `aria-haspopup`, and
  the panel is labelled by the section name its overline draws.
- The label link and the arrow are **both reachable by Tab**, in that order.
  Neither is removed from the tab order in favour of the other: the whole point
  of two targets is that both are reachable, and a keyboard is where that is
  easiest to get wrong.

## Do not

- **Do not nest a sub-nav inside a sub-nav.** Two levels is the whole set. Three
  runs the indent out of a 256px rail, loses the reader's place — the same
  reason [accordion.md](accordion.md) refuses a nested disclosure — and there is
  a better answer already: the middle level becomes a page with its own
  navigation on it, which is what a module dashboard is.
- **Do not give a parent no page.** The label is a link; a parent that goes
  nowhere leaves it a target that looks pressable and is not. A group of rows
  with a title and nothing behind it is the rail's own section heading, which
  already exists and is not this component.
- **Do not give children icons.** The indent carries the relationship. A second
  column of icons at child level competes with the parent's and doubles the
  width the label has to fit in.
- **Do not mark the parent because a child is current.** The forbidden fourth
  state, named at the top of this file.
- **Do not roll a child's count up into the parent.**
- **Do not open the list from the label**, or navigate from the arrow. Each
  target does one thing, and a reader who has learned which is which must not
  find it swapped in another rail.
- **Do not put a sub-nav in an overlay** — a sheet or a drawer. Those are
  short-lived and have room; a disclosure inside one is a second thing to open
  before the reader can reach the thing they opened the overlay for. Draw the
  children flat under a heading there.
- **Do not use it for actions.** Every row goes somewhere. A row that *does*
  something is a [dropdown](dropdown.md) item, and a rail is not where an action
  belongs.
