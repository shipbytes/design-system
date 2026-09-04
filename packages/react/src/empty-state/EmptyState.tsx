import type { HTMLAttributes, ReactNode } from 'react'
import { Icon } from '../icon'
import { cn } from '../lib/cn'

/**
 * Copied from resources/views/components/empty-state.blade.php.
 * See specs/empty-state.md.
 *
 * There is no `children`. The body IS `title` and `description`, and the button
 * is `action` — the Blade version throws when something is passed to its default
 * slot, because an anonymous component silently drops content it never renders
 * and the failure is an empty state whose one useful control is missing.
 *
 * React cannot throw on that as usefully: `children` simply is not in the props
 * type, so the same mistake is a compile error at the call site instead. Same
 * protection, caught earlier.
 */

export type EmptyStateTone = 'neutral' | 'accent' | 'success' | 'warning' | 'danger'

const tiles: Record<EmptyStateTone, string> = {
  neutral: 'bg-neutral-tint text-on-neutral-tint',
  accent: 'bg-accent-tint text-on-accent-tint',
  success: 'bg-success-tint text-on-success-tint',
  warning: 'bg-warning-tint text-on-warning-tint',
  danger: 'bg-danger-tint text-on-danger-tint',
}

export interface EmptyStateProps extends Omit<HTMLAttributes<HTMLDivElement>, 'children' | 'title'> {
  /** The one line that says what is not here. */
  title: ReactNode
  /** Why it is empty, or what to do about it. One sentence. */
  description?: ReactNode
  /** Registry icon name for the mark above the title. */
  icon?: string
  /**
   * An empty state is not an error. `neutral` is the default because most
   * emptiness is simply the beginning — nothing has gone wrong yet, and a
   * coloured tile on a brand-new account tells the reader it has.
   */
  tone?: EmptyStateTone
  /** Drop the border and padding, for an empty state already inside a panel. */
  bare?: boolean
  action?: ReactNode
}

export function EmptyState({
  title,
  description,
  icon,
  tone = 'neutral',
  bare = false,
  action,
  className,
  ...props
}: EmptyStateProps) {
  const tile = tiles[tone] ?? tiles.neutral

  return (
    <div
      className={cn(
        'flex flex-col items-center justify-center text-center',
        bare
          ? 'px-4 py-8'
          : 'rounded-panel border border-dashed border-border-strong bg-surface px-6 py-12',
        className,
      )}
      {...props}
    >
      {icon ? (
        // Decorative: the title below says the same thing in words, and an icon
        // announced before it delays the sentence that matters.
        <span className={cn('mb-4 flex size-12 items-center justify-center rounded-full', tile)}>
          <Icon name={icon} size="6" />
        </span>
      ) : null}

      {/* section, not heading: this sits INSIDE a page that already has a
          heading, and a second display-weight line competes with it. */}
      <p className="text-section font-semibold text-fg">{title}</p>

      {description ? (
        // max-w so the sentence wraps at a readable measure instead of
        // stretching the full width of a table it replaced.
        <p className="mt-1.5 max-w-sm text-body text-fg-muted">{description}</p>
      ) : null}

      {action ? (
        <div className="mt-5 flex flex-wrap items-center justify-center gap-2">{action}</div>
      ) : null}
    </div>
  )
}
