import type { ReactNode } from 'react'
import { cn } from '../lib/cn'
import { Icon } from '../icon/Icon'

/*
 * specs/toast.md.
 *
 * A brief report that something ALREADY happened. Anything the reader must act
 * on is not a toast — a toast is missable by design: on screen for seconds,
 * somewhere their eyes are not, and on a phone possibly under a thumb. A failure
 * that needs a retry belongs in an Alert next to the thing that failed.
 *
 * The component holds no state. The list, the timers and the removal all belong
 * to the host, because the timing rules are host decisions: about five seconds
 * for a plain confirmation, longer or never when the toast carries an action,
 * and paused on hover and focus — a reader moving to click Undo should not watch
 * it disappear under the pointer.
 */

const tones = {
  neutral: { icon: 'information-circle', mark: 'text-fg-muted' },
  accent: { icon: 'information-circle', mark: 'text-accent' },
  success: { icon: 'check-circle', mark: 'text-success' },
  warning: { icon: 'exclamation-triangle', mark: 'text-warning' },
  danger: { icon: 'exclamation-circle', mark: 'text-danger' },
} as const

export type ToastTone = keyof typeof tones

export interface ToastProps {
  tone?: ToastTone
  /** Bold first line. Omit for the single-line form. */
  title?: ReactNode
  /** Heroicon. Defaults to one that matches the tone. */
  icon?: string
  /** Removes this toast. Renders a close control. */
  onDismiss?: () => void
  /** Buttons under the message. Give an action toast a long life, or none. */
  action?: ReactNode
  className?: string
  children?: ReactNode
}

/**
 * `surface` with a border, not the alert's tinted wash.
 *
 * It floats over arbitrary content instead of sitting in the flow, so it has to
 * paint an opaque ground — a wash over an unknown background is unreadable at
 * the one moment it matters. **The tone lives in the icon alone**, which is
 * enough at this size and keeps four stacked toasts from becoming a colour
 * chart.
 */
export function Toast({
  tone = 'neutral',
  title,
  icon,
  onDismiss,
  action,
  className,
  children,
}: ToastProps) {
  const t = tones[tone] ?? tones.neutral

  return (
    <div
      className={cn(
        'pointer-events-auto flex w-full items-start gap-3 rounded-control border',
        'border-border bg-surface p-3.5 shadow-float',
        className,
      )}
      role="status"
    >
      <Icon name={icon ?? t.icon} size={5} className={cn('mt-px shrink-0', t.mark)} />

      <div className="min-w-0 flex-1">
        {title ? (
          <>
            <p className="text-body font-semibold text-fg">{title}</p>
            <div className="mt-0.5 text-body text-fg-muted">{children}</div>
          </>
        ) : (
          <div className="text-body text-fg-body">{children}</div>
        )}

        {action ? <div className="mt-2 flex items-center gap-2">{action}</div> : null}
      </div>

      {onDismiss ? (
        <button
          type="button"
          onClick={onDismiss}
          className="-m-1 shrink-0 rounded-chip p-1 text-fg-muted transition-colors hover:bg-surface-subtle hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
          aria-label="Dismiss"
        >
          <Icon name="x-mark" size={4} />
        </button>
      ) : null}
    </div>
  )
}

const positions = {
  'top-right': 'top-0 right-0 items-end',
  'top-center': 'top-0 left-1/2 -translate-x-1/2 items-center',
  'bottom-right': 'bottom-0 right-0 items-end',
  'bottom-center': 'bottom-0 left-1/2 -translate-x-1/2 items-center',
} as const

export type ToastPosition = keyof typeof positions

export interface ToastRegionProps {
  position?: ToastPosition
  /** The region's name in a landmark list. */
  label?: string
  className?: string
  children?: ReactNode
}

/**
 * Goes in the layout **once**, and stays there empty.
 *
 * A live region only announces content that arrives after it is already in the
 * document. Render the region at the same moment as the first toast and the
 * first toast is silent — which is the one a reader most needs to hear, and the
 * failure is invisible unless you are listening for it.
 *
 * `polite`, never `assertive`: a toast reports something that already happened,
 * and interrupting a screen reader to say that a thing the reader just did
 * worked is exactly the rudeness `polite` exists to avoid.
 */
export function ToastRegion({
  position = 'bottom-right',
  label = 'Notifications',
  className,
  children,
}: ToastRegionProps) {
  const place = positions[position] ?? positions['bottom-right']

  return (
    <div
      className={cn(
        // pointer-events-none on the region and auto on each toast, so the empty
        // container never swallows clicks on the page underneath — an invisible
        // strip that eats clicks is a bug nobody attributes to the toast system.
        'pointer-events-none fixed z-50 flex max-h-screen w-full max-w-sm flex-col gap-2 p-4',
        place,
        className,
      )}
      role="region"
      aria-label={label}
      aria-live="polite"
      aria-relevant="additions"
    >
      {children}
    </div>
  )
}
