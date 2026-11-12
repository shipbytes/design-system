{{--
    ds::tree. Its props are the constructor of
    Shipbytes\BladeUi\View\Components\Tree (label, selectable, name), which
    also hands the items their mode and field name: see that class for why
    this component is not anonymous.

    The items have rendered by the time this runs, each numbered among its
    own siblings by its parent (Support\Tree). Here the top level is
    numbered, a checkbox tree's parents read off their leaves, the one tab
    stop placed, and the single selection's hidden input given the value its
    selected item wrote. dsTree does everything
    that moves: the keys, opening and closing, selecting and checking.
--}}
@php
    use Shipbytes\BladeUi\Support\Tree;

    $items = Tree::stop(Tree::derive(Tree::top((string) $slot)));

    $isModel = fn ($value, $key) => str_starts_with($key, 'wire:model') || str_starts_with($key, 'x-model');
    $model = $attributes->filter($isModel);
    $bound = $model->getAttributes() !== [];

    if ($bound && $selectable === 'none') {
        throw new \InvalidArgumentException(
            "ds::tree \"{$label}\" has a wire:model or x-model and nothing to choose: give it selectable=\"single\" or \"multiple\"."
        );
    }

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsTree', ['select' => $selectable]);
@endphp

<div
    x-data="{{ $state }}"
    @if ($bound) x-modelable="dsValue" {{ $model }} @endif
    data-ds-tree
    {{ $attributes->filter(fn ($value, $key) => ! $isModel($value, $key))->except(['aria-label'])->merge(['class' => 'min-w-0']) }}
>
    <ul
        role="tree"
        aria-label="{{ $label }}"
        @if ($selectable === 'multiple') aria-multiselectable="true" @endif
        data-ds-tree-select="{{ $selectable }}"
        class="flex flex-col gap-px"
    >{{ new \Illuminate\Support\HtmlString($items) }}</ul>

    @if ($selectable === 'single' && $name)
        <input type="hidden" name="{{ $name }}" value="{{ Tree::selected($items) ?? '' }}" data-ds-tree-field>
    @endif
</div>
