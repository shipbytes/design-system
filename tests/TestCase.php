<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use BladeUI\Icons\BladeIconsServiceProvider;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Shipbytes\BladeUi\BladeUiServiceProvider;

/**
 * Boots a minimal Laravel through testbench so the components can actually be
 * rendered. The repo is not a Laravel app, which is precisely why every test it
 * had until now checked tokens instead of components.
 */
abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeUiServiceProvider::class,
        ];
    }
}
