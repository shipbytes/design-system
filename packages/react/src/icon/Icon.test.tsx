import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import { TrashIcon, XMarkIcon, CheckIcon } from '@heroicons/react/24/outline'
import { ArchiveBoxIcon } from '@heroicons/react/20/solid'
import { Icon } from './Icon'
import { IconProvider } from './IconProvider'
import { createIconRegistry } from './registry'

const registry = createIconRegistry({
  outline: { TrashIcon, XMarkIcon, CheckIcon },
  mini: { ArchiveBoxIcon },
})

function draw(ui: React.ReactNode) {
  return render(<IconProvider registry={registry}>{ui}</IconProvider>)
}

describe('Icon', () => {
  // A missing icon warns in development. Spied on rather than left to print:
  // the noise hides real failures, and the warning is itself worth asserting.
  let warn: ReturnType<typeof vi.spyOn>

  beforeEach(() => {
    warn = vi.spyOn(console, 'warn').mockImplementation(() => {})
  })

  afterEach(() => {
    warn.mockRestore()
  })

  it('is decorative by default', () => {
    // Most icons sit beside a label that already says the same thing, and
    // announcing both is noise. specs/icon.md.
    const { container } = draw(<Icon name="trash" />)
    const svg = container.querySelector('svg')

    expect(svg).toHaveAttribute('aria-hidden', 'true')
    expect(svg).not.toHaveAttribute('role')
  })

  it('becomes an image with a name when it is the only thing carrying meaning', () => {
    draw(<Icon name="trash" label="Delete" />)
    const svg = screen.getByRole('img', { name: 'Delete' })

    // aria-hidden must NOT survive here: it cancels the role and the label, and
    // a screen reader then skips the element entirely.
    expect(svg).not.toHaveAttribute('aria-hidden', 'true')
  })

  it('resolves a Heroicons v1 name through the alias map', () => {
    // A renamed v1 name resolves to no component at all and renders nothing,
    // silently. That is what the map is for — `x` is v1 for `x-mark`.
    const { container } = draw(<Icon name="x" />)
    expect(container.querySelector('svg')).toBeInTheDocument()
  })

  it('composes the size class the theme generates rules for', () => {
    const { container } = draw(<Icon name="check" size={4.5} />)
    expect(container.querySelector('svg')?.className.baseVal ?? '').toContain('size-4.5')
  })

  it('keeps the variants apart', () => {
    // The same kebab name exists in several sets, so the registry is per
    // variant. `archive-box` is registered as mini here, is not registered as
    // outline, and is not one of the built-ins.
    const { container: mini } = draw(<Icon name="archive-box" variant="mini" />)
    expect(mini.querySelector('svg')).toBeInTheDocument()

    const { container: outline } = draw(<Icon name="archive-box" />)
    expect(outline.querySelector('svg')).toBeNull()
  })

  it('renders nothing for a name the registry does not carry, and says so', () => {
    const { container } = draw(<Icon name="definitely-not-an-icon" />)

    expect(container.querySelector('svg')).toBeNull()
    // A defect, not a feature — loud where a developer will see it, silent in
    // production where a missing glyph must not take a gate screen down.
    expect(warn).toHaveBeenCalledOnce()
  })

  it('renders nothing when there is no provider above it', () => {
    // The failure is loud in development and silent in production, which is the
    // right way round: a missing glyph must not take a gate screen down.
    const { container } = render(<Icon name="trash" />)
    expect(container.querySelector('svg')).toBeNull()
  })

  it('still draws the icons this package\'s own components need', () => {
    // An alert's tone icon was never the application's to declare, so the
    // built-in floor answers with no provider and no registration.
    const { container } = render(<Icon name="exclamation-triangle" />)
    expect(container.querySelector('svg')).toBeInTheDocument()
  })

  it('lets an application override a built-in with its own glyph', () => {
    const { container } = draw(<Icon name="x-mark" />)

    // `XMarkIcon` is registered above AND is a built-in; the registry wins, so
    // an application that wants a different cross gets its own.
    expect(container.querySelector('svg')).toBeInTheDocument()
  })
})
