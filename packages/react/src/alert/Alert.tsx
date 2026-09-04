import type { HTMLAttributes, ReactNode } from 'react'
import { useState } from 'react'
import { cn } from '../lib/cn'
import { Icon } from '../icon/Icon'

export type AlertTone = 'accent' | 'success' | 'warning' | 'danger'

/**
 * A wash, not a tint: this is a large surface, and the badge-strength fill
 * reads as shouting at panel size. See specs/color.md and specs/alert.md.
 *
 * There is no `neutral`. An alert with no tone is a paragraph.
 */
const tones: Record<AlertTone, { bg: string; border: string; fg: string; icon: string }> = {
  success: { bg: 'bg-success-wash', border: 'border-success/25', fg: 'text-on-success-tint', icon: 'check-circle' },
  warning: { bg: 'bg-warning-wash', border: 'border-warning/25', fg: 'text-on-warning-tint', icon: 'exclamation-triangle' },
  danger: { bg: 'bg-danger-wash', border: 'border-danger/25', fg: 'text-on-danger-tint', icon: 'exclamation-circle' },
  accent: { bg: 'bg-accent-wash', border: 'border-accent/25', fg: 'text-on-accent-tint', icon: 'information-circle' },
}

export interface AlertProps extends Omit<HTMLAttributes<HTMLDivElement>, 'title'> {
  tone?: AlertTone
  /** Bold first line. Omit for the single-line form — one sentence needs no title. */
  title?: ReactNode
  /** Heroicon name. Defaults to one that matches the tone. */
  icon?: string
  /**
   * Only for alerts the reader may safely ignore. A blocking error has no
   * dismiss: dismissing it hides the reason the thing they asked for did not
   * happen.
   */
  dismissible?: boolean
  onDismiss?: () => void
  children?: ReactNode
}

export function Alert({
  tone = 'accent',
  title,
  icon,
  dismissible = false,
  onDismiss,
  className,
  children,
  ...props
}: AlertProps) {
  // An unknown tone falls back to accent rather than rendering an unpainted box.
  const t = tones[tone] ?? tones.accent
  const [dismissed, setDismissed] = useState(false)

  if (dismissed) return null

  return (
    <div
      className={cn('flex items-start gap-3 rounded-control border p-4', t.bg, t.border, t.fg, className)}
      /*
       * `status` for good news, `alert` for bad: an assertive live region
       * interrupts whatever a screen reader is saying, which is right for a
       * failure and rude for a confirmation. This is the whole a11y contract of
       * the component.
       *
       * Note a live region only announces content that ARRIVES after it is in
       * the DOM — to announce, mount the element; do not render it hidden and
       * reveal it.
       */
      role={tone === 'danger' ? 'alert' : 'status'}
      {...props}
    >
      <Icon name={icon ?? t.icon} size={4.5} className="mt-0.5" />

      <div className="min-w-0 flex-1">
        {title ? (
          <>
            <p className="text-body font-semibold">{title}</p>
            <div className="mt-0.5 text-body">{children}</div>
          </>
        ) : (
          <div className="text-body">{children}</div>
        )}
      </div>

      {dismissible ? (
        <button
          type="button"
          className="-m-1 shrink-0 rounded-chip p-1 opacity-60 transition-opacity hover:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
          aria-label="Dismiss"
          onClick={() => {
            setDismissed(true)
            onDismiss?.()
          }}
        >
          <Icon name="x-mark" />
        </button>
      ) : null}
    </div>
  )
}
