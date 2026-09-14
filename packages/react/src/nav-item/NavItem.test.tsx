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

  describe('count', () => {
    it('draws nothing at zero, rather than a nought', () => {
      /*
       * The whole value of a badge is that it reaches zero and goes away. A rail
       * permanently showing `0` against four entries teaches people to stop
       * reading the numbers, after which the one that matters is invisible too.
       */
      const { rerender } = render(<NavItem label="Approvals" href="/a" count={0} />)

      expect(screen.queryByText('0')).not.toBeInTheDocument()

      rerender(<NavItem label="Approvals" href="/a" count={3} />)

      expect(screen.getByText('3')).toBeInTheDocument()
    })

    it('draws nothing for a count that is not a positive number', () => {
      // A count still loading is an absent pill, never a `NaN`.
      const { rerender, container } = render(<NavItem label="Approvals" href="/a" />)

      expect(container.textContent).toBe('Approvals')

      rerender(<NavItem label="Approvals" href="/a" count={Number.NaN} />)

      expect(container.textContent).toBe('Approvals')
    })

    it('caps at 99+ in both states, from one rule', () => {
      /*
       * Past a hundred, "a lot" is the information — and four digits do not fit
       * the collapsed pip. Asserted in BOTH states because a cap living in two
       * places is exactly how the two come to disagree.
       */
      const { rerender } = render(<NavItem label="Approvals" href="/a" count={146} />)

      expect(screen.getByText('99+')).toBeInTheDocument()

      rerender(
        <NavItem label="Approvals" href="/a" count={146} collapsed icon={<svg data-testid="icon" />} />,
      )

      expect(screen.getByText('99+')).toBeInTheDocument()
    })

    it('survives the rail shutting, while the label does not', () => {
      /*
       * The spec's "hidden together" rule is about a count sitting INLINE where
       * a label used to be, which reads as a number belonging to nothing. A pip
       * anchored to the glyph is the opposite shape — visibly attached to the
       * thing it counts — and it is the only reason a shut rail can still say
       * that something needs you.
       */
      render(
        <NavItem label="Approvals" href="/a" count={7} collapsed icon={<svg data-testid="icon" />} />,
      )

      expect(screen.queryByText('Approvals')).not.toBeInTheDocument()
      expect(screen.getByText('7')).toBeInTheDocument()
    })

    it('anchors the collapsed pip to the item, not to the page', () => {
      /*
       * The pip is `absolute`, and `absolute` resolves against the nearest
       * POSITIONED ancestor — not the nearest sibling. The pip is a sibling of
       * the glyph (so `aria-hidden` on the glyph does not take the count with
       * it), so the `relative` has to be on the ITEM. With it on the icon
       * instead, the pip escaped to the viewport and rendered in the page's
       * top-right corner, hundreds of pixels from the rail.
       *
       * jsdom has no layout, so this cannot assert where it LANDS — it asserts
       * the structural fact that produces the right answer: the pip's offset
       * parent is the item. Found in a browser; kept here so it stays fixed.
       */
      const { container } = render(
        <NavItem label="Approvals" href="/a" count={9} collapsed icon={<svg />} />,
      )

      const item = container.querySelector('a')
      const pip = screen.getByText('9')

      expect(item?.className).toContain('relative')
      expect(pip.className).toContain('absolute')
      // The pip is the item's own child, so the item is what positions it.
      expect(pip.parentElement).toBe(item)
    })

    it('inverts on the active row, exactly as a tab count does', () => {
      const { rerender } = render(<NavItem label="Approvals" href="/a" count={7} />)

      expect(screen.getByText('7').className).toContain('bg-neutral-tint')

      rerender(<NavItem label="Approvals" href="/a" count={7} active />)

      expect(screen.getByText('7').className).toContain('bg-surface-inverse')
    })

    it('leaves `badge` rendering exactly as it did', () => {
      // UI-12: every design-system change is additive, with a default equal to
      // today's render. `badge` is a slot and stays one.
      render(<NavItem label="Approvals" href="/a" badge={<em>soon</em>} />)

      expect(screen.getByText('soon').tagName).toBe('EM')
    })
  })
})
