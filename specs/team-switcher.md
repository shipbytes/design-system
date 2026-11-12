# Team switcher

Switch workspace, team or account. A ds::popover holding a listbox of teams,
behind `dsTeamSwitcher`, over a no-script select or list of links that is the
real form.

## Anatomy

```
div[data-ds-team-switcher][x-data=dsTeamSwitcher]
├─ form[method=POST]                       (with `action`)
│  ├─ _token, _method (PUT|PATCH|DELETE)
│  ├─ div[data-ds-team-fallback]           hidden once dsReady
│  │  └─ ds::input as=select [data-ds-team-select] + Switch submit
│  │     or nav > ul > li > a[aria-current]  (every team has href)
│  └─ div[hidden until dsReady]
│     └─ ds::popover (label, bottom-start, width md)
│        ├─ trigger: button[aria-haspopup=dialog][data-ds-team-trigger]
│        │  ├─ avatar sm square [data-ds-team-avatar]
│        │  ├─ name [data-ds-team-name], meta [data-ds-team-meta]
│        │  └─ chevron-up-down
│        └─ panel (-m-4: the list runs to the surface's edges)
│           ├─ search field [data-ds-team-search]   (> searchFrom teams)
│           ├─ ul[role=listbox] > li[role=none] > div|a[role=option]
│           │    avatar, name, meta, check (group-aria-selected)
│           ├─ div[role=status] > p[data-ds-team-empty][hidden]
│           └─ footer slot
```

- **The select carries the value in both modes.** With the script it is hidden
  but still in the form, so the form posts the chosen id under `name`, and
  `wire:model` / `x-model` from the tag land on it.
- **Ids** are hashed from the teams, `name` and `label`, never from `current`,
  so a Livewire morph after a switch patches the list rather than replacing it.

## States

| State | Treatment |
|---|---|
| no script | The fallback: select + Switch (with `action`), select alone, or links |
| closed | Trigger showing the current team (from PHP) |
| open | The popover's; focus on the field, or on the current option |
| filtering | Options not matching are `hidden`; the rest ordered by CSS `order`, best first; the empty row when none |
| chosen | `aria-selected` and the trigger's face move to it; the panel closes |

## Behaviour (`dsTeamSwitcher`)

- Ranking: shared/rank.js (`fold`, `rank`, also the command palette's). Score
  `max(rank(name) × 2, rank(meta) × 2 − 1)`; ties keep the server's order. The
  DOM is never reordered, so a morph sees what it rendered.
- Choosing a link closes the panel and lets the link navigate. Otherwise:
  `select.value = id`, then a `change` event on the select, bubbling, with
  `detail = { id }`. It is a plain `Event` carrying `detail`, not a
  `CustomEvent`: Alpine's `x-model` (and `wire:model`) takes a CustomEvent's
  detail as the value. With `action`, then `form.requestSubmit()`.
- The search field's own `change` is stopped, so a host listening on the
  switcher hears only teams chosen.
- Listeners sit on the root and query the DOM on each event. The scope exposes
  `dsReady`.

## Accessibility

| | |
|---|---|
| Trigger | `button`, `aria-haspopup="dialog"`, `aria-expanded` and `aria-controls` (the popover's) |
| Name | The current team's name and meta, as text (visually hidden when compact) |
| Panel | `role=dialog` named by `label` (the popover's) |
| List | `role=listbox` named by `label`; `role=option`, `aria-selected` on the current; `tabindex=-1`, focus moved by the script |
| Field | `type=search`, `aria-label` = placeholder, `aria-controls` the list |
| Empty | Announced through `role=status` |
| Keyboard | Field: Down into the list, Enter chooses the first match. List: Up/Down wrap (Up from the first to the field), Home, End, Enter/Space choose. Escape closes (popover). Tab to the footer and out |
| Focus | Back on the trigger after a choice or Escape |
| No script | A labelled native select and a submit button, or a `nav` of links with `aria-current="page"` |

## Do not

- **Do not reorder the DOM to rank.** CSS `order` only: Livewire morphs by
  position and id.
- **Do not dispatch a CustomEvent for `change`.** It breaks `x-model`.
- **Do not mix links and values.** Every team has `href`, or none is a link.
- **Do not add creation flows to the panel.** The footer links out.
