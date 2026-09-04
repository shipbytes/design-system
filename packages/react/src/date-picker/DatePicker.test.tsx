import { describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { DatePicker, monthGrid } from './DatePicker'

describe('monthGrid', () => {
  it('leads with the blanks a month needs to start on the right weekday', () => {
    // 1 March 2026 is a Sunday; a Monday-first week needs six blanks.
    const cells = monthGrid('2026-03-15', 1)

    expect(cells.slice(0, 6)).toEqual([null, null, null, null, null, null])
    expect(cells[6]).toBe('2026-03-01')
    expect(cells).toHaveLength(6 + 31)
  })

  it('starts on Sunday when asked', () => {
    const cells = monthGrid('2026-03-15', 0)

    expect(cells[0]).toBe('2026-03-01')
  })

  it('handles a leap February', () => {
    expect(monthGrid('2028-02-10', 1).filter(Boolean)).toHaveLength(29)
  })
})

describe('DatePicker', () => {
  it('reports a Y-m-d string, never a Date', async () => {
    // `new Date('2026-03-29')` is UTC midnight, and west of Greenwich that is
    // the 28th — a picker built on Date objects selects the day before the one
    // that was clicked, for some users, some of the time.
    const onChange = vi.fn()
    render(<DatePicker value="2026-03-15" onChange={onChange} />)

    await userEvent.click(screen.getByRole('button', { name: /2026-03-15/ }))
    await userEvent.click(screen.getByRole('button', { name: '29' }))

    expect(onChange).toHaveBeenCalledWith('2026-03-29')
  })

  it('opens on the month the value is in, not the month last browsed to', async () => {
    render(<DatePicker value="2026-03-15" onChange={() => {}} />)

    await userEvent.click(screen.getByRole('button', { name: /2026-03-15/ }))

    expect(screen.getByText('March 2026')).toBeInTheDocument()
  })

  it('refuses a day outside min and max', async () => {
    const onChange = vi.fn()
    render(<DatePicker value="2026-03-15" min="2026-03-10" max="2026-03-20" onChange={onChange} />)

    await userEvent.click(screen.getByRole('button', { name: /2026-03-15/ }))

    expect(screen.getByRole('button', { name: '5' })).toBeDisabled()
    expect(screen.getByRole('button', { name: '12' })).toBeEnabled()
  })

  it('puts exactly one day in the tab order, so the arrows do the walking', async () => {
    render(<DatePicker value="2026-03-15" onChange={() => {}} />)

    await userEvent.click(screen.getByRole('button', { name: /2026-03-15/ }))

    const tabbable = screen
      .getAllByRole('button')
      .filter((button) => button.dataset.day && button.tabIndex === 0)

    expect(tabbable).toHaveLength(1)
    expect(tabbable[0]!.dataset.day).toBe('2026-03-15')
  })

  it('announces the month it moved to', async () => {
    render(<DatePicker value="2026-03-15" onChange={() => {}} />)

    await userEvent.click(screen.getByRole('button', { name: /2026-03-15/ }))
    await userEvent.click(screen.getByRole('button', { name: 'Next month' }))

    const heading = screen.getByText('April 2026')

    // Pressing the arrows changes the grid the reader is standing in and
    // nothing else announces it.
    expect(heading).toHaveAttribute('aria-live', 'polite')
  })

  it('collects a period in two clicks, and swaps them if they arrive backwards', async () => {
    const onChange = vi.fn()
    const { rerender } = render(<DatePicker range value={[null, null]} onChange={onChange} />)

    await userEvent.click(screen.getByRole('button', { name: 'Choose a period' }))
    await userEvent.click(screen.getByRole('button', { name: '20' }))

    expect(onChange).toHaveBeenLastCalledWith([expect.stringMatching(/-20$/), null])

    // The popover stays open after the first click — a period is two clicks,
    // and closing between them would make it four.
    const started = onChange.mock.calls.at(-1)![0] as [string, null]
    rerender(<DatePicker range value={started} onChange={onChange} />)

    await userEvent.click(screen.getByRole('button', { name: '10' }))

    const [from, to] = onChange.mock.calls.at(-1)![0] as [string, string]

    expect(from < to).toBe(true)
  })

  it('clears to null rather than to today', async () => {
    const onChange = vi.fn()
    render(<DatePicker value="2026-03-15" onChange={onChange} />)

    await userEvent.click(screen.getByRole('button', { name: /2026-03-15/ }))
    await userEvent.click(screen.getByRole('button', { name: 'Clear' }))

    expect(onChange).toHaveBeenCalledWith(null)
  })

  it('hides Clear where the field is required', async () => {
    render(<DatePicker value="2026-03-15" clearable={false} onChange={() => {}} />)

    await userEvent.click(screen.getByRole('button', { name: /2026-03-15/ }))

    expect(screen.queryByRole('button', { name: 'Clear' })).toBeNull()
  })
})
