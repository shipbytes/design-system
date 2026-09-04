import { describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Pagination, paginationWindow } from './Pagination'

describe('paginationWindow', () => {
  it('keeps the first and last page and elides the rest', () => {
    expect(paginationWindow(8, 16)).toEqual([1, null, 7, 8, 9, null, 16])
  })

  it('prints a gap of exactly one page rather than eliding it', () => {
    // "1 … 3" is the same width as "1 2 3" and hides a page for nothing.
    expect(paginationWindow(4, 6)).toEqual([1, 2, 3, 4, 5, 6])
  })

  it('renders nothing at all for a single page', () => {
    expect(paginationWindow(1, 1)).toEqual([])
  })
})

describe('Pagination', () => {
  it('renders nothing on a single page, so no call site needs a condition', () => {
    const { container } = render(<Pagination page={1} perPage={50} total={20} onChange={() => {}} />)

    expect(container).toBeEmptyDOMElement()
  })

  it('marks the current page as a raised card, never a colour', () => {
    render(<Pagination page={3} perPage={10} total={100} onChange={() => {}} />)

    const current = screen.getByRole('button', { name: 'Go to page 3' })

    expect(current).toHaveAttribute('aria-current', 'page')
    expect(current.className).toContain('shadow-raised')
    // The accent is spoken for by links; colouring "you are here" collides.
    expect(current.className).not.toContain('bg-accent')
  })

  it('keeps a disabled arrow visible rather than removing it', () => {
    // A row that drops its first control on page 1 shifts every other control
    // left, so "next" moves the moment you use it.
    render(<Pagination page={1} perPage={10} total={100} onChange={() => {}} />)

    const previous = screen.getAllByRole('button', { name: 'Previous page' })

    expect(previous.length).toBeGreaterThan(0)
    previous.forEach((button) => expect(button).toBeDisabled())
  })

  it('says which rows are showing', () => {
    render(<Pagination page={4} perPage={15} total={237} onChange={() => {}} />)

    expect(screen.getByText(/Showing/)).toHaveTextContent('Showing 46 to 60 of 237 results')
  })

  it('reports the page that was asked for', async () => {
    const onChange = vi.fn()
    render(<Pagination page={3} perPage={10} total={100} onChange={onChange} />)

    await userEvent.click(screen.getByRole('button', { name: 'Go to page 4' }))

    expect(onChange).toHaveBeenCalledWith(4)
  })

  it('shows no position when it is simple, because a simple paginator knows none', () => {
    render(<Pagination page={2} perPage={10} total={100} simple onChange={() => {}} />)

    expect(screen.queryByRole('button', { name: 'Go to page 1' })).toBeNull()
    expect(screen.queryByText(/Showing/)).toBeNull()
  })

  it('labels every numbered link, so a screen reader gets more than bare digits', () => {
    render(<Pagination page={1} perPage={10} total={30} onChange={() => {}} />)

    expect(screen.getByRole('button', { name: 'Go to page 2' })).toBeInTheDocument()
  })
})
