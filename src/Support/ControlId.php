<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

use Illuminate\View\ComponentAttributeBag;

/**
 * The id a form control gets when the host does not pass one.
 *
 * It has to satisfy two hosts that want opposite things.
 *
 * A plain Blade page wants it UNIQUE. Two fields named "email" labelled "Email",
 * a sign-in form and a newsletter box, must not share an id, or the second
 * label focuses the first field. So an unbound control keeps a random id.
 *
 * Livewire wants it STABLE. A control bound with wire:model is rendered again
 * on every request, and Livewire's morph matches elements by id. A new random
 * id on each render made it REPLACE the field instead of updating it, and a
 * replaced field loses focus and caret: a live search kept the first burst of
 * typing and dropped everything after its first response. Nothing errored. So
 * a bound control's id is a hash of what makes it that control, the binding,
 * name, value and label, and comes out the same on every render.
 *
 * Two bound controls that agree on all four are the same field twice; pass an
 * `id` to tell them apart.
 */
final class ControlId
{
    public static function for(ComponentAttributeBag $attributes, ?string $label = null, ?string $name = null): string
    {
        if ($id = $attributes->get('id')) {
            return (string) $id;
        }

        $name ??= (string) $attributes->get('name', '');

        $binding = null;

        foreach ($attributes->getAttributes() as $key => $value) {
            if (str_starts_with((string) $key, 'wire:model')) {
                $binding = (string) $value;
                break;
            }
        }

        $seed = $binding === null
            ? $name.$label.uniqid('', true)
            : implode('|', [$binding, $name, (string) $attributes->get('value', ''), (string) $label]);

        return 'ds-'.substr(md5($seed), 0, 8);
    }
}
