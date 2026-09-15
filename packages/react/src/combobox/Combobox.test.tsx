import { describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Combobox, type ComboboxOption } from './Combobox'

const options: ComboboxOption[] = [
  { value: '1', label: 'Kilogram', meta: 'KG' },
  { value: '2', label: 'Metric tonne', meta: 'MT' },
  { value: '3', label: 'Litre', meta: 'LTR' },
]

describe('Combobox', () => {
  it('filters the list it is given, in the browser', async () => {
    render(<Combobox options={options} value={null} onChange={() => {}} label="Unit" />)

    await userEvent.type(screen.getByRole('combobox'), 'lit')

    expect(screen.getAllByRole('option').map((o) => o.textContent)).toEqual(['LitreLTR'])
  })

  it('says so when the filter matches nothing', async () => {
    render(<Combobox options={options} value={null} onChange={() => {}} />)

    await userEvent.type(screen.getByRole('combobox'), 'zzz')

    // An empty list with no message reads as a broken control.
    expect(screen.getByText('No matches')).toBeInTheDocument()
  })

  it('leaves the options alone when the consumer filters server-side', async () => {
    const onQueryChange = vi.fn()

    render(
      <Combobox
        options={options}
        value={null}
        onChange={() => {}}
        filter={false}
        onQueryChange={onQueryChange}
      />,
    )

    await userEvent.type(screen.getByRole('combobox'), 'zzz')

    // The component fetched nothing and hid nothing — it reported the typing
    // and rendered exactly what it was given.
    expect(onQueryChange).toHaveBeenLastCalledWith('zzz')
    expect(screen.getAllByRole('option')).toHaveLength(3)
  })

  it('reports a single choice as a value and closes', async () => {
    const onChange = vi.fn()
    render(<Combobox options={options} value={null} onChange={onChange} />)

    await userEvent.click(screen.getByRole('combobox'))
    await userEvent.click(screen.getByRole('option', { name: /Metric tonne/ }))

    expect(onChange).toHaveBeenCalledWith('2')
    expect(screen.queryByRole('listbox')).toBeNull()
  })

  it('reports several choices as an array and stays open', async () => {
    const onChange = vi.fn()
    const { rerender } = render(<Combobox options={options} value={[]} onChange={onChange} multiple />)

    await userEvent.click(screen.getByRole('combobox'))
    await userEvent.click(screen.getByRole('option', { name: /Kilogram/ }))

    expect(onChange).toHaveBeenCalledWith(['1'])

    rerender(<Combobox options={options} value={['1']} onChange={onChange} multiple />)

    expect(screen.getByRole('listbox')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Remove Kilogram' })).toBeInTheDocument()
  })

  it('removes the last chip on backspace with an empty query', async () => {
    const onChange = vi.fn()
    render(<Combobox options={options} value={['1', '2']} onChange={onChange} multiple />)

    await userEvent.click(screen.getByRole('combobox'))
    await userEvent.keyboard('{Backspace}')

    // Without it the only way to undo a selection is to aim at a 12px ✕.
    expect(onChange).toHaveBeenCalledWith(['1'])
  })

  /*
   * The defect specs/combobox.md was silent about, in both implementations: a
   * settled single-select rendered its answer as the PLACEHOLDER, in
   * `text-fg-muted`, and so drew itself as an empty focused text box. These say
   * the answer is the field's text, that the caret does not lie about being
   * editable, and that typing still works from there.
   */
  it('shows the chosen value as the field text, not as a placeholder', () => {
    render(<Combobox options={options} value="3" onChange={() => {}} placeholder="Search…" />)

    const field = screen.getByRole('combobox')

    expect(field).toHaveValue('Litre')
    expect(field).toHaveAttribute('placeholder', 'Search…')
    // A settled field is not waiting to be typed into, and a caret says it is.
    expect(field.className).toContain('caret-transparent')
  })

  it('keeps the label in the field when the list opens', async () => {
    render(<Combobox options={options} value="3" onChange={() => {}} />)

    await userEvent.click(screen.getByRole('combobox'))

    expect(screen.getByRole('combobox')).toHaveValue('Litre')
    expect(screen.getByRole('option', { name: /Litre/ })).toHaveAttribute('aria-selected', 'true')
  })

  it('starts a fresh query on typing rather than editing the label', async () => {
    const onQueryChange = vi.fn()

    render(
      <Combobox options={options} value="3" onChange={() => {}} filter={false} onQueryChange={onQueryChange} />,
    )

    await userEvent.type(screen.getByRole('combobox'), 'ki')

    // The browser hands back "Litreki"; what was INSERTED is the query. A
    // consumer filtering server-side must never be asked to search for a label
    // it already resolved.
    expect(screen.getByRole('combobox')).toHaveValue('ki')
    expect(onQueryChange).toHaveBeenLastCalledWith('ki')
  })

  it('brings the label back when the query is deleted', async () => {
    render(<Combobox options={options} value="3" onChange={() => {}} filter={false} />)

    const field = screen.getByRole('combobox')

    await userEvent.type(field, 'k')
    expect(field).toHaveValue('k')

    await userEvent.keyboard('{Backspace}')

    // Nothing is lost by typing and changing your mind.
    expect(field).toHaveValue('Litre')
    expect(field.className).toContain('caret-transparent')
  })

  it('shows the placeholder while a chosen label has not resolved yet', () => {
    // The window a consumer that fetches selected labels by id lives in. At
    // full strength the old fallback would be a glaring `4711` in the field.
    render(<Combobox options={options} value="4711" onChange={() => {}} placeholder="Search…" />)

    expect(screen.getByRole('combobox')).toHaveValue('')
    expect(screen.getByRole('combobox')).toHaveAttribute('placeholder', 'Search…')
  })

  it('clears a single value with the ✕', async () => {
    const onChange = vi.fn()
    render(<Combobox options={options} value="3" onChange={onChange} />)

    await userEvent.click(screen.getByRole('button', { name: 'Clear Litre' }))

    expect(onChange).toHaveBeenCalledWith(null)
  })

  it('clears a single value with backspace on an empty query', async () => {
    const onChange = vi.fn()
    render(<Combobox options={options} value="3" onChange={onChange} />)

    await userEvent.click(screen.getByRole('combobox'))
    await userEvent.keyboard('{Backspace}')

    // It used to be gated on `multiple`, so a single choice was a one-way door.
    expect(onChange).toHaveBeenCalledWith(null)
  })

  it('will not let a required field be cleared, and says it is required', async () => {
    const onChange = vi.fn()
    render(<Combobox options={options} value="3" onChange={onChange} required />)

    expect(screen.getByRole('combobox')).toHaveAttribute('aria-required', 'true')
    expect(screen.queryByRole('button', { name: /^Clear/ })).toBeNull()

    await userEvent.click(screen.getByRole('combobox'))
    await userEvent.keyboard('{Backspace}')

    // Clearing it could only produce a state the form rejects.
    expect(onChange).not.toHaveBeenCalled()
  })

  it('leaves multi-select alone: chips, not a value, and no clear ✕', () => {
    render(<Combobox options={options} value={['1']} onChange={() => {}} multiple />)

    expect(screen.getByRole('combobox')).toHaveValue('')
    expect(screen.getByRole('button', { name: 'Remove Kilogram' })).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: /^Clear/ })).toBeNull()
  })

  it('announces multi-select on the listbox', async () => {
    render(<Combobox options={options} value={[]} onChange={() => {}} multiple />)

    await userEvent.click(screen.getByRole('combobox'))

    expect(screen.getByRole('listbox')).toHaveAttribute('aria-multiselectable', 'true')
  })

  it('walks the filtered list with the arrow keys', async () => {
    render(<Combobox options={options} value={null} onChange={() => {}} />)

    const field = screen.getByRole('combobox')
    await userEvent.click(field)
    await userEvent.keyboard('{ArrowDown}')

    expect(document.activeElement).toHaveAttribute('role', 'option')
  })

  it('will not choose a disabled option', async () => {
    const onChange = vi.fn()

    render(
      <Combobox
        options={[{ value: '1', label: 'Retired', disabled: true }]}
        value={null}
        onChange={onChange}
      />,
    )

    await userEvent.click(screen.getByRole('combobox'))
    await userEvent.click(screen.getByRole('option', { name: 'Retired' }))

    expect(onChange).not.toHaveBeenCalled()
  })

  /*
   * Known gap 1. The list used to be `absolute` inside the component's own
   * `relative` root, so any ancestor with `overflow: auto` clipped it — the ERP
   * consuming this had to order a dialog's fields around the limitation. These
   * two say the list has left the container and that leaving it did not break
   * choosing from it, which is the half that regresses: the outside-click
   * handler no longer sees option clicks as inside.
   */
  it('opens its list outside any scroll container that encloses it', async () => {
    render(
      <div data-testid="scroller" style={{ overflowY: 'auto', height: '80px' }}>
        <Combobox options={options} value={null} onChange={() => {}} label="Unit" />
      </div>,
    )

    await userEvent.click(screen.getByRole('combobox'))

    const scroller = screen.getByTestId('scroller')
    const listbox = screen.getByRole('listbox')

    expect(scroller).not.toContainElement(listbox)
    expect(listbox.parentElement).toBe(document.body)
  })

  it('still chooses an option from the portalled list', async () => {
    const onChange = vi.fn()

    render(
      <div style={{ overflowY: 'auto', height: '80px' }}>
        <Combobox options={options} value={null} onChange={onChange} />
      </div>,
    )

    await userEvent.click(screen.getByRole('combobox'))
    await userEvent.click(screen.getByRole('option', { name: /Metric tonne/ }))

    expect(onChange).toHaveBeenCalledWith('2')
  })

  it('replaces help with the error and marks the field invalid', () => {
    render(<Combobox options={options} value={null} onChange={() => {}} help="Pick one." error="Required." />)

    expect(screen.getByText('Required.')).toBeInTheDocument()
    expect(screen.queryByText('Pick one.')).toBeNull()
    expect(screen.getByRole('combobox')).toHaveAttribute('aria-invalid', 'true')
  })
})
