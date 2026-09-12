import { render, screen } from '@testing-library/react'
import { Avatar, initialsOf } from './Avatar'

describe('initials', () => {
  it('takes the first and last word, not the first two', () => {
    // "Ada King Lovelace" is AL: the middle name is the part nobody uses.
    expect(initialsOf('Ada King Lovelace')).toBe('AL')
    expect(initialsOf('Lata Parmar')).toBe('LP')
    expect(initialsOf('Prince')).toBe('P')
  })

  it('survives a multi-byte first letter', () => {
    // A name is the likeliest place in an interface to meet one, and slicing a
    // surrogate pair in half renders a replacement glyph.
    expect(initialsOf('Émile Zola')).toBe('ÉZ')
    expect(initialsOf('Ýlmaz Öztürk')).toBe('ÝÖ')
  })

  it('is empty for an empty name rather than throwing', () => {
    expect(initialsOf('   ')).toBe('')
  })
})

describe('Avatar', () => {
  it('draws initials when there is no image', () => {
    render(<Avatar name="Sangeeta Jani" />)

    expect(screen.getByText('SJ')).toBeInTheDocument()
  })

  it('gives the image the name as its alt', () => {
    render(<Avatar name="Rakesh Kadam" src="/r.jpg" />)

    expect(screen.getByAltText('Rakesh Kadam')).toBeInTheDocument()
  })

  it('announces nothing when the name is written beside it', () => {
    // A row announcing "Ada Lovelace, Ada Lovelace" is worse than one that
    // announces it once.
    const { container } = render(<Avatar name="Ada Lovelace" src="/a.jpg" decorative />)

    expect(container.firstElementChild).toHaveAttribute('aria-hidden', 'true')
    // Queried by tag: an `alt=""` image has no `img` role, which is the point.
    expect(container.querySelector('img')).toHaveAttribute('alt', '')
    expect(screen.queryByRole('img')).not.toBeInTheDocument()
  })

  it('draws a mark rather than an empty circle with nothing to show', () => {
    // A blank avatar is a bug the reader cannot report.
    const { container } = render(<Avatar />)

    expect(container.querySelector('svg')).toBeInTheDocument()
    expect(container.firstElementChild).toHaveAttribute('aria-hidden', 'true')
  })

  it('is round for a person and square for a thing', () => {
    const { container: person } = render(<Avatar name="A B" />)
    const { container: thing } = render(<Avatar name="Jalaqua" square />)

    expect(person.firstElementChild).toHaveClass('rounded-full')
    expect(thing.firstElementChild).toHaveClass('rounded-control')
  })
})
