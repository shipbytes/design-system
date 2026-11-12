# Timeline

Events in order, with a rail joining their markers: an order's history, an
audit log, an activity feed. `ds::timeline` is the list, `ds::timeline-item`
an entry, `ds::timeline-group` a run of entries under a sticky heading
("Today"). Plain Blade and CSS: no Alpine, no script.

## Anatomy

```
<ol class="group/timeline flex flex-col" aria-label="Order history" data-dense?>
    <li class="group/item relative flex gap-3 pb-6">                      ← timeline-item
        <span aria-hidden="true" class="absolute left-4 w-px bg-border …"/>  ← its piece of the rail
        <span aria-hidden="true" class="size-8 rounded-full ring-4 …">       ← marker: avatar | icon | dot
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap justify-between">
                <p>title, or a link to `href`</p>
                <time datetime="2026-11-10T09:31:00+00:00">November 10, 2026 9:31 AM</time>
            </div>
            <div>the slot: the body</div>
            <div>the `actions` slot</div>
        </div>
    </li>
    <li class="pb-6">                                                      ← timeline-group
        <h3 class="sticky top-(--ds-timeline-top,0px) bg-(--ds-timeline-bg,…)">Today</h3>
        <ol>items</ol>
    </li>
</ol>
```

| Part | Token | Why |
|---|---|---|
| Rail | `bg-border`, 1px, down the middle of the marker column | Quiet: it joins, it does not compete. |
| Marker column | 32px; 24px `dense` | |
| Icon marker | the tone's tint: `bg-{tone}-tint text-on-{tone}-tint` | The badge's recipe. |
| Dot marker | 10px (8 dense), the tone's solid | For entries with nothing to show. |
| Avatar marker | `ds::avatar`, `sm`, decorative | The title names the person. |
| Cut-out | `ring-4` of `--ds-timeline-bg` (default the surface) | The rail runs behind the markers and stops short of each one. |
| Title | `text-body`, medium, `text-fg` | |
| Time | `text-meta`, `text-fg-muted`, tabular | |
| Body | `text-body`, `text-fg-muted`, breaks long words | A URL wraps instead of widening the column. |
| Group heading | `text-meta`, semibold, `text-fg-muted`, sticky | |

## The rail

Each item draws its own piece of the rail, the full height of its box,
behind its marker: from the marker down on the first item, up to the marker
on the last, none on an item alone. So the rail joins the first marker to
the last and never runs past either, and however long a body is, the item
grows and its piece of the rail with it. No item needs to know its
neighbours; CSS `:first-child`, `:last-child` and `:only-child` decide.

Inside a group the same holds within the group, so the rail breaks at each
heading, as the time it marks does.

## Time

| `time` | Shown | `<time datetime>` |
|---|---|---|
| a `DateTimeInterface` | the locale's medium date and time (`isoFormat('LLL')`), or `timeFormat` | ISO 8601 |
| an ISO 8601 string (`2026-11-10`, `2026-11-10T09:31:00Z`) | the same, date only for a date | ISO 8601 |
| anything else ("Yesterday", "10:42") | as written | `datetime`, when given; otherwise a plain span |

`timeFormat="relative"` shows "3 hours ago" (Carbon, the app's locale) with
the full date as its `title`: a relative time is computed at render and goes
stale on a page left open. A PHP date format string is used as given.

## Dense

`dense` on the timeline: 24px markers, a smaller icon and dot, less space
between entries. The items read it from `data-dense` on the list through a
named group, not through Blade's aware directive, so items rendered by a
Livewire child or a partial are dense too.

## Sticky headings

A group's heading is `position: sticky` at the top of the nearest scrolling
box, so it stays while its entries pass under it. It needs a background to
hide them: the surface by default. On another colour set `--ds-timeline-bg`
on any ancestor (`[--ds-timeline-bg:var(--color-surface-subtle)]`), which
the markers' cut-outs use too; under a sticky page header set
`--ds-timeline-top` to its height.

## Accessibility

| | |
|---|---|
| Structure | an ordered list; a group is an item holding a heading and its own list |
| Name | `label` names the list (`aria-label`) |
| Markers | `aria-hidden`: the icon, the dot and the avatar repeat the title, and the tone is colour |
| Time | `<time datetime>` where the moment is known |
| Keyboard | only what the host puts in: the title link, the actions |
| Heading level | `level` on the group, 2 to 6, default 3, to fit the page's outline |

The title must say what happened, including the person when there is an
avatar and the outcome when there is a tone: "Payment failed", never a red
dot alone.

## Load more, with Livewire

Render the items in a Livewire component's view inside one `ds::timeline`
and append pages: the list stays one list, the rail joins the new items to
the old because the last item is only last until more arrive, and nothing
needs re-initialising. docs/components/timeline.md has the example.

## Do not

- **Do not let the tone carry the meaning.** The title says it.
- **Do not nest timelines.** A group is the one level of structure.
- **Do not reverse the order with CSS.** The order is the list's; sort the data.
- **Do not put a timeline in a horizontal row.** It is a column; a horizontal
  sequence of states is a stepper.
