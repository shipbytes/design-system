import type { HTMLAttributes, ReactNode, TdHTMLAttributes, ThHTMLAttributes } from 'react'
import { cn } from '../lib/cn'

/**
 * Copied from resources/views/components/table.blade.php, table-row and
 * table-cell. See specs/table.md.
 *
 * Three components rather than one, matching Blade, because a table's rows and
 * cells are written by the consumer and the recipes have to be reachable there.
 */

export type TableAlign = 'left' | 'center' | 'right'

export interface TableColumn {
  /** An empty label still reserves its width — that is how an actions column keeps its space. */
  label: ReactNode
  align?: TableAlign
  /** A Tailwind width class, e.g. `w-20`. */
  width?: string
  /** Distinguishes columns whose labels repeat or are empty. */
  key?: string
}

const alignments: Record<TableAlign, string> = {
  left: 'text-left',
  center: 'text-center',
  right: 'text-right',
}

export interface TableProps extends Omit<HTMLAttributes<HTMLDivElement>, 'children'> {
  /** Omit and supply your own <thead> in `head` instead. */
  columns?: (TableColumn | string)[]
  head?: ReactNode
  children?: ReactNode
}

export function Table({ columns, head, className, children, ...props }: TableProps) {
  return (
    // The scroll container is the point: a wide table scrolls inside its own
    // rounded box rather than pushing the page sideways. Without it a single
    // long cell makes the whole layout scroll, which on a phone is
    // indistinguishable from a broken page.
    <div className={cn('overflow-x-auto rounded-control border border-border', className)} {...props}>
      <table className="w-full divide-y divide-divider">
        {columns ? (
          <thead className="bg-surface-subtle">
            <tr>
              {columns.map((column, index) => {
                const c: TableColumn = typeof column === 'string' ? { label: column } : column

                return (
                  <th
                    key={c.key ?? index}
                    scope="col"
                    className={cn(
                      'px-4 py-3 text-overline uppercase text-fg-muted',
                      alignments[c.align ?? 'left'],
                      c.width,
                    )}
                  >
                    {c.label}
                  </th>
                )
              })}
            </tr>
          </thead>
        ) : (
          head
        )}

        <tbody className="divide-y divide-divider">{children}</tbody>
      </table>
    </div>
  )
}

export interface TableRowProps extends HTMLAttributes<HTMLTableRowElement> {
  /**
   * Rows hover by default because in practice almost every row in this app is
   * clickable somewhere. Pass false for a genuinely inert row — a hover
   * affordance on something that does nothing is a lie.
   */
  hover?: boolean
}

export function TableRow({ hover = true, className, children, ...props }: TableRowProps) {
  return (
    <tr className={cn(hover && 'transition-colors hover:bg-surface-subtle', className)} {...props}>
      {children}
    </tr>
  )
}

export interface TableCellProps extends TdHTMLAttributes<HTMLTableCellElement> {
  align?: TableAlign
  /** Stop the cell wrapping. Right for dates and counts, wrong for prose. */
  nowrap?: boolean
}

export function TableCell({ align = 'left', nowrap = false, className, children, ...props }: TableCellProps) {
  return (
    <td
      className={cn(
        'px-4 py-3 text-body text-fg-body',
        alignments[align],
        nowrap && 'whitespace-nowrap',
        className,
      )}
      {...props}
    >
      {children}
    </td>
  )
}

/**
 * A header cell for a hand-written `head`, so a sortable header does not have
 * to reproduce the recipe to add a button to it.
 */
export interface TableHeadCellProps extends ThHTMLAttributes<HTMLTableCellElement> {
  align?: TableAlign
}

export function TableHeadCell({ align = 'left', className, children, ...props }: TableHeadCellProps) {
  return (
    <th
      scope="col"
      className={cn('px-4 py-3 text-overline uppercase text-fg-muted', alignments[align], className)}
      {...props}
    >
      {children}
    </th>
  )
}
