import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import { Panel, PanelBleed, PanelRow } from './Panel'

describe('Panel', () => {
  it('renders the title as a heading', () => {
    render(<Panel title="Reason codes">body</Panel>)
    expect(screen.getByRole('heading', { name: 'Reason codes' })).toBeInTheDocument()
  })

  it('has no header at all when there is nothing to put in one', () => {
    render(<Panel>just content</Panel>)
    expect(screen.queryByRole('heading')).not.toBeInTheDocument()
  })

  it('switches to the feature look when given an icon', () => {
    const { container, rerender } = render(<Panel title="Quiet">x</Panel>)
    expect(container.firstElementChild?.className).toContain('rounded-control')

    rerender(<Panel title="Loud" icon="chart-bar">x</Panel>)
    expect(container.firstElementChild?.className).toContain('rounded-panel')
    expect(container.firstElementChild?.className).toContain('border-border-strong')
  })

  /*
   * The regression these three exist for: the body used to default to a mode
   * with no padding at all, and 52 panels in the first consuming application
   * rendered their content flush against the border because nobody passed the
   * other mode. There is no mode now, so there is nothing to get wrong — but
   * the inset itself must stay asserted, because `PanelBleed` negates it by a
   * matching number and the two would drift apart silently.
   */
  it('insets its body, with no mode to forget', () => {
    const { container } = render(<Panel title="Reason codes">body</Panel>)
    const body = container.firstElementChild?.lastElementChild

    expect(body?.className).toContain('px-5')
    expect(body?.className).toContain('sm:px-6')
  })

  it('puts the header on the same inset as the body, with and without an icon', () => {
    // Four different insets used to be in play, so a feature panel's heading and
    // its rows sat 8px out of step and no page could line anything up.
    for (const icon of [undefined, 'chart-bar']) {
      const { container, unmount } = render(<Panel title="Reason codes" icon={icon}>body</Panel>)
      const [header, body] = [...(container.firstElementChild?.children ?? [])]

      expect(header?.className).toContain('px-5')
      expect(header?.className).toContain('sm:px-6')
      expect(body?.className).toContain('px-5')
      unmount()
    }
  })

  it('paints its own foreground as well as its own background', () => {
    // A component that paints its background and inherits its text colour is
    // invisible on any surface the host did not anticipate.
    const { container } = render(<Panel>x</Panel>)
    const cls = container.firstElementChild?.className ?? ''

    /*
     * The exact class, not any class beginning with it. `bg-surface-subtle`,
     * `-sunken` and `-inverse` all contain the substring `bg-surface`, so a
     * `toContain` here would pass on a component that had quietly moved to one
     * of them. Nothing is wrong behind this today; it is tightened because that
     * is the shape of assertion UI-D1 hid inside for as long as `NavItem`
     * existed, and it is four characters to close.
     */
    expect(cls).toMatch(/(^|\s)bg-surface(\s|$)/)
    expect(cls).toContain('text-fg-body')
  })
})

describe('PanelBleed', () => {
  it('negates exactly the inset the body applies', () => {
    const { container } = render(<PanelBleed>edge to edge</PanelBleed>)
    const cls = container.firstElementChild?.className ?? ''

    // The pair below must stay the mirror of `PANEL_INSET`. A divided list
    // breaks out through this, and a mismatch shows as a list a few pixels
    // wider or narrower than its panel — which reads as a rendering bug.
    expect(cls).toContain('-mx-5')
    expect(cls).toContain('sm:-mx-6')
  })

  it('keeps whatever else the caller puts on it', () => {
    const { container } = render(<PanelBleed className="mt-4">x</PanelBleed>)
    expect(container.firstElementChild?.className).toContain('mt-4')
  })
})

describe('PanelRow', () => {
  it('carries the panel inset itself, so its text lines up with the heading', () => {
    const { container } = render(<PanelRow>a row</PanelRow>)
    const cls = container.firstElementChild?.className ?? ''

    expect(cls).toContain('px-5')
    expect(cls).toContain('sm:px-6')
  })

  it('is a div with no hover state when it does not go anywhere', () => {
    // A hover affordance on something unclickable is a lie.
    const { container } = render(<PanelRow>Plain</PanelRow>)
    const row = container.firstElementChild

    expect(row?.tagName).toBe('DIV')
    expect(row?.className).not.toContain('hover:bg-surface-subtle')
  })

  it('becomes a link with a hover state when it does', () => {
    render(<PanelRow href="/masters/uoms">UOMs</PanelRow>)
    const row = screen.getByRole('link', { name: 'UOMs' })

    expect(row).toHaveAttribute('href', '/masters/uoms')
    expect(row.className).toContain('hover:bg-surface-subtle')
  })
})
