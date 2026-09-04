import { describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Alert } from './Alert'

describe('Alert', () => {
  it('interrupts for a failure and waits its turn for everything else', () => {
    // This is the whole a11y contract of the component. specs/alert.md.
    const { rerender } = render(<Alert tone="danger">Save failed.</Alert>)
    expect(screen.getByRole('alert')).toBeInTheDocument()

    rerender(<Alert tone="success">Saved.</Alert>)
    expect(screen.getByRole('status')).toBeInTheDocument()
  })

  it('falls back to accent for an unknown tone rather than rendering an unpainted box', () => {
    // @ts-expect-error — deliberately passing a tone outside the union.
    render(<Alert tone="mauve">Something</Alert>)
    expect(screen.getByRole('status').className).toContain('bg-accent-wash')
  })

  it('has no dismiss control unless asked for one', () => {
    render(<Alert tone="danger">The GRN was rejected.</Alert>)
    expect(screen.queryByRole('button', { name: 'Dismiss' })).not.toBeInTheDocument()
  })

  it('names the dismiss control and removes the alert when it is used', async () => {
    const onDismiss = vi.fn()
    render(<Alert dismissible onDismiss={onDismiss}>Heads up.</Alert>)

    await userEvent.click(screen.getByRole('button', { name: 'Dismiss' }))

    expect(screen.queryByText('Heads up.')).not.toBeInTheDocument()
    expect(onDismiss).toHaveBeenCalledOnce()
  })

  it('hides its icon from assistive technology — the message says what happened', () => {
    const { container } = render(<Alert tone="warning">Contract expires in 3 days.</Alert>)
    expect(container.querySelector('svg')).toHaveAttribute('aria-hidden', 'true')
  })
})
