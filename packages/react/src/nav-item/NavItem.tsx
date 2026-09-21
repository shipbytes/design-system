import type { ElementType, ReactNode } from 'react'
import { cn } from '../lib/cn'
import { NavRowBody, navRow } from './row'

/*
 * specs/nav-item.md.
 *
 * One row of a navigation rail — icon, label, optional count — in two states
 * that are **the same element**. The rail animates its width and the item
 * follows; it does not swap between an expanded component and a collapsed one,
 * because two components would need two sets of every fix and several of them
 * would only ever get one.
 *
 * ## The look lives in `./row`, not here
 *
 * `navRow` and `NavRowBody` are this row, and `SubNav`'s parent row asks for the
 * same two. specs/sub-nav.md requires it in as many words — the parent row is
 * "the nav item's own recipe … not a second copy that drifts" — and the reason
 * is the twenty-nine-items-across-two-forked-files problem this component was
 * written to end, arriving a second time at component scale.
 *
 * ## The active state is elevation, never colour
 *
 * `border-strong` + `surface` + `shadow-raised`, against a transparent border
 * and no fill when inactive. Colour would have to compete with the icon and the
 * label for the same signal and survive both themes; a change of ELEVATION
 * reads instantly and means the same thing in dark. The recipe in `./row`
 * carries the utility spelling and the note about why `border-strong` alone
 * drew a near-black ring for months.
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
      className={cn(navRow({ active, collapsed }), className)}
    >
      <NavRowBody
        label={label}
        icon={icon}
        badge={badge}
        count={count}
        countCap={countCap}
        active={active}
        collapsed={collapsed}
        chipped={chipped}
      />
    </Component>
  )
}
