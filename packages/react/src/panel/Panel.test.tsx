import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import { Panel, PanelRow } from './Panel'

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

  it('paints its own foreground as well as its own background', () => {
    // A component that paints its background and inherits its text colour is
    // invisible on any surface the host did not anticipate.
    const { container } = render(<Panel>x</Panel>)
    const cls = container.firstElementChild?.className ?? ''

    expect(cls).toContain('bg-surface')
    expect(cls).toContain('text-fg-body')
  })
})

describe('PanelRow', () => {
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
