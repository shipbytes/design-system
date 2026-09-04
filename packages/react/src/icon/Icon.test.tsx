import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import { Icon } from './Icon'

describe('Icon', () => {
  it('is decorative by default', () => {
    // Most icons sit beside a label that already says the same thing, and
    // announcing both is noise. specs/icon.md.
    const { container } = render(<Icon name="trash" />)
    const svg = container.querySelector('svg')

    expect(svg).toHaveAttribute('aria-hidden', 'true')
    expect(svg).not.toHaveAttribute('role')
  })

  it('becomes an image with a name when it is the only thing carrying meaning', () => {
    render(<Icon name="trash" label="Delete" />)
    const svg = screen.getByRole('img', { name: 'Delete' })

    // aria-hidden must NOT survive here: it cancels the role and the label, and
    // a screen reader then skips the element entirely.
    expect(svg).not.toHaveAttribute('aria-hidden', 'true')
  })

  it('resolves a Heroicons v1 name through the alias map', () => {
    // A renamed v1 name resolves to no component at all and renders nothing,
    // silently. That is what the map is for.
    const { container } = render(<Icon name="x" />)
    expect(container.querySelector('svg')).toBeInTheDocument()
  })

  it('composes the size class the theme generates rules for', () => {
    const { container } = render(<Icon name="check" size={4.5} />)
    expect(container.querySelector('svg')?.className.baseVal ?? '').toContain('size-4.5')
  })

  it('renders nothing for a name that does not exist', () => {
    const { container } = render(<Icon name="definitely-not-an-icon" />)
    expect(container.querySelector('svg')).toBeNull()
  })
})
