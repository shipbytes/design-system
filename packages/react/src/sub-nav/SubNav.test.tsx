import { describe, expect, it, vi } from 'vitest'
import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { SubNav, SubNavItem, type SubNavProps } from './SubNav'

/*
 * specs/sub-nav.md, and the rail it was written for: /gate with four pages
 * inside it, drawn as equals in a list of twenty-three.
 */
function Gate(props: Partial<SubNavProps> = {}) {
  const { children, ...rest } = props

  return (
    <ul>
      <SubNav
        label="Gate"
        href="/gate"
        ownPageLabel="Gate dashboard"
        icon={<svg data-testid="gate-icon" />}
        open
        onOpenChange={() => {}}
        {...rest}
      >
        {children ?? (
          <>
            <SubNavItem label="Shift handovers" href="/gate/handovers" />
            <SubNavItem label="Guards on gates" href="/gate/guards" />
            <SubNavItem label="Roll calls" href="/gate/roll-calls" active />
            <SubNavItem label="Person in" href="/gate/persons" />
          </>
        )}
      </SubNav>
    </ul>
  )
}

/** The element the list's animation and its `visibility` live on. */
const listWrapper = (container: HTMLElement) =>
  container.querySelector('ul > li > div.grid') as HTMLElement

describe('SubNav', () => {
  it('navigates from the label and never from the arrow', async () => {
    /*
     * One row, two targets. One press doing both means the reader cannot reach
     * the parent's own page without also opening five rows they did not ask
     * for, and cannot open the list to look without leaving the page they are
     * on.
     */
    const onOpenChange = vi.fn()

    render(<Gate onOpenChange={onOpenChange} />)

    const label = screen.getByRole('link', { name: 'Gate' })
    const arrow = screen.getByRole('button', { name: 'Gate pages' })

    expect(label).toHaveAttribute('href', '/gate')

    // A `<button>` inside an `<a>` is invalid and the browser gives the inner
    // control no reliable keyboard behaviour, so the two are SIBLINGS.
    expect(arrow.closest('a')).toBeNull()
    expect(label.contains(arrow)).toBe(false)
    expect(arrow).not.toHaveAttribute('href')

    await userEvent.click(label)
    expect(onOpenChange).not.toHaveBeenCalled()

    await userEvent.click(arrow)
    expect(onOpenChange).toHaveBeenCalledWith(false)
  })

  it('opens and closes from the arrow, and says so on the arrow', async () => {
    const onOpenChange = vi.fn()

    const { container, rerender } = render(<Gate open={false} onOpenChange={onOpenChange} />)

    const arrow = screen.getByRole('button', { name: 'Gate pages' })
    const list = container.querySelector('ul ul') as HTMLElement

    // On the ARROW, which is the control that owns the list — not on the link,
    // which owns a page.
    expect(arrow).toHaveAttribute('aria-expanded', 'false')
    expect(screen.getByRole('link', { name: 'Gate' })).not.toHaveAttribute('aria-expanded')
    expect(list.id).not.toBe('')
    expect(arrow).toHaveAttribute('aria-controls', list.id)

    await userEvent.click(arrow)
    expect(onOpenChange).toHaveBeenCalledWith(true)

    rerender(<Gate open onOpenChange={onOpenChange} />)
    expect(screen.getByRole('button', { name: 'Gate pages' })).toHaveAttribute('aria-expanded', 'true')
  })

  it('takes a closed list out of the tab order, and keeps both targets in it', async () => {
    /*
     * `visibility: hidden` takes the children out of the tab order AND out of
     * the accessibility tree; `overflow: hidden` takes them out of neither, and
     * a rail whose hidden rows are still tabbable is the classic broken
     * disclosure — it looks perfect and walks a keyboard user through five
     * links that are not on screen.
     */
    const { container, rerender } = render(<Gate open={false} />)

    expect(listWrapper(container).className).toContain('invisible')
    expect(listWrapper(container).className).toContain('grid-rows-[0fr]')

    rerender(<Gate open />)

    expect(listWrapper(container).className).toContain('visible')
    expect(listWrapper(container).className).toContain('grid-rows-[1fr]')

    // Both reachable by Tab, in that order. The whole point of two targets is
    // that neither is removed from the tab order in favour of the other.
    await userEvent.tab()
    expect(screen.getByRole('link', { name: 'Gate' })).toHaveFocus()

    await userEvent.tab()
    expect(screen.getByRole('button', { name: 'Gate pages' })).toHaveFocus()
  })

  it('draws the raised card on the current child, and nothing at all on its parent', () => {
    /*
     * The forbidden fourth state. nav-item.md, under Do not: "Add a second
     * active-ish state for 'parent of the current page'." The behaviour carries
     * it instead — going to a page inside a closed list opens that list, and the
     * current child's card then says where the reader is.
     *
     * The absence is asserted, not assumed, because a later hand will add it
     * back by instinct: a parent that "contains the current page" looks like it
     * wants marking, and the whole spec is an argument that it does not.
     */
    const { container } = render(<Gate />)

    const child = screen.getByRole('link', { name: 'Roll calls' })

    expect(child).toHaveAttribute('aria-current', 'page')
    expect(child.className).toContain('shadow-raised')
    expect(child.className).toContain('border-border-strong')
    // The exact class: `bg-surface-subtle`, `-sunken` and `-inverse` all contain
    // the substring, and the resting child HOVERS to `bg-surface-subtle`.
    expect(child.className).toMatch(/(^|\s)bg-surface(\s|$)/)
    expect(child.className).not.toMatch(/(^|\s)border-strong(\s|$)/)

    const parentLink = screen.getByRole('link', { name: 'Gate' })
    const parentRow = parentLink.parentElement as HTMLElement

    expect(parentLink).not.toHaveAttribute('aria-current')

    for (const mark of ['shadow-raised', 'border-border-strong', 'bg-accent', 'accent-tint', 'accent-wash']) {
      expect(parentRow.className).not.toContain(mark)
      expect(parentLink.className).not.toContain(mark)
    }

    // …and the row's own card still works when the PARENT's page is current,
    // so the assertions above are about the fourth state and not about a
    // component that cannot draw a card at all.
    expect(container.querySelector('[aria-current="page"]')).toBe(child)
  })

  it('draws the card on the parent when the parent is where you are', () => {
    render(<Gate active />)

    const parentRow = screen.getByRole('link', { name: 'Gate' }).parentElement as HTMLElement

    // On the CONTAINER, so the card wraps both targets as one row.
    expect(parentRow.className).toContain('shadow-raised')
    expect(parentRow.className).toContain('border-border-strong')
    expect(screen.getByRole('link', { name: 'Gate' })).toHaveAttribute('aria-current', 'page')
  })

  it('never changes its own open state', async () => {
    /*
     * Controlled only, and there is no uncontrolled mode. The component is not
     * told the route, so it cannot implement "going to a page inside a closed
     * list opens it"; an open list is history rather than a derivation from the
     * current page; and the memory is per browser under a key the shell owns.
     *
     * What the component owes in return is that it never closes itself. It
     * reports a press and draws what it is told.
     */
    const onOpenChange = vi.fn()

    const { container } = render(<Gate open={false} onOpenChange={onOpenChange} />)

    await userEvent.click(screen.getByRole('button', { name: 'Gate pages' }))

    expect(onOpenChange).toHaveBeenCalledWith(true)
    // And nothing moved.
    expect(screen.getByRole('button', { name: 'Gate pages' })).toHaveAttribute('aria-expanded', 'false')
    expect(listWrapper(container).className).toContain('invisible')
    expect(listWrapper(container).className).toContain('grid-rows-[0fr]')
  })

  it('renders a parent with no children as a plain row, with no arrow at all', () => {
    /*
     * Not a disabled arrow and not an arrow that opens nothing: a control that
     * looks pressable and does nothing is worse than no control. A rail of
     * mostly-childless entries would otherwise grow a column of dead chevrons.
     */
    render(
      <ul>
        <SubNav label="Dashboard" href="/" open onOpenChange={() => {}} />
      </ul>,
    )

    expect(screen.getByRole('link', { name: 'Dashboard' })).toHaveAttribute('href', '/')
    expect(screen.queryByRole('button')).not.toBeInTheDocument()
  })

  it("counts the parent's own page and never its children", () => {
    /*
     * A component cannot know whether the parent's twelve and the child's twelve
     * are the same twelve, and a number that appears when a list is shut and
     * vanishes when it opens reads as the count having MOVED.
     */
    render(
      <Gate count={3}>
        <SubNavItem label="Shift handovers" href="/gate/handovers" count={8} />
      </Gate>,
    )

    expect(screen.getByText('3')).toBeInTheDocument()
    expect(screen.getByText('8')).toBeInTheDocument()
    // Not 11 anywhere.
    expect(screen.queryByText('11')).not.toBeInTheDocument()
  })

  describe('collapsed', () => {
    it("opens a pop-out that carries the parent's own page first", async () => {
      /*
       * There is no label left to press, so the row stops being a link and
       * becomes ONE control. The parent's own page is the panel's first row,
       * named in full, because otherwise it is unreachable.
       */
      render(<Gate collapsed />)

      const icon = screen.getByRole('button', { name: 'Gate' })

      expect(icon).toHaveAttribute('aria-expanded', 'false')
      expect(icon).toHaveAttribute('aria-haspopup')
      // Nothing is reachable until it is pressed.
      expect(screen.queryByRole('link', { name: 'Roll calls' })).not.toBeInTheDocument()

      await userEvent.click(icon)

      const panel = screen.getByRole('dialog', { name: 'Gate' })
      const rows = within(panel).getAllByRole('link')

      expect(rows[0]).toHaveTextContent('Gate dashboard')
      expect(rows[0]).toHaveAttribute('href', '/gate')
      expect(rows.map((row) => row.textContent)).toEqual([
        'Gate dashboard',
        'Shift handovers',
        'Guards on gates',
        'Roll calls',
        'Person in',
      ])

      // Focus enters the panel's first row, as dropdown.md has it.
      expect(rows[0]).toHaveFocus()
    })

    it('closes on Escape and gives the focus back to the icon', async () => {
      render(<Gate collapsed />)

      const icon = screen.getByRole('button', { name: 'Gate' })

      await userEvent.click(icon)
      expect(screen.getByRole('dialog', { name: 'Gate' })).toBeInTheDocument()

      await userEvent.keyboard('{Escape}')

      expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
      // Taken verbatim from dropdown.md rather than redesigned: focus returns to
      // the control that opened the panel, or it lands on the document body and
      // the next Tab starts from the top of the page.
      expect(icon).toHaveFocus()
    })

    it('marks a collapsed icon whose list holds the current page, without touching the pip', () => {
      /*
       * The one deliberate exception to "do not mark the parent". It is allowed
       * here for the single reason that the alternative is impossible: the list
       * is not on screen, so there is nowhere to put the card. Bottom-right, so
       * it never collides with a count pip on the top-right.
       */
      const { container } = render(<Gate collapsed count={4} />)

      const inverse = [...container.querySelectorAll('span')].filter((node) =>
        node.className.includes('bg-surface-inverse'),
      )

      const pip = inverse.find((node) => node.textContent === '4')
      const dot = inverse.find((node) => node.textContent === '')

      expect(pip?.className).toContain('-top-1')
      expect(dot?.className).toContain('-bottom-1')
      expect(dot?.className).not.toContain('-top-1')
    })

    it('does not draw the dot when the parent itself is where you are', () => {
      /*
       * The icon draws the card, and the dot would be saying it twice.
       */
      const { container } = render(<Gate collapsed active />)

      const empty = [...container.querySelectorAll('span')].filter(
        (node) => node.className.includes('bg-surface-inverse') && node.textContent === '',
      )

      expect(empty).toHaveLength(0)
    })

    it('does not draw the dot when no child is current', () => {
      const { container } = render(
        <Gate collapsed>
          <SubNavItem label="Shift handovers" href="/gate/handovers" />
        </Gate>,
      )

      const empty = [...container.querySelectorAll('span')].filter(
        (node) => node.className.includes('bg-surface-inverse') && node.textContent === '',
      )

      expect(empty).toHaveLength(0)
    })

    it('shows no tooltip on the icon', () => {
      // The panel's overline already names the section, and a tip and a panel
      // opening from the same press land on top of each other.
      render(<Gate collapsed />)

      expect(screen.getByRole('button', { name: 'Gate' })).not.toHaveAttribute('title')
    })

    it('does not open on hover unless the shell asks for it', async () => {
      /*
       * Off by default: a rail that already peeks on hover would fight it, and a
       * touch screen has no hover at all — so a component that depended on it
       * would have no way to open on the one device that most needs the pop-out.
       */
      render(<Gate collapsed />)

      await userEvent.hover(screen.getByRole('button', { name: 'Gate' }))

      expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
    })
  })
})
