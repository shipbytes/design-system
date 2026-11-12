@props([
    /**
     * Rows hover by default because in practice almost every row in this app is
     * clickable somewhere. Pass false for a genuinely inert row — a hover
     * affordance on something that does nothing is a lie.
     */
    'hover' => true,
])

<tr {{ $attributes->class(['motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter hover:bg-surface-subtle' => $hover]) }}>
    {{ $slot }}
</tr>
