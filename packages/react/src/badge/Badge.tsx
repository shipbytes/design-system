import { cva, type VariantProps } from 'class-variance-authority'
import type { HTMLAttributes } from 'react'
import { cn } from '../lib/cn'

export type BadgeTone = 'neutral' | 'accent' | 'success' | 'warning' | 'danger'

/**
 * Copied from resources/views/components/badge.blade.php. See specs/badge.md.
 *
 * Tint is the default because a badge annotates, it does not act. A row of
 * eight solid badges turns a table into the badge column.
 */
export const badgeRecipe = cva(
  'inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-meta font-medium whitespace-nowrap',
  {
    variants: {
      variant: { tint: '', solid: '', outline: 'border border-border text-fg-body' },
      tone: {
        neutral: '',
        accent: '',
        success: '',
        warning: '',
        danger: '',
      },
    },
    compoundVariants: [
      { variant: 'tint', tone: 'neutral', class: 'bg-neutral-tint text-on-neutral-tint' },
      { variant: 'tint', tone: 'accent', class: 'bg-accent-tint text-on-accent-tint' },
      { variant: 'tint', tone: 'success', class: 'bg-success-tint text-on-success-tint' },
      { variant: 'tint', tone: 'warning', class: 'bg-warning-tint text-on-warning-tint' },
      { variant: 'tint', tone: 'danger', class: 'bg-danger-tint text-on-danger-tint' },
      { variant: 'solid', tone: 'neutral', class: 'bg-surface-inverse text-on-inverse' },
      { variant: 'solid', tone: 'accent', class: 'bg-accent text-on-accent' },
      // success and warning fills are light enough that a white label fails, so
      // they take on-inverse — the near-black the system already uses on light
      // fills.
      { variant: 'solid', tone: 'success', class: 'bg-success text-on-inverse' },
      { variant: 'solid', tone: 'warning', class: 'bg-warning text-on-inverse' },
      { variant: 'solid', tone: 'danger', class: 'bg-danger text-on-danger' },
    ],
    defaultVariants: { variant: 'tint', tone: 'neutral' },
  },
)

const dots: Record<BadgeTone, string> = {
  neutral: 'bg-fg-muted',
  accent: 'bg-accent',
  success: 'bg-success',
  warning: 'bg-warning',
  danger: 'bg-danger',
}

export interface BadgeProps
  extends HTMLAttributes<HTMLSpanElement>,
    Omit<VariantProps<typeof badgeRecipe>, 'tone'> {
  tone?: BadgeTone
  /** Leading status dot, in the tone's BASE colour — a tint-on-tint dot is invisible. */
  dot?: boolean
}

export function Badge({
  tone = 'neutral',
  variant = 'tint',
  dot = false,
  className,
  children,
  ...props
}: BadgeProps) {
  // An unknown tone degrades to neutral rather than throwing: a typo should
  // not blank the fill and leave unreadable text on an unpainted background.
  const safeTone: BadgeTone = tone in dots ? tone : 'neutral'

  return (
    <span className={cn(badgeRecipe({ tone: safeTone, variant }), className)} {...props}>
      {dot ? (
        // Decorative: the label already says what the state is. A badge whose
        // meaning is only the dot is a broken badge.
        <span className={cn('size-1.5 rounded-full', dots[safeTone])} aria-hidden="true" />
      ) : null}
      {children}
    </span>
  )
}
