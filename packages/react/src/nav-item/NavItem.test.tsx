import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import { NavItem } from './NavItem'

describe('NavItem', () => {
  it('tells a screen reader which item is current', () => {
    /*
     * `aria-current="page"`. The hand-written version the spec was written from
     * did not carry it, so the ONLY signal that a nav item was current was
     * visual — a screen reader user had no way to tell where they were.
     */
    render(
      <>
        <NavItem label="Gate" href="/gate" active />
        <NavItem label="Stores" href="/stores" />
      </>,
    )

    expect(screen.getByRole('link', { name: 'Gate' })).toHaveAttribute('aria-current', 'page')
    // Absent, not `false` — some assistive technology announces the false.
    expect(screen.getByRole('link', { name: 'Stores' })).not.toHaveAttribute('aria-current')
  })

  it('marks the active item by elevation rather than by colour', () => {
    /*
     * A raised white card and never a colour. Colour would have to compete with
     * the icon and the label for the same signal and survive both themes; a
     * change of ELEVATION reads instantly and means the same thing in dark.
     */
    render(<NavItem label="Gate" href="/gate" active />)

    const item = screen.getByRole('link', { name: 'Gate' })

    expect(item.className).toContain('shadow-raised')
    expect(item.className).toContain('border-strong')
    expect(item.className).toContain('bg-surface')
  })

  it('is one element in both states, not two components', () => {
    /*
     * The rail animates its width and the item follows. Two components would
     * need two sets of every fix, and several of them would only get one — the
     * twenty-nine-items-across-two-forked-files problem the spec opens with.
     */
    const { rerender, container } = render(<NavItem label="Gate" href="/gate" />)

    const expanded = container.firstElementChild

    rerender(<NavItem label="Gate" href="/gate" collapsed />)

    expect(container.firstElementChild).toBe(expanded)
  })

  it('gives a collapsed item its name back, and does not double it when expanded', () => {
    /*
     * Collapsed, the label is gone from the accessible tree along with the
     * pixels, so the row needs an `aria-label`. Expanded it must NOT have one:
     * an `aria-label` over visible text overrides the text, and the two then
     * drift apart with nobody able to see it.
     */
    const { rerender } = render(<NavItem label="Weighbridge" href="/wb" collapsed />)

    expect(screen.getByRole('link', { name: 'Weighbridge' })).toHaveAttribute('aria-label', 'Weighbridge')

    rerender(<NavItem label="Weighbridge" href="/wb" />)

    expect(screen.getByRole('link', { name: 'Weighbridge' })).not.toHaveAttribute('aria-label')
  })

  it('hides the label and the count together', () => {
    // Derived from one condition, so the two can never disagree — a collapsed
    // rail showing a count beside no label is what that rule prevents.
    const { rerender } = render(<NavItem label="Approvals" href="/a" badge={12} />)

    expect(screen.getByText('12')).toBeInTheDocument()

    rerender(<NavItem label="Approvals" href="/a" badge={12} collapsed />)

    expect(screen.queryByText('12')).not.toBeInTheDocument()
    expect(screen.queryByText('Approvals')).not.toBeInTheDocument()
  })

  it('renders as whatever the shell needs it to be', () => {
    // A router Link, an anchor, a button. A nav rail in a single-page
    // application is not a list of anchors, and a component that assumed one
    // would be unusable in the place it was built for.
    render(
      <NavItem as="button" label="Sign out" onClick={() => {}} />,
    )

    expect(screen.getByRole('button', { name: 'Sign out' })).toBeInTheDocument()
  })

  it('keeps a chipped icon one colour whatever the state', () => {
    /*
     * The chip is already a container. Making the icon change colour too gives
     * the active item three simultaneous signals — card, chip, icon — for a
     * single fact.
     */
    const { rerender, container } = render(
      <NavItem label="Gate" href="/gate" chipped icon={<svg data-testid="icon" />} />,
    )

    const inactive = container.querySelector('span')?.className

    rerender(<NavItem label="Gate" href="/gate" chipped active icon={<svg data-testid="icon" />} />)

    expect(container.querySelector('span')?.className).toBe(inactive)
  })
})
