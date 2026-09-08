import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Tooltip } from './Tooltip'

describe('Tooltip', () => {
  it('describes the trigger rather than naming it', async () => {
    /*
     * The spec's central rule. A tooltip is the least reliable place in an
     * interface to put information — no hover on a touch screen, nothing in a
     * printout, gone the moment the pointer moves — so the trigger keeps its
     * OWN accessible name and the tip only describes it.
     *
     * The common mistake is an icon-only button whose only label is its
     * tooltip, and this test is what makes that mistake visible: the button is
     * still findable by its own name with the tip open.
     */
    const user = userEvent.setup()

    render(
      <Tooltip text="Removes it from the register">
        <button type="button" aria-label="Delete vehicle" />
      </Tooltip>,
    )

    const trigger = screen.getByRole('button', { name: 'Delete vehicle' })

    await user.hover(trigger)

    const tip = await screen.findByRole('tooltip')

    expect(tip).toHaveTextContent('Removes it from the register')
    expect(trigger.getAttribute('aria-describedby')).toBe(tip.id)
    // Still named by itself, not by the tip.
    expect(screen.getByRole('button', { name: 'Delete vehicle' })).toBe(trigger)
  })

  it('appears on focus as well as on hover', async () => {
    // A tip that only appears on hover does not exist for a keyboard user, and
    // the trigger is usually an icon-only button whose whole meaning is in it.
    const user = userEvent.setup()

    render(
      <Tooltip text="Print the slip">
        <button type="button" aria-label="Print" />
      </Tooltip>,
    )

    await user.tab()

    expect(await screen.findByRole('tooltip')).toHaveTextContent('Print the slip')
  })

  it('dismisses on Escape without moving the pointer', async () => {
    /*
     * WCAG 1.4.13: content shown on hover must be dismissible without moving
     * the pointer. It is the part everyone forgets, and it matters most for the
     * case the criterion was written for — a tip covering the thing underneath
     * it.
     */
    const user = userEvent.setup()

    render(
      <Tooltip text="Print the slip">
        <button type="button" aria-label="Print" />
      </Tooltip>,
    )

    await user.tab()
    expect(await screen.findByRole('tooltip')).toBeInTheDocument()

    await user.keyboard('{Escape}')

    expect(screen.queryByRole('tooltip')).not.toBeInTheDocument()
  })

  it('keeps the trigger’s own handlers', async () => {
    // The tip clones the trigger rather than wrapping it, so it must not eat
    // the handlers the trigger already had — a button that stopped responding
    // to clicks because somebody explained it would be a strange trade.
    const user = userEvent.setup()
    let clicked = false

    render(
      <Tooltip text="Explained">
        <button type="button" onClick={() => (clicked = true)}>
          Save
        </button>
      </Tooltip>,
    )

    await user.click(screen.getByRole('button', { name: 'Save' }))

    expect(clicked).toBe(true)
  })

  it('renders the trigger alone when there is nothing to say', () => {
    /*
     * A conditionally-explained control. Without this, every caller wraps the
     * whole thing in a ternary — and an empty `aria-describedby` pointing at
     * nothing is worse than none at all.
     */
    render(
      <Tooltip>
        <button type="button">Save</button>
      </Tooltip>,
    )

    expect(screen.getByRole('button', { name: 'Save' })).not.toHaveAttribute('aria-describedby')
  })

  it('does not wrap the trigger in an element of its own', () => {
    // A wrapper span around a button changes the layout of every toolbar it
    // appears in — and the tip's aria-describedby would land on the wrapper
    // rather than on the control a screen reader announces.
    const { container } = render(
      <Tooltip text="Explained">
        <button type="button">Save</button>
      </Tooltip>,
    )

    expect(container.firstElementChild?.tagName).toBe('BUTTON')
  })
})
