<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Icon set
    |--------------------------------------------------------------------------
    |
    | The <x-ds::icon> component resolves names through blade-heroicons, which
    | ships Heroicons v2. The dashboard this system came from was built against
    | v1 and picked up v2 icons later, so it runs a mix — and v2 renamed 33 of
    | the names in use. icons/icons.json in the design system carries the full
    | v1 -> v2 map; the aliases below cover the names most likely to be typed
    | from memory, so an old name resolves instead of rendering nothing.
    |
    */
    'icon_aliases' => [
        'x' => 'x-mark',
        'search' => 'magnifying-glass',
        'mail' => 'envelope',
        'cog' => 'cog-6-tooth',
        'menu' => 'bars-3',
        'refresh' => 'arrow-path',
        'logout' => 'arrow-right-on-rectangle',
        'external-link' => 'arrow-top-right-on-square',
        'dots-vertical' => 'ellipsis-vertical',
        'location-marker' => 'map-pin',
        'photograph' => 'photo',
        'eye-off' => 'eye-slash',
        'duplicate' => 'square-2-stack',
        'pencil-alt' => 'pencil-square',
        'exclamation' => 'exclamation-triangle',
        'view-grid' => 'squares-2x2',
        'template' => 'rectangle-group',
        'collection' => 'rectangle-stack',
        'badge-check' => 'check-badge',
        'filter' => 'funnel',
        'upload' => 'arrow-up-tray',
        'support' => 'lifebuoy',
        'globe' => 'globe-americas',
        'chat' => 'chat-bubble-oval-left',
        'reply' => 'arrow-uturn-left',
        'document-download' => 'document-arrow-down',
        'color-swatch' => 'swatch',
        'office-building' => 'building-office',
        'switch-horizontal' => 'arrows-right-left',
        'adjustments' => 'adjustments-horizontal',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default icon size
    |--------------------------------------------------------------------------
    |
    | 16px matches the dashboard's dominant `size-4`. Icons inherit currentColor
    | so they take their colour from the text around them.
    |
    */
    'icon_size' => 4,
];
