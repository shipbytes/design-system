import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import { EmptyState } from './EmptyState'

describe('EmptyState', () => {
  it('is not a heading — it sits inside a page that already has one', () => {
    render(<EmptyState title="No reason codes yet" />)

    expect(screen.queryByRole('heading')).toBeNull()
    expect(screen.getByText('No reason codes yet').className).toContain('text-section')
  })

  it('defaults to neutral, because most emptiness is simply the beginning', () => {
    const { container } = render(<EmptyState title="Nothing here" icon="inbox" />)

    // A coloured tile on a brand-new account tells the reader something has
    // gone wrong when nothing has.
    expect(container.innerHTML).toContain('bg-neutral-tint')
  })

  it('wraps the description at a readable measure', () => {
    render(<EmptyState title="No matches" description="Try a shorter search term." />)

    expect(screen.getByText('Try a shorter search term.').className).toContain('max-w-sm')
  })

  it('renders the action where it can be seen', () => {
    render(<EmptyState title="No masters" action={<button type="button">New</button>} />)

    expect(screen.getByRole('button', { name: 'New' })).toBeInTheDocument()
  })

  it('drops its frame when it is already inside a panel', () => {
    const { container } = render(<EmptyState title="Empty" bare />)

    expect(container.firstElementChild?.className).not.toContain('border-dashed')
  })
})
