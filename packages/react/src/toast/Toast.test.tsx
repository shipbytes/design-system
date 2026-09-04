import { describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Toast, ToastRegion } from './Toast'

describe('ToastRegion', () => {
  it('is a polite live region that exists before there is anything in it', () => {
    // A live region only announces content that arrives AFTER it is in the
    // document. Render it with the first toast and that toast is silent — the
    // one a reader most needs to hear. specs/toast.md.
    render(<ToastRegion />)

    const region = screen.getByRole('region', { name: 'Notifications' })
    expect(region).toHaveAttribute('aria-live', 'polite')
    expect(region).toBeEmptyDOMElement()
  })

  it('never assertive', () => {
    // A toast reports something that already happened. Interrupting a screen
    // reader to say that a thing the reader just did worked is exactly the
    // rudeness `polite` exists to avoid.
    render(<ToastRegion position="top-center" />)
    expect(screen.getByRole('region')).not.toHaveAttribute('aria-live', 'assertive')
  })

  it('does not swallow clicks on the page underneath it', () => {
    // An invisible full-width strip that eats clicks is a bug nobody
    // attributes to the toast system.
    render(<ToastRegion />)
    expect(screen.getByRole('region').className).toContain('pointer-events-none')
  })
})

describe('Toast', () => {
  it('announces politely and carries its tone in the icon alone', () => {
    const { container } = render(<Toast tone="success">Saved.</Toast>)

    expect(screen.getByRole('status')).toHaveTextContent('Saved.')
    // Surface with a border, not the alert's tinted wash: it floats over
    // arbitrary content and has to paint an opaque ground.
    expect(screen.getByRole('status').className).toContain('bg-surface')
    expect(container.querySelector('svg')?.getAttribute('class')).toContain('text-success')
  })

  it('renders a dismiss control only when there is somewhere for it to go', async () => {
    const user = userEvent.setup()
    const onDismiss = vi.fn()

    const { rerender } = render(<Toast>Saved.</Toast>)
    expect(screen.queryByRole('button', { name: 'Dismiss' })).toBeNull()

    rerender(<Toast onDismiss={onDismiss}>Saved.</Toast>)
    await user.click(screen.getByRole('button', { name: 'Dismiss' }))

    expect(onDismiss).toHaveBeenCalledOnce()
  })
})
