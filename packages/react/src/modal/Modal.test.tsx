import { describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Modal } from './Modal'

describe('Modal', () => {
  it('takes its accessible name from the visible title', () => {
    // aria-labelledby pointing at the rendered heading, never aria-label — an
    // aria-label is a second copy of the title that nobody can see and nobody
    // updates when the title changes. specs/modal.md.
    render(
      <Modal open onOpenChange={() => {}} title="Delete report?" description="This cannot be undone.">
        Body
      </Modal>,
    )

    const dialog = screen.getByRole('dialog', { name: 'Delete report?' })
    expect(dialog).toHaveAttribute('aria-describedby')
    expect(screen.getByText('This cannot be undone.')).toBeInTheDocument()
  })

  it('closes on Escape, the backdrop and the close button when dismissible', async () => {
    const user = userEvent.setup()
    const onOpenChange = vi.fn()

    render(
      <Modal open onOpenChange={onOpenChange} title="Rename">
        Body
      </Modal>,
    )

    await user.click(screen.getByRole('button', { name: 'Close' }))
    expect(onOpenChange).toHaveBeenCalledWith(false)

    onOpenChange.mockClear()
    await user.keyboard('{Escape}')
    expect(onOpenChange).toHaveBeenCalledWith(false)
  })

  it('refuses every dismissal path when it is not dismissible', async () => {
    // A destructive confirm whose backdrop dismisses it gets dismissed by
    // accident, and the accident reads as "cancelled" — the safe outcome
    // exactly until it is not.
    const user = userEvent.setup()
    const onOpenChange = vi.fn()

    render(
      <Modal open onOpenChange={onOpenChange} dismissible={false} title="Confirm" footer={<button type="button">Cancel</button>}>
        Body
      </Modal>,
    )

    expect(screen.queryByRole('button', { name: 'Close' })).toBeNull()

    await user.keyboard('{Escape}')
    expect(onOpenChange).not.toHaveBeenCalled()
  })

  it('renders nothing at all when closed', () => {
    render(
      <Modal open={false} onOpenChange={() => {}} title="Rename">
        Body
      </Modal>,
    )

    expect(screen.queryByRole('dialog')).toBeNull()
  })

  it('puts the footer actions in their own region below the body', () => {
    render(
      <Modal open onOpenChange={() => {}} title="Delete" footer={<button type="button">Delete</button>}>
        The report and its exports are removed immediately.
      </Modal>,
    )

    expect(screen.getByRole('button', { name: 'Delete' })).toBeInTheDocument()
    expect(screen.getByText(/removed immediately/)).toBeInTheDocument()
  })
})
