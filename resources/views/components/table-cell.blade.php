@props([
    'align' => 'left',
    /** Stop the cell wrapping. Right for dates and counts, wrong for prose. */
    'nowrap' => false,
])

<td @class([
    'px-4 py-3 text-body text-fg-body',
    'text-left' => $align === 'left',
    'text-center' => $align === 'center',
    'text-right' => $align === 'right',
    'whitespace-nowrap' => $nowrap,
]) {{ $attributes }}>
    {{ $slot }}
</td>
