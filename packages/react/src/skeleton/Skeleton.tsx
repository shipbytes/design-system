import type { HTMLAttributes } from 'react'
import { cn } from '../lib/cn'

export type SkeletonVariant = 'text' | 'block' | 'circle'
export type SkeletonSize = 'sm' | 'md' | 'lg'

// Closed sets, mapped to literal classes. Nothing here is interpolated, so
// Tailwind's scanner sees every class it needs to generate.
const blocks: Record<SkeletonSize, string> = { sm: 'h-16', md: 'h-24', lg: 'h-40' }
const circles: Record<SkeletonSize, string> = { sm: 'size-8', md: 'size-10', lg: 'size-12' }

/**
 * `fg/10` rather than surface-subtle: a skeleton has to be visible on the card
 * AND on the sunken ground behind it, and a fixed surface token is only ever
 * right on one of them.
 */
const fill = 'bg-fg/10'

/**
 * motion-safe: a pulsing block is exactly the kind of thing that triggers
 * vestibular symptoms, and a loading state is not worth that.
 */
const pulse = 'motion-safe:animate-pulse'

export interface SkeletonProps extends HTMLAttributes<HTMLDivElement> {
  variant?: SkeletonVariant
  /** Number of bars for `text`. The last one is short, the way a paragraph ends. */
  lines?: number
  size?: SkeletonSize
}

/**
 * aria-hidden, with `aria-busy` on the region it stands in.
 *
 * A skeleton has no content to announce — it is a picture of content that does
 * not exist yet. The HOST owns the announcement; see specs/skeleton.md.
 */
export function Skeleton({
  variant = 'text',
  lines = 3,
  size = 'md',
  className,
  ...props
}: SkeletonProps) {
  const count = Math.max(1, Math.trunc(lines))

  return (
    <div className={cn('w-full', className)} aria-hidden="true" {...props}>
      {variant === 'circle' ? (
        <div className={cn(circles[size], 'shrink-0 rounded-full', fill, pulse)} />
      ) : variant === 'block' ? (
        <div className={cn('w-full rounded-control', blocks[size], fill, pulse)} />
      ) : (
        <div className="flex w-full flex-col gap-2">
          {Array.from({ length: count }, (_, i) => (
            // The last bar is short. A block of equal-length bars reads as a
            // table; a paragraph ends mid-line, and that is what makes this
            // look like the text it stands in for.
            <div
              key={i}
              className={cn(
                'h-3 rounded-chip',
                fill,
                pulse,
                i === count - 1 && count > 1 ? 'w-3/5' : 'w-full',
              )}
            />
          ))}
        </div>
      )}
    </div>
  )
}
