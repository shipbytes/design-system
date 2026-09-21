import type { ReactNode } from 'react'
import { cn } from '../lib/cn'

/*
 * The nav rail's row, in ONE place.
 *
 * specs/nav-item.md draws it and specs/sub-nav.md reuses it:
 *
 *   | Parent row | the nav item's own recipe | It *is* a nav item. Resting,
 *   | hover and current appearance come from one place in each implementation
 *   | — **not a second copy that drifts**. |
 *
 * A second copy is the thing this file exists to prevent. The rail already had
 * that failure once in Blade — twenty-nine nav items across two sidebar files
 * that are a copy-paste fork of each other, where every fix has to be made
 * twice and several have not been. A sub-nav parent that merely RESEMBLED a nav
 * item would be the same mistake at component scale, and it would be invisible:
 * two rails that look alike until one of them gets the next fix.
 *
 * So the classes live here, the markup inside the row lives here, and both
 * components ask for them.
 */

/** Shape and type. Every nav row is this, collapsed or not, parent or child. */
export const navRowBase = 'relative flex items-center rounded-lg text-section font-medium transition-colors'

/**
 * "You are here" — and the only place it is written.
 *
 * `border-strong` + `surface` + `shadow-raised`: the active nav item's card, the
 * current pagination cell's card, and the current sub-nav child's. A change of
 * ELEVATION rather than a colour, because colour would have to compete with the
 * icon and the label for the same signal and be redefined against a dark ground.
 *
 * The utility is `border-border-strong`, not `border-strong`. `border-strong`
 * is the TOKEN's name and generates nothing — the row then keeps the bare
 * `border` from the base with no colour, and Tailwind v4's `currentColor`
 * default draws the card's ring in `fg-body`: near-black in light, near-white in
 * dark. That defect lived in NavItem from the day it was written.
 */
export const navRowCard = 'border-border-strong bg-surface text-fg-body shadow-raised'

/** Not current. The border is transparent rather than absent so the box does not resize. */
export const navRowResting = 'border-transparent text-fg-subtle'

/** What a PRESSABLE nav row does under the pointer. Never on a row already drawn as a card. */
export const navRowHover = 'hover:bg-surface-subtle hover:text-fg-body'

/**
 * Which piece of a row is being drawn.
 *
 * `whole` — one element that is both the row and the only target. The nav item,
 *   and a collapsed sub-nav parent, which is one control because there is no
 *   label left to press.
 *
 * `card` — a CONTAINER that draws the card and nothing else. An expanded
 *   sub-nav parent row holds a link and a button as siblings, because a
 *   `<button>` inside an `<a>` is invalid markup and the browser gives the inner
 *   control no reliable keyboard behaviour. The container draws the card so that
 *   it wraps both targets as one row, and it takes no padding and no hover: two
 *   targets that highlight as one are one target as far as the reader can tell.
 *
 * `press` — a control INSIDE such a container. It carries the row's padding and
 *   its own hover fill, and leaves the card to the container.
 */
export type NavRowPart = 'whole' | 'card' | 'press'

export interface NavRowOptions {
  active?: boolean
  collapsed?: boolean
  part?: NavRowPart
}

export function navRow({ active = false, collapsed = false, part = 'whole' }: NavRowOptions = {}): string {
  /*
   * `px-2` plus a 20px icon plus `gap-3` is what puts a nav label at 40px from
   * the row's edge — the number specs/sub-nav.md indents its children to. It is
   * written once, here, so the indent cannot drift away from the thing it lines
   * up with.
   */
  const padding = collapsed ? 'p-2' : 'gap-3 px-2 py-2'

  if (part === 'press') {
    return cn(
      navRowBase,
      // The link takes the room the arrow does not, and truncates rather than
      // pushing the arrow off the rail.
      'min-w-0 flex-1',
      padding,
      active ? 'text-fg-body' : cn('text-fg-subtle', navRowHover),
    )
  }

  return cn(
    navRowBase,
    'border',
    collapsed ? 'size-8 justify-center' : 'w-full',
    part === 'whole' ? padding : null,
    active ? navRowCard : cn(navRowResting, part === 'whole' ? navRowHover : null),
  )
}

/**
 * The count as it is drawn, or null for "draw nothing".
 *
 * One function for both states, so the expanded pill and the collapsed pip can
 * never disagree about whether there is anything to show or about where the cap
 * falls — which is exactly the kind of thing two call sites get subtly different.
 */
export function countLabel(count: number | undefined, cap: number): string | null {
  if (typeof count !== 'number' || !Number.isFinite(count) || count <= 0) {
    return null
  }

  return count > cap ? `${cap}+` : String(count)
}

export interface NavRowBodyProps {
  label: string
  icon?: ReactNode
  /** Arbitrary trailing content, rendered as given. Hidden with the label when collapsed. */
  badge?: ReactNode
  count?: number
  countCap?: number
  active?: boolean
  collapsed?: boolean
  chipped?: boolean
}

/**
 * What is INSIDE a nav row: the icon, the count in both of its shapes, the
 * label, the slot.
 *
 * A fragment rather than an element, deliberately. The collapsed count pip is
 * `absolute`, and `absolute` resolves against the nearest POSITIONED ancestor;
 * the `relative` is on the row. A wrapper here would become that ancestor and
 * move the pip.
 */
export function NavRowBody({
  label,
  icon,
  badge,
  count,
  countCap = 99,
  active = false,
  collapsed = false,
  chipped = false,
}: NavRowBodyProps) {
  const shown = countLabel(count, countCap)

  return (
    <>
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
    </>
  )
}
