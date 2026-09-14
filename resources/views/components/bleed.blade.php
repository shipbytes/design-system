{{--
    The way out of a panel body's inset, for something that must touch the edge.

    A divided list is the case this exists for: a row's hover highlight and the
    divider under it stop looking deliberate the moment they stop short of the
    panel's border. Rows inside still carry their own padding — `<x-panel-row>`
    does it, and a hand-written row uses the same `px-5 sm:px-6` — so the text
    goes on lining up with the heading while the background does not.

        <x-panel title="On the bridge" icon="scale">
            <x-toolbar />
            <x-bleed>
                <ul class="divide-y divide-divider">…</ul>
            </x-bleed>
        </x-panel>

    The margins negate `panel.blade.php`'s inset exactly. That is only possible
    because there is one inset to negate.
--}}
<div {{ $attributes->merge(['class' => '-mx-5 sm:-mx-6']) }}>
    {{ $slot }}
</div>
