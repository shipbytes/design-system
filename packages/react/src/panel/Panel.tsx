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

/**
 * The one horizontal inset every surface in a panel shares.
 *
 * Header, body and row all read this, so a title, a toolbar and a row's text
 * line up on a single left edge. They did not before: a feature header sat at
 * `px-5 sm:px-6`, a plain header at `px-4`, a padded body at `px-5 sm:px-6` and
 * a row at `px-4` — four values, so a panel with an icon and a list had its
 * heading and its rows 8px out of step with each other.
 *
 * {@see PanelBleed} negates exactly this, which only works because there is one
 * value to negate.
 */
const PANEL_INSET = 'px-5 sm:px-6'

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
            PANEL_INSET,
            feature ? 'pt-5 sm:pt-6' : 'border-b border-border py-3',
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

      {/*
       * The body is ALWAYS inset, and {@see PanelBleed} is the way out.
       *
       * This used to be two modes, `plain` (padded) and `rows` (bare, for lists
       * whose rows pad themselves), with `rows` as the DEFAULT. Across 140 call
       * sites in the first application to consume this, `rows` was chosen
       * deliberately **nought** times and its default left 52 panels with their
       * content flush against the edge — forms, link lists, empty states and
       * toolbars, none of which is a row. The documentation was not the problem;
       * it said exactly what the modes did. The default was.
       *
       * So the mode is gone rather than reversed. A prop nobody sets on purpose,
       * whose default is wrong for all but a handful of panels, collects
       * mistakes instead of offering a choice — and leaving it in, deprecated,
       * would have kept two ways to reach the edge. There is now one rule and
       * one number, and `PANEL_INSET` is that number.
       */}
      <div className={cn(PANEL_INSET, 'py-5 sm:py-6', feature && 'pt-4')}>{children}</div>
    </div>
  )
}

/**
 * The way out of the body's inset, for something that must touch the edge.
 *
 * A divided list is the case this exists for: a row's hover highlight and the
 * divider under it stop looking deliberate the moment they stop short of the
 * panel's border. The rows inside still carry their own padding — {@see PanelRow}
 * does it, and a hand-written row uses the same `px-5 sm:px-6` — so the text
 * goes on lining up with the heading above it while the background does not.
 *
 * ```tsx
 * <Panel title="On the bridge" icon="scale">
 *   <Toolbar />
 *   <PanelBleed>
 *     <ul className="divide-y divide-divider">…</ul>
 *   </PanelBleed>
 *   <Pagination />
 * </Panel>
 * ```
 *
 * Negative margins are more fragile than padding that was never applied, which
 * is the real cost of removing the old `rows` mode. It is worth paying: a panel
 * holding a list AND a toolbar — 26 of the 33 list panels in the first
 * application to consume this — needs the inset and the breakout at once, so
 * the negative margin was unavoidable for the great majority anyway, and a mode
 * that served the remaining handful bought nothing but a second convention.
 */
export function PanelBleed({ className, children, ...props }: HTMLAttributes<HTMLDivElement>) {
  return (
    <div {...props} className={cn('-mx-5 sm:-mx-6', className)}>
      {children}
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
  const base = cn('flex items-center gap-3 py-3 text-body text-fg-body', PANEL_INSET)

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
