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
  /**
   * Offer a page size. Absent, there is none and the markup is unchanged.
   *
   * Only offer sizes the SERVER will honour: a list that asks for 500 and is
   * quietly clamped to 200 reports a page size it does not have, and the reader
   * has no way to tell.
   */
  perPageOptions?: number[]
  onPerPageChange?: (perPage: number) => void
}

export function Pagination({
  page,
  perPage,
  total,
  onChange,
  simple = false,
  label = 'Pagination',
  perPageOptions,
  onPerPageChange,
  className,
  ...props
}: PaginationProps) {
  const last = Math.max(1, Math.ceil(total / Math.max(1, perPage)))
  const current = Math.min(Math.max(1, page), last)
  const paged = last > 1
  const sizes = perPageOptions?.length && onPerPageChange ? perPageOptions : null

  /*
   * Nothing to page and no size to choose: render nothing, so a call site need
   * not wrap this in a condition.
   *
   * A single page with a size control still renders, and that is the case worth
   * spelling out. Raising the size to 100 can turn two pages into one; if the
   * whole row vanished with the second page, the control that did it would be
   * gone too and there would be no way back to 25.
   */
  if (!paged && !sizes) {
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
        {paged ? arrow('previous') : <span />}
        {simple || !paged ? null : (
          <span className="text-body tabular-nums text-fg-muted">
            {current} / {last}
          </span>
        )}
        {paged ? arrow('next') : <span />}
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
          {sizes ? <PerPage value={perPage} options={sizes} onChange={onPerPageChange!} /> : null}

          {paged ? arrow('previous') : null}

          {simple || !paged
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

          {paged ? arrow('next') : null}
        </div>
      </div>
    </nav>
  )
}

/**
 * How many rows a page holds.
 *
 * A native `<select>`: it is four numbers, it sits in a row of icon-sized
 * controls, and a listbox here would be a popover to pick between 25 and 50.
 * `Input as="select"` carries a labelled field's padding, which is wrong beside
 * a 32px arrow.
 */
function PerPage({
  value,
  options,
  onChange,
}: {
  value: number
  options: number[]
  onChange: (perPage: number) => void
}) {
  return (
    <label className="mr-2 flex items-center gap-2">
      <span className="sr-only">Rows per page</span>
      <select
        value={value}
        onChange={(event) => onChange(Number(event.target.value))}
        className="h-8 rounded-control border border-border bg-surface px-2 text-body text-fg-body hover:border-border-strong focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
      >
        {/*
         * The value in force is offered even when it is not one of the options —
         * a list arriving on the server's default of 50 with options of 25 and
         * 100 would otherwise show 25 while displaying fifty rows.
         */}
        {[...new Set([...options, value])]
          .sort((a, b) => a - b)
          .map((option) => (
            <option key={option} value={option}>
              {option} per page
            </option>
          ))}
      </select>
    </label>
  )
}
