import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import { StatTile } from './StatTile'

/**
 * jsdom has no `matchMedia`, so every test here would take the reduced-motion
 * branch by accident. Declaring it makes the choice explicit — and the two
 * tests that care set it deliberately.
 */
function prefersReducedMotion(reduced: boolean): void {
  Object.defineProperty(window, 'matchMedia', {
    writable: true,
    configurable: true,
    value: (query: string) => ({
      matches: reduced,
      media: query,
      onchange: null,
      addListener: () => {},
      removeListener: () => {},
      addEventListener: () => {},
      removeEventListener: () => {},
      dispatchEvent: () => false,
    }),
  })
}

describe('StatTile', () => {
  it('renders the final value, so it is right before any script runs', () => {
    prefersReducedMotion(true)

    render(<StatTile label="Pending approvals" value={12} />)

    expect(screen.getByText('12')).toBeInTheDocument()
  })

  it('uses tabular numerals on the value', () => {
    /*
     * Two reasons, and the second is the one people forget: the digits line up
     * across the tiles in a row, AND the count-up does not change width as it
     * climbs. Proportional digits make the number jitter while it counts.
     */
    prefersReducedMotion(true)

    render(<StatTile label="Open lines" value={1204} />)

    expect(screen.getByText('1204').className).toContain('tabular-nums')
  })

  it('leaves a formatted value exactly as it was given', () => {
    prefersReducedMotion(false)

    render(<StatTile label="Fill rate" value="98.4%" />)

    expect(screen.getByText('98.4%')).toBeInTheDocument()
  })

  it('shows no chip at all when there is no comparison', () => {
    /*
     * A missing comparison is not a zero. "No change since last week" and
     * "nothing to compare against yet" are different statements, and a 0% chip
     * asserts the first.
     */
    prefersReducedMotion(true)

    const { container } = render(
      <StatTile label="Open lines" value={7} caption="No previous period" />,
    )

    expect(screen.getByText('No previous period')).toBeInTheDocument()
    expect(container.querySelector('.bg-success-tint')).toBeNull()
    expect(container.querySelector('.bg-danger-tint')).toBeNull()
    expect(screen.queryByText(/%/)).toBeNull()
  })

  it('draws a zero delta as a chip, because zero is a comparison', () => {
    prefersReducedMotion(true)

    const { container } = render(<StatTile label="Open lines" value={7} delta={0} />)

    expect(container.querySelector('.bg-success-tint')).not.toBeNull()
    expect(screen.getByText('+0%')).toBeInTheDocument()
  })

  it('tints a fall danger and writes it with a real minus sign', () => {
    prefersReducedMotion(true)

    const { container } = render(<StatTile label="Open lines" value={7} delta={-18} />)

    expect(container.querySelector('.bg-danger-tint')).not.toBeNull()
    // A minus sign, not a hyphen: the hyphen is narrower than a digit and
    // breaks the tabular alignment it sits in.
    expect(screen.getByText('−18%')).toBeInTheDocument()
  })

  it('is a link with a hover state when it drills down', () => {
    prefersReducedMotion(true)

    render(<StatTile label="Pending approvals" value={3} href="/approvals" />)

    const tile = screen.getByRole('link')

    expect(tile).toHaveAttribute('href', '/approvals')
    expect(tile.className).toContain('hover:shadow-raised')
  })

  it('has no hover state when it goes nowhere', () => {
    // A hover affordance on something unclickable is a lie.
    prefersReducedMotion(true)

    const { container } = render(<StatTile label="Open lines" value={7} />)

    expect(screen.queryByRole('link')).toBeNull()
    expect(container.firstElementChild?.className).not.toContain('hover:')
  })

  it('skips the count-up for a reader who has asked for reduced motion', () => {
    /*
     * The hand-written original animated regardless. This is the class of bug
     * that survives review indefinitely because it only affects people who are
     * not in the room — everything looks right to everybody who checks it.
     */
    prefersReducedMotion(true)

    render(<StatTile label="Open lines" value={250} />)

    // Final on the first paint, with no frames in between.
    expect(screen.getByText('250')).toBeInTheDocument()
  })

  it('starts the count-up from zero when motion is welcome', () => {
    prefersReducedMotion(false)

    render(<StatTile label="Open lines" value={250} />)

    expect(screen.getByText('0')).toBeInTheDocument()
  })
})
