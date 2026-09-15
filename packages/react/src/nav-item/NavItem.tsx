import type { ElementType, ReactNode } from 'react'
import { cn } from '../lib/cn'

/*
 * specs/nav-item.md.
 *
 * One row of a navigation rail — icon, label, optional count — in two states
 * that are **the same element**. The rail animates its width and the item
 * follows; it does not swap between an expanded component and a collapsed one,
 * because two components would need two sets of every fix and several of them
 * would only ever get one.
 *
 * ## The active state is elevation, never colour
 *
 * `border-strong` + `surface` + `shadow-raised`, against a transparent border
 * and no fill when inactive. Colour would have to compete with the icon and the
 * label for the same signal and survive both themes; a change of ELEVATION
 * reads instantly and means the same thing in dark.
 *
 * Those are TOKEN names, and the utility that paints the first of them is
 * `border-border-strong` — the token registers as `--color-border-strong`, so
 * Tailwind prefixes the property to the token's own name. This file wrote
 * `border-strong`, which generates nothing, from the day it was written: the
 * element kept the bare `border` from its base classes with no colour, and
 * Tailwind v4's default of `currentColor` drew the active ring in `fg-body` —
 * near-black in light and near-white in dark. Every other component in the
 * library (Panel, StatTile, Pagination, Radio, Checkbox, EmptyState,
 * FileUpload, Tabs) already writes the longer spelling.
 *
 * And it carries `aria-current="page"`. The hand-written version the spec was
 * written from did not, so the only signal that an item was current was visual
 * — a screen reader user had no way to tell where they were.
 *
 * ## `collapsed` is passed in
 *
 * The spec is explicit that the collapse CONDITION belongs to the shell: one
 * rail has hover-to-peek and a mode that locks it shut, another has neither. A
 * presentation component that knew which is unusable in the other place.
 */

export interface NavItemProps {
  /** Rendered as this element — a router `Link`, an `a`, a `button`. */
  as?: ElementType
  href?: string
  label: string
  icon?: ReactNode
  /** Arbitrary trailing content, rendered as given. Hidden with the label when collapsed. */
  badge?: ReactNode
  /**
   * A count — "how many are waiting on you" — drawn by the component.
   *
   * Its own prop rather than something handed through `badge`, and the split is
   * worth stating: `badge` is a SLOT and renders whatever it is given, so a
   * count put through it is styled by each shell separately and three sidebars
   * using it end up with three different pills. This one the component draws,
   * which is what makes the two rules below enforceable rather than advisory.
   *
   * It is the same pill `Tab`'s `count` draws — neutral tint, inverting on the
   * active row — because a rail count and a tab count are the same fact in two
   * places, and a reader should not have to learn two shapes for it.
   *
   * **Zero renders nothing at all**, not a `0`. The whole value of a badge is
   * that it reaches zero and goes away; a rail permanently showing `0` against
   * four entries teaches people to stop reading the numbers, after which the one
   * that matters is invisible too. Anything that is not a positive finite number
   * is treated the same way, so a count still loading is an absent pill rather
   * than a `NaN`.
   *
   * **Capped, and rendered `99+`.** Past a hundred "a lot" is the information;
   * the exact figure is one click away on the screen the entry opens. It also
   * has to fit the collapsed pip, where four digits do not.
   */
  count?: number
  /** Where a count stops being a number and becomes `N+`. */
  countCap?: number
  active?: boolean
  collapsed?: boolean
  /**
   * The icon in a tinted well.
   *
   * When chipped the icon keeps ONE colour whatever the state: the chip is
   * already a container, and making it change colour too gives the active item
   * three simultaneous signals (card, chip, icon) for a single fact.
   */
  chipped?: boolean
  onClick?: () => void
  className?: string
}

export function NavItem({
  as: Component = 'a',
  href,
  label,
  icon,
  badge,
  count,
  countCap = 99,
  active = false,
  collapsed = false,
  chipped = false,
  onClick,
  className,
}: NavItemProps) {
  const shown = countLabel(count, countCap)

  return (
    <Component
      href={href}
      onClick={onClick}
      /*
       * The only signal a screen reader gets. Absent — not `false` — when the
       * item is not current, because `aria-current="false"` is a value some
       * assistive technology announces.
       */
      aria-current={active ? 'page' : undefined}
      /*
       * Collapsed, the label is gone from the accessible tree along with the
       * pixels, so the row needs its name back. Expanded it must NOT be here:
       * an `aria-label` on an element whose visible text says the same thing
       * overrides the text, and the two then drift.
       */
      aria-label={collapsed ? label : undefined}
      title={undefined}
      className={cn(
        /*
         * `relative` so a collapsed count can hang off the ITEM's corner.
         *
         * It has to be here and not on the icon: the pip is a SIBLING of the
         * glyph — deliberately, so `aria-hidden` on the glyph does not take the
         * count with it — and `absolute` resolves against the nearest POSITIONED
         * ancestor, not the nearest sibling. With the `relative` on the icon
         * instead, the pip escaped to the viewport and rendered at its top-right
         * corner, hundreds of pixels from the rail. jsdom has no layout, so the
         * component tests could not see it; a browser could, immediately.
         */
        'relative flex items-center rounded-lg border transition-colors',
        // `section` size at `medium` weight: 14/20, not the body 14/24. A rail
        // is a list of short labels, and the looser leading makes every row
        // four pixels taller for nothing.
        'text-section font-medium',
        collapsed ? 'size-8 justify-center p-2' : 'w-full gap-3 px-2 py-2',
        active
          ? 'border-border-strong bg-surface text-fg-body shadow-raised'
          : 'border-transparent text-fg-subtle hover:text-fg-body hover:bg-surface-subtle',
        className,
      )}
    >
      {icon ? (
        <span
          className={cn(
            'flex shrink-0 items-center justify-center',
            chipped
              ? 'size-6 rounded-md bg-surface-subtle text-fg-subtle'
              : active
                ? 'text-fg-body'
                : 'text-fg-subtle',
          )}
          /*
           * `aria-hidden` on the GLYPH only, and this is why the pip is a
           * sibling of it rather than a child: the icon repeats the label and
           * has nothing to say, but a count is the one thing on a collapsed rail
           * that is not recoverable from anywhere else on screen.
           */
          aria-hidden="true"
        >
          {icon}
        </span>
      ) : null}

      {/*
       * Collapsed, the count becomes a pip on the icon's corner rather than
       * disappearing with the label.
       *
       * The spec's rule — a count and its label are hidden by ONE condition — is
       * about a count sitting INLINE where a label used to be, which reads as a
       * number belonging to nothing. A pip anchored to the glyph is the opposite
       * shape: it is visibly attached to the thing it counts, and it is the only
       * reason a shut rail can still say something needs you.
       *
       * Always inverse, in both states. The expanded pill can afford the neutral
       * tint because it sits in a wide row; at this size on a light chip it
       * would vanish, and a badge that cannot be seen is not a badge.
       */}
      {collapsed
        ? shown !== null && (
            <span
              className={cn(
                'pointer-events-none absolute -top-1 -right-1 rounded-full px-1',
                'text-meta font-medium tabular-nums',
                'bg-surface-inverse text-on-inverse',
              )}
            >
              {shown}
            </span>
          )
        : null}

      {/*
       * Both hidden together, and by the same condition. The spec derives the
       * label's visibility from the collapse expression rather than from a
       * second one, so the two can never disagree — a collapsed rail showing a
       * count beside no label is the failure that rule prevents.
       */}
      {collapsed ? null : (
        <>
          <span className="min-w-0 flex-1 truncate text-left">{label}</span>

          {shown !== null && (
            <span
              className={cn(
                // tabular-nums so the rail does not reflow as counts change
                // width. The same recipe `Tab`'s count carries, deliberately.
                'shrink-0 rounded-full px-1.5 py-0.5 text-meta font-medium tabular-nums',
                active ? 'bg-surface-inverse text-on-inverse' : 'bg-neutral-tint text-on-neutral-tint',
              )}
            >
              {shown}
            </span>
          )}

          {badge ? <span className="shrink-0 text-meta text-fg-muted">{badge}</span> : null}
        </>
      )}
    </Component>
  )
}

/**
 * The count as it is drawn, or null for "draw nothing".
 *
 * One function for both states, so the expanded pill and the collapsed pip can
 * never disagree about whether there is anything to show or about where the cap
 * falls — which is exactly the kind of thing two call sites get subtly different.
 */
function countLabel(count: number | undefined, cap: number): string | null {
  if (typeof count !== 'number' || !Number.isFinite(count) || count <= 0) {
    return null
  }

  return count > cap ? `${cap}+` : String(count)
}
