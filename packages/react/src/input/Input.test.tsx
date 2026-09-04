import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import { Input } from './Input'

describe('Input', () => {
  it('ties a real <label for> to the control', () => {
    // A placeholder is not a label: it vanishes on input and fails contrast.
    render(<Input label="Login ID" name="login_id" />)
    expect(screen.getByLabelText('Login ID')).toBeInstanceOf(HTMLInputElement)
  })

  it('generates a unique id per instance so two fields never collide', () => {
    render(
      <>
        <Input label="First" name="a" />
        <Input label="Second" name="b" />
      </>,
    )

    expect(screen.getByLabelText('First').id).not.toBe(screen.getByLabelText('Second').id)
  })

  it('wires an error to the control with aria-describedby and aria-invalid', () => {
    render(<Input label="Code" error="Code must be uppercase." />)
    const field = screen.getByLabelText('Code')

    expect(field).toHaveAttribute('aria-invalid', 'true')
    expect(field).toHaveAccessibleDescription('Code must be uppercase.')
  })

  it('replaces help with the error rather than stacking both', () => {
    // Two lines of guidance under one field is one line too many.
    render(<Input label="Code" help="Uppercase letters only." error="Code is required." />)

    expect(screen.getByText('Code is required.')).toBeInTheDocument()
    expect(screen.queryByText('Uppercase letters only.')).not.toBeInTheDocument()
  })

  it('renders a select with its options and a caret', () => {
    render(
      <Input as="select" label="Scope" defaultValue="S">
        <option value="S">Shared</option>
        <option value="P">Plant-specific</option>
      </Input>,
    )

    const select = screen.getByLabelText('Scope')
    expect(select).toBeInstanceOf(HTMLSelectElement)
    expect(screen.getByRole('option', { name: 'Plant-specific' })).toBeInTheDocument()
  })

  it('renders a textarea with the requested rows', () => {
    render(<Input as="textarea" label="Note" rows={5} />)
    expect(screen.getByLabelText('Note')).toHaveAttribute('rows', '5')
  })
})
