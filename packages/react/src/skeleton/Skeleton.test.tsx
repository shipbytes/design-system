import { describe, expect, it } from 'vitest'
import { render } from '@testing-library/react'
import { Skeleton } from './Skeleton'

describe('Skeleton', () => {
  it('is hidden from assistive technology', () => {
    // It is a picture of content that does not exist yet. Left visible it reads
    // as a run of empty elements, which is worse than silence — the HOST
    // carries aria-busy. specs/skeleton.md.
    const { container } = render(<Skeleton />)
    expect(container.firstElementChild).toHaveAttribute('aria-hidden', 'true')
  })

  it('ends the last bar short, the way a paragraph ends', () => {
    // A block of equal-length bars reads as a table, not as text.
    const { container } = render(<Skeleton lines={3} />)
    const bars = container.querySelectorAll('.h-3')

    expect(bars).toHaveLength(3)
    expect(bars[2]?.className).toContain('w-3/5')
    expect(bars[0]?.className).toContain('w-full')
  })

  it('does not shorten a single bar — there is no paragraph to end', () => {
    const { container } = render(<Skeleton lines={1} />)
    expect(container.querySelector('.h-3')?.className).toContain('w-full')
  })

  it('respects a reduced-motion preference', () => {
    const { container } = render(<Skeleton variant="block" />)
    expect(container.querySelector('.rounded-control')?.className).toContain('motion-safe:animate-pulse')
  })

  it('clamps a nonsense line count instead of rendering nothing', () => {
    const { container } = render(<Skeleton lines={0} />)
    expect(container.querySelectorAll('.h-3')).toHaveLength(1)
  })
})

describe('the table variant', () => {
  it('draws a header and the rows asked for', () => {
    // A grid, because a grid is what is coming: a list that loaded behind
    // paragraph bars moved everything on the screen when the rows arrived.
    const { container } = render(<Skeleton variant="table" rows={4} columns={3} />)

    // One header row plus four body rows.
    expect(container.querySelectorAll('.border-b').length).toBeGreaterThanOrEqual(4)
    expect(container.firstElementChild).toHaveAttribute('aria-hidden', 'true')
  })

  it('keeps a rhythm at any column count', () => {
    const { container } = render(<Skeleton variant="table" rows={1} columns={9} />)

    // Widths repeat rather than running out, so nine columns still alternate.
    expect(container.querySelectorAll('.w-24').length).toBeGreaterThan(1)
  })
})
