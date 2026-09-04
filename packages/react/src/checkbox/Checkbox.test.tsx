import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Checkbox } from './Checkbox'

describe('Checkbox', () => {
  it('is the native input, styled — not a decorated span beside a hidden one', () => {
    // A visually-hidden checkbox loses high-contrast rendering, the browser's
    // own focus behaviour and forced-colors support, and every one of those
    // failures is invisible in a normal browser.
    render(<Checkbox label="On the presence list" />)

    const box = screen.getByRole('checkbox')

    expect(box.tagName).toBe('INPUT')
    expect(box.className).toContain('appearance-none')
    expect(box.className).toContain('forced-colors:appearance-auto')
  })

  it('ties the label to the input, so its whole width is a click target', async () => {
    render(<Checkbox label="May raise a purchase requisition" />)

    await userEvent.click(screen.getByText('May raise a purchase requisition'))

    expect(screen.getByRole('checkbox')).toBeChecked()
  })

  it('sets indeterminate as a PROPERTY, since no attribute can', () => {
    render(<Checkbox label="All" indeterminate />)

    const box = screen.getByRole('checkbox') as HTMLInputElement

    expect(box.indeterminate).toBe(true)
    // And it still reports unchecked, which is what the mixed mark means.
    expect(box.checked).toBe(false)
  })

  it('replaces help with the error rather than stacking them', () => {
    render(<Checkbox label="Current" help="Only one year is current." error="Another year already is." />)

    expect(screen.getByText('Another year already is.')).toBeInTheDocument()
    expect(screen.queryByText('Only one year is current.')).toBeNull()
    expect(screen.getByRole('checkbox')).toHaveAttribute('aria-invalid', 'true')
  })

  it('describes the control by whichever message is showing', () => {
    render(<Checkbox label="Crosses midnight" help="Set it when the shift ends the next day." />)

    const box = screen.getByRole('checkbox')
    const describedBy = box.getAttribute('aria-describedby')

    expect(describedBy).toBeTruthy()
    expect(document.getElementById(describedBy!)).toHaveTextContent('Set it when the shift ends the next day.')
  })
})
