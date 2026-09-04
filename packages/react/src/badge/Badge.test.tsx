import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import { Badge } from './Badge'

describe('Badge', () => {
  it('is a plain span with no role — it is not a control', () => {
    render(<Badge>Active</Badge>)
    const badge = screen.getByText('Active')

    expect(badge.tagName).toBe('SPAN')
    expect(badge).not.toHaveAttribute('role')
  })

  it('defaults to tint, because a badge annotates rather than acts', () => {
    render(<Badge tone="success">Passed</Badge>)
    expect(screen.getByText('Passed').className).toContain('bg-success-tint')
  })

  it('degrades an unknown tone to neutral instead of leaving it unpainted', () => {
    // @ts-expect-error — deliberately passing a tone outside the union.
    render(<Badge tone="chartreuse">Odd</Badge>)
    expect(screen.getByText('Odd').className).toContain('bg-neutral-tint')
  })

  it('hides the dot from assistive technology — the label already says the state', () => {
    const { container } = render(<Badge tone="success" dot>Running</Badge>)
    const dot = container.querySelector('[aria-hidden="true"]')

    expect(dot).toBeInTheDocument()
    // The BASE colour, not the tint: a tint-on-tint dot is invisible.
    expect(dot?.className).toContain('bg-success')
    expect(dot?.className).not.toContain('bg-success-tint')
  })
})
