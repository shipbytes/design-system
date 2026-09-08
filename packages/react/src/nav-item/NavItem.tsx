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
  /** A count on the right. Hidden with the label when collapsed. */
  badge?: ReactNode
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
      className={cn(
        'flex items-center rounded-lg border transition-colors',
        // `section` size at `medium` weight: 14/20, not the body 14/24. A rail
        // is a list of short labels, and the looser leading makes every row
        // four pixels taller for nothing.
        'text-section font-medium',
        collapsed ? 'size-8 justify-center p-2' : 'w-full gap-3 px-2 py-2',
        active
          ? 'border-strong bg-surface text-fg-body shadow-raised'
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
          aria-hidden="true"
        >
          {icon}
        </span>
      ) : null}

      {/*
       * Both hidden together, and by the same condition. The spec derives the
       * label's visibility from the collapse expression rather than from a
       * second one, so the two can never disagree — a collapsed rail showing a
       * count beside no label is the failure that rule prevents.
       */}
      {collapsed ? null : (
        <>
          <span className="min-w-0 flex-1 truncate text-left">{label}</span>
          {badge ? <span className="shrink-0 text-meta text-fg-muted">{badge}</span> : null}
        </>
      )}
    </Component>
  )
}
