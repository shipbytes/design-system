import { describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Button } from './Button'

describe('Button', () => {
  it('defaults to type=button, not submit', async () => {
    // The default is submit, which is how a "Cancel" button ends up saving the
    // form it sits in. specs/button.md.
    const onSubmit = vi.fn((e: React.FormEvent) => e.preventDefault())
    render(
      <form onSubmit={onSubmit}>
        <Button>Cancel</Button>
      </form>,
    )

    expect(screen.getByRole('button')).toHaveAttribute('type', 'button')
    await userEvent.click(screen.getByRole('button'))
    expect(onSubmit).not.toHaveBeenCalled()
  })

  it('still submits when asked to', async () => {
    const onSubmit = vi.fn((e: React.FormEvent) => e.preventDefault())
    render(
      <form onSubmit={onSubmit}>
        <Button type="submit">Save</Button>
      </form>,
    )

    await userEvent.click(screen.getByRole('button'))
    expect(onSubmit).toHaveBeenCalledOnce()
  })

  it('blocks a second click while loading, and says it is busy', async () => {
    const onClick = vi.fn()
    render(<Button loading onClick={onClick}>Save</Button>)

    const button = screen.getByRole('button')
    expect(button).toBeDisabled()
    expect(button).toHaveAttribute('aria-busy', 'true')

    await userEvent.click(button)
    expect(onClick).not.toHaveBeenCalled()
  })

  it('keeps its label while loading rather than swapping it for "Loading…"', () => {
    render(<Button loading>Save changes</Button>)
    expect(screen.getByRole('button')).toHaveTextContent('Save changes')
  })

  it('does not fade while loading — faded reads as refused, not working', () => {
    render(<Button loading>Save</Button>)
    const cls = screen.getByRole('button').className
    expect(cls).toContain('cursor-wait')
    expect(cls).not.toContain('disabled:opacity-50')
  })

  it('renders an <a> when it navigates', () => {
    render(<Button href="/reports">Reports</Button>)
    expect(screen.getByRole('link', { name: 'Reports' })).toHaveAttribute('href', '/reports')
  })

  it('keeps the border on every variant so swapping one does not resize the box', () => {
    const { rerender } = render(<Button variant="primary">A</Button>)
    expect(screen.getByRole('button').className).toContain('border')

    rerender(<Button variant="secondary">A</Button>)
    expect(screen.getByRole('button').className).toContain('border')
  })

  it('lets a caller override a recipe class instead of stacking two', () => {
    render(<Button className="rounded-full">A</Button>)
    const cls = screen.getByRole('button').className
    expect(cls).toContain('rounded-full')
    expect(cls).not.toContain('rounded-control')
  })
})
