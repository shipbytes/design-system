import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Radio } from './Radio'
import { RadioGroup } from './RadioGroup'

describe('Radio', () => {
  it('is the native input, styled — not a decorated span beside a hidden one', () => {
    // A visually-hidden radio loses high-contrast rendering, the browser's own
    // focus behaviour and forced-colors support, and every one of those
    // failures is invisible in a normal browser.
    render(<Radio label="Yes" />)

    const dot = screen.getByRole('radio')

    expect(dot.tagName).toBe('INPUT')
    expect(dot.className).toContain('appearance-none')
    expect(dot.className).toContain('forced-colors:appearance-auto')
  })

  it('is round, which is the only signal that says "one of these"', () => {
    // The shape is the difference between a radio and a checkbox before the
    // reader clicks anything. Round and ticked is the worst of both.
    render(<Radio label="Yes" />)

    expect(screen.getByRole('radio').className).toContain('rounded-full')
  })

  it('ties the label to the input, so its whole width is a click target', async () => {
    render(<Radio label="Emergency purchase" />)

    await userEvent.click(screen.getByText('Emergency purchase'))

    expect(screen.getByRole('radio')).toBeChecked()
  })

  it('describes an option by its own footnote', () => {
    render(<Radio label="Yearly" help="Two months free." />)

    const dot = screen.getByRole('radio')

    expect(dot.getAttribute('aria-describedby')).toBeTruthy()
    expect(screen.getByText('Two months free.')).toHaveAttribute(
      'id',
      dot.getAttribute('aria-describedby'),
    )
  })
})

describe('RadioGroup', () => {
  it('is a fieldset with a legend, which is what names the question', () => {
    /*
     * A radio announces its own label and nothing else, so "Monthly" is read
     * without ever saying what is being chosen. The legend is what a screen
     * reader repeats as the group is entered.
     */
    render(
      <RadioGroup legend="Is this an emergency purchase?">
        <Radio label="Yes" value="1" />
        <Radio label="No" value="0" />
      </RadioGroup>,
    )

    expect(screen.getByRole('group', { name: 'Is this an emergency purchase?' })).toBeInTheDocument()
  })

  it('gives every option one name, which is what makes the arrows work', () => {
    /*
     * The shared name is what makes them a group to the BROWSER, and the
     * browser's arrow-key navigation comes free with it. A group whose options
     * carried different names would look right and behave as several groups of
     * one — checkable all at once.
     */
    render(
      <RadioGroup legend="Purpose" name="purpose">
        <Radio label="Breakdown" value="BREAKDOWN" />
        <Radio label="Preventive" value="PREVENTIVE" />
      </RadioGroup>,
    )

    const names = screen.getAllByRole('radio').map((r) => (r as HTMLInputElement).name)

    expect(new Set(names).size).toBe(1)
    expect(names[0]).toBe('purpose')
  })

  it('generates a shared name when the caller does not give one', () => {
    render(
      <RadioGroup legend="Purpose">
        <Radio label="A" value="a" />
        <Radio label="B" value="b" />
      </RadioGroup>,
    )

    const names = screen.getAllByRole('radio').map((r) => (r as HTMLInputElement).name)

    expect(names[0]).not.toBe('')
    expect(new Set(names).size).toBe(1)
  })

  it('leaves an option that names itself alone', () => {
    render(
      <RadioGroup legend="Purpose" name="purpose">
        <Radio label="A" value="a" />
        <Radio label="Something else entirely" value="x" name="other" />
      </RadioGroup>,
    )

    const names = screen.getAllByRole('radio').map((r) => (r as HTMLInputElement).name)

    expect(names).toEqual(['purpose', 'other'])
  })

  it('puts the error on the QUESTION and not on the first option', () => {
    /*
     * "Choose a purpose" is about the question. Attaching it to the first radio
     * would say the first radio is invalid, which it is not.
     */
    render(
      <RadioGroup legend="Purpose" error="Choose a purpose.">
        <Radio label="Breakdown" value="BREAKDOWN" />
      </RadioGroup>,
    )

    const group = screen.getByRole('group')

    expect(group).toHaveAttribute('aria-invalid', 'true')
    expect(screen.getByText('Choose a purpose.')).toHaveAttribute(
      'id',
      group.getAttribute('aria-describedby'),
    )
    expect(screen.getByRole('radio')).not.toHaveAttribute('aria-invalid')
  })

  it('replaces the group help with the error rather than stacking them', () => {
    render(
      <RadioGroup legend="Purpose" help="Pick the closest." error="Choose a purpose.">
        <Radio label="Breakdown" value="BREAKDOWN" />
      </RadioGroup>,
    )

    expect(screen.getByText('Choose a purpose.')).toBeInTheDocument()
    expect(screen.queryByText('Pick the closest.')).toBeNull()
  })

  it('disables every option from the fieldset, without touching each one', () => {
    render(
      <RadioGroup legend="Purpose" disabled>
        <Radio label="A" value="a" />
        <Radio label="B" value="b" />
      </RadioGroup>,
    )

    screen.getAllByRole('radio').forEach((r) => expect(r).toBeDisabled())
  })
})
