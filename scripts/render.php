<?php

/**
 * Render a Blade file, with nothing between Blade and stdout.
 *
 *   php scripts/render.php <path-to-blade-file>
 *
 * This is what removed the "you need a throwaway Laravel app" step from every
 * script in this repo. Testbench already boots a Laravel for the render tests;
 * it can boot one here too, so the gallery, the documentation screenshots and
 * the behaviour tests all render through the same three lines and a clean clone
 * needs no host application at all.
 *
 * NOT `php artisan tinker --execute`, which was the previous approach: PsySH
 * rewrites the markup on its way out, turning `...` into `..`, which breaks
 * every spread operator in a component. See CLAUDE.md.
 */

declare(strict_types=1);

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$file = $argv[1] ?? null;

if ($file === null || ! is_file($file)) {
    fwrite(STDERR, "usage: php scripts/render.php <path-to-blade-file>\n");
    exit(1);
}

$app = Orchestra\Testbench\Foundation\Application::create(
    basePath: $root.'/vendor/orchestra/testbench-core/laravel',
    options: ['extra' => ['providers' => [
        BladeUI\Icons\BladeIconsServiceProvider::class,
        BladeUI\Heroicons\BladeHeroiconsServiceProvider::class,
        Shipbytes\BladeUi\BladeUiServiceProvider::class,
    ]]],
);

// A canary for anything that rewrites the markup between Blade and here. It
// costs one string and it is the reason the spread-operator bug cannot come
// back unnoticed.
echo '<!--ds-canary [...ok]-->';

echo $app->make('view')->file($file)->render();
