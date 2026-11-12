<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

/**
 * What the package's components are, read from the one place that decides it:
 * the views in resources/views/components. Every list and every count the docs
 * state is checked against this, so none of them is a second source of truth.
 */
final class Components
{
    /**
     * Sub-components, and the parent whose docs page and spec cover them.
     * Written out rather than derived from the name: `tab` belongs to `tabs`
     * and `radio-group` belongs to `radio`, neither of which any suffix rule
     * gets right. A new sub-component with no entry here fails, which is the
     * point.
     */
    public const PARENTS = [
        'accordion-item' => 'accordion',
        'bottom-nav-item' => 'bottom-nav',
        'breadcrumb-item' => 'breadcrumb',
        'chip' => 'chip-group',
        'command-group' => 'command-palette',
        'command-item' => 'command-palette',
        'dropdown-item' => 'dropdown',
        'filter-chip' => 'filter-bar',
        'panel-row' => 'panel',
        'radio-card' => 'radio-cards',
        'radio-group' => 'radio',
        'segment' => 'segmented-control',
        'sheet-item' => 'sheet',
        'sortable-item' => 'sortable-list',
        'split-pane' => 'split',
        'step' => 'stepper',
        'tab' => 'tabs',
        'tab-panel' => 'tabs',
        'table-cell' => 'table',
        'table-row' => 'table',
        'timeline-group' => 'timeline',
        'timeline-item' => 'timeline',
        'toast-region' => 'toast',
        'toggle' => 'toggle-group',
        'toolbar-button' => 'toolbar',
        'toolbar-separator' => 'toolbar',
        'tree-item' => 'tree',
    ];

    /** @return list<string> every component view, by its tag name */
    public static function all(): array
    {
        $names = array_map(
            fn (string $path) => basename($path, '.blade.php'),
            glob(__DIR__.'/../resources/views/components/*.blade.php'),
        );
        sort($names);

        return $names;
    }

    /** The number every stated count must equal. */
    public static function count(): int
    {
        return count(self::all());
    }

    /** The component whose page and spec cover this one: itself, or its parent. */
    public static function pageOf(string $component): string
    {
        return self::PARENTS[$component] ?? $component;
    }
}
