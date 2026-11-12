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

        // Scoped, not a singleton: Octane and queue workers flush scoped
        // instances between requests, so "once per request" stays true there.
        $this->app->scoped('blade-ui.missing-icons', fn () => new \ArrayObject());
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'ds');

        Blade::componentNamespace('Shipbytes\\BladeUi\\View\\Components', 'ds');

        $this->registerScriptRoute();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/blade-ui.php' => config_path('blade-ui.php'),
            ], 'blade-ui-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/ds'),
            ], 'blade-ui-views');
        }
    }

    /**
     * The route ds::scripts links: GET {prefix}/blade-ui.{hash}.js.
     *
     * Registered straight on the router, in no group, so none of the `web`
     * group's middleware runs: no session, no cookie, no CSRF, no auth. A
     * script tag in the head of a login page must load for a guest.
     *
     * Skipped when the routes are cached, because `route:cache` already
     * captured it from this same method, controller and name included.
     */
    private function registerScriptRoute(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $prefix = trim((string) config('blade-ui.script.prefix', 'blade-ui'), '/');

        $this->app['router']
            ->get(ltrim($prefix.'/blade-ui.{hash}.js', '/'), Http\ScriptController::class)
            ->where('hash', '[A-Za-z0-9]+')
            ->name('blade-ui.script');
    }
}
