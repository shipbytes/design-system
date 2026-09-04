import type { HTMLAttributes } from 'react'
import { Icon } from '../icon'
import { cn } from '../lib/cn'

/**
 * Rewritten from specs/pagination.md, not ported.
 *
 * The Blade side is a VIEW, resolved by `$paginator->links()` and handed
 * `$paginator` and `$elements` by the framework. There is nothing to copy: a
 * React consumer has a page number and a total, not a Laravel paginator. What
 * carries over is every decision the spec records, and those are the whole
 * value of it.
 */

/**
 * The page numbers and gaps to render, given where we are.
 *
 * A window around the current page, always with the first and last, and `null`
 * where a gap is elided. Extracted so the arithmetic is testable without a DOM.
 */
export function paginationWindow(current: number, last: number, around = 1): (number | null)[] {
  if (last <= 1) {
    return []
  }

  const pages = new Set<number>([1, last])

  for (let page = current - around; page <= current + around; page++) {
    if (page >= 1 && page <= last) {
      pages.add(page)
    }
  }

  const sorted = [...pages].sort((a, b) => a - b)
  const out: (number | null)[] = []

  sorted.forEach((page, index) => {
    const previous = sorted[index - 1]

    // A gap of exactly one page is printed rather than elided: "1 … 3" is the
    // same width as "1 2 3" and hides a page for nothing.
    if (previous !== undefined && page - previous === 2) {
      out.push(page - 1)
    } else if (previous !== undefined && page - previous > 2) {
      out.push(null)
    }

    out.push(page)
  })

  return out
}

const cell =
  'flex size-control-md items-center justify-center rounded-control text-body tabular-nums transition-colors ' +
  'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring'

export interface PaginationProps extends Omit<HTMLAttributes<HTMLElement>, 'onChange'> {
  page: number
  perPage: number
  total: number
  onChange: (page: number) => void
  /**
   * Previous/next only, with no position. A simple paginator never runs a COUNT
   * query — that is the entire point of it — so it knows neither the total nor
   * the last page, and there is nothing truthful to show.
   */
  simple?: boolean
  label?: string
}

export function Pagination({
  page,
  perPage,
  total,
  onChange,
  simple = false,
  label = 'Pagination',
  className,
  ...props
}: PaginationProps) {
  const last = Math.max(1, Math.ceil(total / Math.max(1, perPage)))
  const current = Math.min(Math.max(1, page), last)

  // The view renders nothing at all on a single page; wrapping the call site in
  // a condition is redundant.
  if (last <= 1) {
    return null
  }

  const from = (current - 1) * perPage + 1
  const to = Math.min(current * perPage, total)

  // Disabled arrows stay VISIBLE, faded rather than removed. A row that drops
  // its first control on page 1 shifts every other control left, so the "next"
  // arrow moves the moment you use it.
  const arrow = (direction: 'previous' | 'next') => {
    const target = direction === 'previous' ? current - 1 : current + 1
    const disabled = direction === 'previous' ? current === 1 : current === last

    return (
      <button
        type="button"
        onClick={() => onChange(target)}
        disabled={disabled}
        aria-label={direction === 'previous' ? 'Previous page' : 'Next page'}
        className={cn(
          cell,
          disabled
            ? 'cursor-not-allowed text-fg-subtle'
            : 'text-fg-muted hover:bg-surface-subtle hover:text-fg',
        )}
      >
        <Icon name={direction === 'previous' ? 'chevron-left' : 'chevron-right'} variant="mini" size="4" />
      </button>
    )
  }

  return (
    <nav aria-label={label} className={cn('flex items-center justify-between gap-4', className)} {...props}>
      {/* Below sm the row carries the POSITION. The stock view is previous/next
          only, which leaves a phone with no way to tell page 2 from page 20. */}
      <div className="flex w-full items-center justify-between gap-2 sm:hidden">
        {arrow('previous')}
        {simple ? null : (
          <span className="text-body tabular-nums text-fg-muted">
            {current} / {last}
          </span>
        )}
        {arrow('next')}
      </div>

      <div className="hidden w-full items-center justify-between gap-4 sm:flex">
        {simple ? (
          <span />
        ) : (
          <p className="text-body text-fg-muted">
            Showing <span className="tabular-nums text-fg">{from}</span> to{' '}
            <span className="tabular-nums text-fg">{to}</span> of{' '}
            <span className="tabular-nums text-fg">{total}</span> results
          </p>
        )}

        <div className="flex items-center gap-1">
          {arrow('previous')}

          {simple
            ? null
            : paginationWindow(current, last).map((entry, index) =>
                entry === null ? (
                  <span
                    key={`gap-${index}`}
                    aria-hidden="true"
                    className={cn(cell, 'text-fg-subtle')}
                  >
                    …
                  </span>
                ) : (
                  <button
                    key={entry}
                    type="button"
                    onClick={() => onChange(entry)}
                    aria-label={`Go to page ${entry}`}
                    aria-current={entry === current ? 'page' : undefined}
                    className={cn(
                      cell,
                      // The current page is a RAISED CARD, never a colour — the
                      // same treatment as the active nav item. "You are here"
                      // means the same thing in both places, and it survives
                      // dark mode as a change of elevation rather than a fill
                      // that would have to be redefined against a dark ground.
                      entry === current
                        ? 'border border-border-strong bg-surface text-fg shadow-raised'
                        : 'text-fg-muted hover:bg-surface-subtle hover:text-fg',
                    )}
                  >
                    {entry}
                  </button>
                ),
              )}

          {arrow('next')}
        </div>
      </div>
    </nav>
  )
}
