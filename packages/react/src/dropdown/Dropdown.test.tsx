import { describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Dropdown, DropdownItem } from './Dropdown'

function Menu({ onDelete }: { onDelete?: () => void } = {}) {
  return (
    <Dropdown trigger={<button type="button">Actions</button>}>
      <DropdownItem icon="pencil-square" href="/edit">
        Edit
      </DropdownItem>
      <DropdownItem disabled>Duplicate</DropdownItem>
      <DropdownItem tone="danger" onSelect={onDelete}>
        Delete
      </DropdownItem>
    </Dropdown>
  )
}

describe('Dropdown', () => {
  it('tells assistive technology that the trigger owns a menu, and whether it is open', async () => {
    // Without this the menu is invisible to a screen reader — the
    // accessibility equivalent of the unstyled-vendor bug: looks completely
    // fine, is completely broken, reports nothing. specs/dropdown.md.
    const user = userEvent.setup()
    render(<Menu />)

    const trigger = screen.getByRole('button', { name: 'Actions' })
    expect(trigger).toHaveAttribute('aria-haspopup', 'menu')
    expect(trigger).toHaveAttribute('aria-expanded', 'false')

    await user.click(trigger)
    expect(trigger).toHaveAttribute('aria-expanded', 'true')
    expect(screen.getByRole('menu')).toBeInTheDocument()
  })

  it('moves between items with the arrow keys and skips the disabled one', async () => {
    // role="menu" is a promise that the arrow keys work, and that a disabled
    // item is out of the keyboard path rather than merely dim.
    const user = userEvent.setup()
    render(<Menu />)

    await user.click(screen.getByRole('button', { name: 'Actions' }))
    await user.keyboard('{ArrowDown}')

    expect(screen.getByRole('menuitem', { name: 'Edit' })).toHaveFocus()

    await user.keyboard('{ArrowDown}')
    expect(screen.getByRole('menuitem', { name: 'Delete' })).toHaveFocus()
  })

  it('renders a link for somewhere to go and never a disabled one', async () => {
    const user = userEvent.setup()
    render(<Menu />)

    await user.click(screen.getByRole('button', { name: 'Actions' }))

    expect(screen.getByRole('menuitem', { name: 'Edit' }).tagName).toBe('A')

    // A disabled <a> is still focusable and still followable by keyboard, so
    // the only reliable way to stop it going anywhere is for it not to be one.
    const disabled = screen.getByRole('menuitem', { name: 'Duplicate', hidden: true })
    expect(disabled.tagName).not.toBe('A')
    expect(disabled).toHaveAttribute('data-disabled')
  })

  it('runs the action and closes', async () => {
    const user = userEvent.setup()
    const onDelete = vi.fn()
    render(<Menu onDelete={onDelete} />)

    await user.click(screen.getByRole('button', { name: 'Actions' }))
    await user.click(screen.getByRole('menuitem', { name: 'Delete' }))

    expect(onDelete).toHaveBeenCalledOnce()
    expect(screen.queryByRole('menu')).toBeNull()
  })

  it('returns focus to the trigger when it closes', async () => {
    // Otherwise dismissing a menu drops the reader at the top of the document
    // with no idea where they were.
    const user = userEvent.setup()
    render(<Menu />)

    const trigger = screen.getByRole('button', { name: 'Actions' })
    await user.click(trigger)
    await user.keyboard('{Escape}')

    expect(trigger).toHaveFocus()
  })
})
