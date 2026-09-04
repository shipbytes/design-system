import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import { Table, TableCell, TableRow } from './Table'

describe('Table', () => {
  it('scrolls inside its own box rather than pushing the page sideways', () => {
    const { container } = render(
      <Table columns={['Code']}>
        <TableRow>
          <TableCell>KG</TableCell>
        </TableRow>
      </Table>,
    )

    // The spec calls the scroll container not optional: without it a single
    // long cell makes the whole layout scroll.
    expect(container.firstElementChild?.className).toContain('overflow-x-auto')
  })

  it('reserves the width of a column with no heading', () => {
    render(<Table columns={[{ label: '', width: 'w-14', key: 'actions' }]} />)

    const headers = screen.getAllByRole('columnheader')

    expect(headers).toHaveLength(1)
    expect(headers[0]!.className).toContain('w-14')
    expect(headers[0]).toHaveTextContent('')
  })

  it('accepts a plain string as a column', () => {
    render(<Table columns={['Code', 'Name']} />)

    expect(screen.getAllByRole('columnheader').map((h) => h.textContent)).toEqual(['Code', 'Name'])
  })

  it('takes a hand-written head when no columns are given', () => {
    render(
      <Table
        head={
          <thead>
            <tr>
              <th scope="col">Sortable</th>
            </tr>
          </thead>
        }
      />,
    )

    expect(screen.getByRole('columnheader')).toHaveTextContent('Sortable')
  })
})

describe('TableRow', () => {
  it('hovers by default, because almost every row here is clickable somewhere', () => {
    render(
      <table>
        <tbody>
          <TableRow>
            <td>a</td>
          </TableRow>
        </tbody>
      </table>,
    )

    expect(screen.getByRole('row').className).toContain('hover:bg-surface-subtle')
  })

  it('drops the hover for a genuinely inert row — the affordance would be a lie', () => {
    render(
      <table>
        <tbody>
          <TableRow hover={false}>
            <td>a</td>
          </TableRow>
        </tbody>
      </table>,
    )

    expect(screen.getByRole('row').className).not.toContain('hover:')
  })
})

describe('TableCell', () => {
  it('sets its own type rather than inheriting the browser default', () => {
    render(
      <table>
        <tbody>
          <tr>
            <TableCell>12</TableCell>
          </tr>
        </tbody>
      </table>,
    )

    expect(screen.getByRole('cell').className).toContain('text-body')
  })

  it('can refuse to wrap, for a date or a count', () => {
    render(
      <table>
        <tbody>
          <tr>
            <TableCell nowrap align="right">
              04-Sep-2026
            </TableCell>
          </tr>
        </tbody>
      </table>,
    )

    const cell = screen.getByRole('cell')

    expect(cell.className).toContain('whitespace-nowrap')
    expect(cell.className).toContain('text-right')
  })
})
