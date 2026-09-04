import type { AnchorHTMLAttributes, HTMLAttributes, ReactNode } from 'react'
import { cn } from '../lib/cn'
import { Icon } from '../icon/Icon'

export type PanelIconTone = 'accent' | 'success' | 'warning' | 'danger' | 'neutral'

const iconTones: Record<PanelIconTone, string> = {
  accent: 'bg-accent-tint text-on-accent-tint',
  success: 'bg-success-tint text-on-success-tint',
  warning: 'bg-warning-tint text-on-warning-tint',
  danger: 'bg-danger-tint text-on-danger-tint',
  neutral: 'bg-neutral-tint text-on-neutral-tint',
}

export interface PanelProps extends Omit<HTMLAttributes<HTMLDivElement>, 'title'> {
  /** Panel heading. Omit for a bare bordered container. */
  title?: ReactNode
  /** Second line under the title. Only meaningful with an icon. */
  subtitle?: ReactNode
  /** Heroicon name for the leading tile. Its presence switches to the feature look. */
  icon?: string
  iconTone?: PanelIconTone
  /** Trailing header link, e.g. "View all". */
  action?: ReactNode
  actionHref?: string
  /** Replaces the whole generated header. */
  header?: ReactNode
  /**
   * rows  — children separated by dividers, each managing its own padding
   * plain — a single padded region, for free-form content
   */
  body?: 'rows' | 'plain'
  children?: ReactNode
}

export function Panel({
  title,
  subtitle,
  icon,
  iconTone = 'accent',
  action,
  actionHref,
  header,
  body = 'rows',
  className,
  children,
  ...props
}: PanelProps) {
  const feature = Boolean(icon)

  /*
   * A feature panel sits on the page in its own right and gets the larger
   * radius and the solid edge. A list panel sits in a column of siblings and
   * stays quiet.
   *
   * Both paint their own surface AND their own foreground. A component that
   * paints its own background must set its own text colour — the Blade version
   * once painted only the surface and rendered near-invisible text on the dark
   * theme, because a host had always happened to set a colour above it.
   */
  const shell = feature
    ? 'rounded-panel border border-border-strong bg-surface text-fg-body overflow-hidden'
    : 'rounded-control border border-border bg-surface text-fg-body'

  const hasHeader = Boolean(title || action || header)

  return (
    <div className={cn(shell, className)} {...props}>
      {hasHeader ? (
        <div
          className={cn(
            'flex items-center justify-between gap-3',
            feature ? 'px-5 pt-5 sm:px-6 sm:pt-6' : 'border-b border-border px-4 py-3',
          )}
        >
          {header ?? (
            <div className="flex min-w-0 items-center gap-2.5">
              {feature && icon ? (
                <span
                  className={cn(
                    'flex size-8 shrink-0 items-center justify-center rounded-control',
                    iconTones[iconTone] ?? iconTones.accent,
                  )}
                >
                  <Icon name={icon} />
                </span>
              ) : null}
              <div className="min-w-0">
                {/* The title is content: it wraps rather than truncating.
                    `balance` keeps a two-line wrap from leaving an orphan word. */}
                <h2 className="text-section text-balance text-fg">{title}</h2>
                {subtitle ? (
                  /* fg-MUTED, not fg-subtle: a subtitle is the only place its
                     content appears, so it is text a reader must be able to
                     read. See specs/color.md. */
                  <p className="truncate text-meta text-fg-muted">{subtitle}</p>
                ) : null}
              </div>
            </div>
          )}

          {action ? (
            // Secondary by design: the panel's content is the point, and a
            // "View all" that competes with it pulls the eye off the data.
            <a
              href={actionHref}
              className="shrink-0 whitespace-nowrap text-meta font-medium text-fg-muted transition-colors hover:text-fg"
            >
              {action}
            </a>
          ) : null}
        </div>
      ) : null}

      <div
        className={cn(
          body === 'rows' && 'divide-y divide-divider',
          body === 'plain' && 'px-5 py-5 sm:px-6 sm:py-6',
          body === 'plain' && feature && 'pt-4',
        )}
      >
        {children}
      </div>
    </div>
  )
}

export type PanelRowProps =
  | (HTMLAttributes<HTMLDivElement> & { href?: undefined })
  | (AnchorHTMLAttributes<HTMLAnchorElement> & { href: string })

/**
 * Rows carry their own padding rather than the panel padding the body: a hover
 * state that stops short of the panel edge looks like a mistake.
 */
export function PanelRow(props: PanelRowProps) {
  const { className, children, ...rest } = props

  // Its own foreground, even though the panel sets one: a row is the
  // sub-component most likely to be used elsewhere, and an inherited text
  // colour is only correct until it is not.
  const base = 'flex items-center gap-3 px-4 py-3 text-body text-fg-body'

  if ('href' in rest && rest.href !== undefined) {
    return (
      <a
        {...(rest as AnchorHTMLAttributes<HTMLAnchorElement>)}
        className={cn(base, 'transition-colors hover:bg-surface-subtle', className)}
      >
        {children}
      </a>
    )
  }

  // No href, no hover: a hover affordance on something unclickable is a lie.
  return (
    <div {...(rest as HTMLAttributes<HTMLDivElement>)} className={cn(base, className)}>
      {children}
    </div>
  )
}
