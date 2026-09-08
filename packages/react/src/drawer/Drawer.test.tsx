import { describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Drawer } from './Drawer'

describe('Drawer', () => {
  it('takes its accessible name from the visible title', () => {
    render(
      <Drawer open onOpenChange={() => {}} title="Filters" description="Narrow the register.">
        Body
      </Drawer>,
    )

    const drawer = screen.getByRole('dialog', { name: 'Filters' })

    expect(drawer).toHaveAttribute('aria-describedby')
    expect(screen.getByText('Narrow the register.')).toBeInTheDocument()
  })

  it('closes on Escape and on the close button when dismissible', async () => {
    const user = userEvent.setup()
    const onOpenChange = vi.fn()

    render(
      <Drawer open onOpenChange={onOpenChange} title="Filters">
        Body
      </Drawer>,
    )

    await user.click(screen.getByRole('button', { name: 'Close' }))
    expect(onOpenChange).toHaveBeenCalledWith(false)

    onOpenChange.mockClear()
    await user.keyboard('{Escape}')
    expect(onOpenChange).toHaveBeenCalledWith(false)
  })

  it('refuses every dismissal path when it is not dismissible', async () => {
    /*
     * And then the footer MUST offer a way out — a panel with no exit is a trap
     * and the component cannot check that for you, which is why the prop is
     * named for what it does rather than for what it protects.
     */
    const user = userEvent.setup()
    const onOpenChange = vi.fn()

    render(
      <Drawer open onOpenChange={onOpenChange} dismissible={false} title="Filters">
        Body
      </Drawer>,
    )

    expect(screen.queryByRole('button', { name: 'Close' })).not.toBeInTheDocument()

    await user.keyboard('{Escape}')
    expect(onOpenChange).not.toHaveBeenCalled()
  })

  it('slides from the edge it belongs to, in one lookup', () => {
    /*
     * Position, border side and both slide directions come from ONE map. Split
     * across three lookups, a `side` change silently keeps the previous slide
     * direction and the panel flies in from the wrong edge — which looks like a
     * CSS bug and is a data-structure one.
     */
    const { rerender } = render(
      <Drawer open onOpenChange={() => {}} title="Filters">
        Body
      </Drawer>,
    )

    let panel = screen.getByRole('dialog')

    expect(panel.className).toContain('right-0')
    expect(panel.className).toContain('border-l')
    expect(panel.className).toContain('animate-drawer-in-right')

    rerender(
      <Drawer open onOpenChange={() => {}} side="left" title="Navigation">
        Body
      </Drawer>,
    )

    panel = screen.getByRole('dialog')

    expect(panel.className).toContain('left-0')
    expect(panel.className).toContain('border-r')
    expect(panel.className).toContain('animate-drawer-in-left')
    // And nothing of the right-hand edge is left behind.
    expect(panel.className).not.toContain('animate-drawer-in-right')
  })

  it('leaves a sliver of page at full width', () => {
    /*
     * `calc(100vw - 3rem)`, not `max-w-none`. A panel covering everything is a
     * screen, not a drawer; the strip of page still showing is what says the
     * thing you came from is still there and one click away. That is the whole
     * difference between the two.
     */
    render(
      <Drawer open onOpenChange={() => {}} size="full" title="Register">
        Body
      </Drawer>,
    )

    const panel = screen.getByRole('dialog')

    expect(panel.className).toContain('calc(100vw-3rem)')
    expect(panel.className).not.toContain('max-w-none')
  })

  it('scrolls the body and keeps the footer reachable', () => {
    /*
     * A drawer is usually a long list of filters, which is exactly the case
     * where losing the Apply button off the bottom matters most.
     */
    render(
      <Drawer open onOpenChange={() => {}} title="Filters" footer={<button type="button">Apply</button>}>
        <p>Body</p>
      </Drawer>,
    )

    const body = screen.getByText('Body').parentElement

    expect(body?.className).toContain('overflow-y-auto')
    expect(body?.className).toContain('flex-1')
    expect(screen.getByRole('button', { name: 'Apply' })).toBeInTheDocument()
  })

  it('is full width on a phone at every size', () => {
    // Each size is paired with `w-full`, so a drawer is a full-width panel on a
    // phone and a sized one on a desktop without a second breakpoint rule.
    render(
      <Drawer open onOpenChange={() => {}} size="sm" title="Filters">
        Body
      </Drawer>,
    )

    const panel = screen.getByRole('dialog')

    expect(panel.className).toContain('w-full')
    expect(panel.className).toContain('sm:max-w-sm')
  })
})
