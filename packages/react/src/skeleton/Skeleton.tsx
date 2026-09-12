import type { HTMLAttributes } from 'react'
import { cn } from '../lib/cn'

export type SkeletonVariant = 'text' | 'block' | 'circle' | 'table'
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
  /** `table` only: how many body rows to stand in for. */
  rows?: number
  /** `table` only: how many columns. Widths vary by position, not at random. */
  columns?: number
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
  rows = 6,
  columns = 5,
  className,
  ...props
}: SkeletonProps) {
  const count = Math.max(1, Math.trunc(lines))

  return (
    <div className={cn('w-full', className)} aria-hidden="true" {...props}>
      {variant === 'table' ? (
        <TableSkeleton rows={rows} columns={columns} />
      ) : variant === 'circle' ? (
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

/**
 * A grid, because a grid is what is coming.
 *
 * The `text` variant's own comment says it: a block of equal-length bars reads
 * as a table. A list that loaded behind a stack of paragraph bars and then
 * became a table shifted everything on the screen at the moment the rows
 * arrived — the reader's eye had already settled somewhere the content was
 * not going to be.
 *
 * Column widths vary by POSITION and not at random: the first column is a code
 * or a name and the last is usually a status or an action, so a fixed pattern
 * stands in for the real thing rather than flickering differently on every
 * render.
 */
function TableSkeleton({ rows, columns }: { rows: number; columns: number }) {
  const r = Math.max(1, Math.trunc(rows))
  const c = Math.max(1, Math.trunc(columns))

  // Repeating, so any column count keeps a rhythm instead of running out.
  const widths = ['w-24', 'w-40', 'w-28', 'w-20', 'w-32', 'w-16']

  return (
    <div className="flex w-full flex-col">
      <div className="flex items-center gap-4 border-b border-divider px-4 py-3">
        {Array.from({ length: c }, (_, i) => (
          <div key={i} className={cn('h-3 rounded-chip', widths[i % widths.length], fill, pulse)} />
        ))}
      </div>

      {Array.from({ length: r }, (_, row) => (
        <div key={row} className="flex items-center gap-4 border-b border-divider px-4 py-3 last:border-0">
          {Array.from({ length: c }, (_, i) => (
            <div
              key={i}
              className={cn('h-4 rounded-chip', widths[i % widths.length], fill, pulse)}
            />
          ))}
        </div>
      ))}
    </div>
  )
}
