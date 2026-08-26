<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the design system's Blade components under the `ds` namespace,
 * so they read as <x-ds::button> and can never collide with an application's
 * own components.
 *
 * The components are presentation only. They carry no state, dispatch no
 * events and assume no Livewire — anything reactive is passed in by the host
 * application through attributes, which is what keeps the same markup usable
 * from a plain Blade view, a Livewire component, or a Volt page.
 */
final class BladeUiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/blade-ui.php', 'blade-ui');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'ds');

        Blade::componentNamespace('Shipbytes\\BladeUi\\View\\Components', 'ds');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/blade-ui.php' => config_path('blade-ui.php'),
            ], 'blade-ui-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/ds'),
            ], 'blade-ui-views');
        }
    }
}
