<?php

/**
 * Answer one HTTP request through Laravel, and print the response as JSON.
 *
 *   php scripts/request.php <url> ['{"If-None-Match": "…"}']
 *
 * The behaviour tests hand every request for http://localhost/… to this, so
 * the browser fetches the script from the package's REAL route, through the
 * HTTP kernel, its headers included, rather than from a copy of the file the
 * harness served itself. A copy would prove the script works and nothing about
 * the route ds::scripts now links.
 *
 * Livewire is booted too, which is what lets a page here be a real Livewire
 * page: Livewire injects its own script into it and serves that script from
 * its own route, so host setup (c) is driven as a host runs it.
 *
 * Pages are Blade files in DS_PAGES, served at /_ds/<name>. Dev-only, like
 * render.php; it ships nowhere.
 */

declare(strict_types=1);

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

[$url, $headers] = [$argv[1] ?? null, json_decode($argv[2] ?? '{}', true) ?: []];

if ($url === null) {
    fwrite(STDERR, "usage: php scripts/request.php <url> [headers-json]\n");
    exit(1);
}

// Its own compiled-view directory: render.php compiles the same views without
// Livewire's precompiler, and a view compiled by one is reused by the other.
// Set through the environment because the config is not loaded yet when a
// resolving callback would run, and the compiler is resolved during boot.
$compiled = sys_get_temp_dir().'/ds-request-views';
is_dir($compiled) || mkdir($compiled, 0777, true);
foreach (['VIEW_COMPILED_PATH' => $compiled, 'APP_KEY' => 'base64:'.base64_encode(str_repeat('k', 32))] as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $_SERVER[$key] = $value;
}

$app = Orchestra\Testbench\Foundation\Application::create(
    basePath: $root.'/vendor/orchestra/testbench-core/laravel',
    options: ['extra' => ['providers' => [
        BladeUI\Icons\BladeIconsServiceProvider::class,
        BladeUI\Heroicons\BladeHeroiconsServiceProvider::class,
        Shipbytes\BladeUi\BladeUiServiceProvider::class,
        \Livewire\LivewireServiceProvider::class,
    ]]],
);

foreach (glob($root.'/tests/Fixtures/Browser/*.php') as $fixture) {
    $class = 'Shipbytes\\BladeUi\\Tests\\Fixtures\\Browser\\'.basename($fixture, '.php');
    Livewire::component(Illuminate\Support\Str::kebab(basename($fixture, '.php')), $class);
}

Route::get('/_ds/{page}', function (string $page) {
    $file = getenv('DS_PAGES').'/'.basename($page).'.blade.php';
    abort_unless(is_file($file), 404);

    return view()->file($file);
});
app('router')->getRoutes()->refreshNameLookups();

$request = Request::create($url, 'GET');
foreach ($headers as $name => $value) {
    $request->headers->set($name, $value);
}

$response = $app->make(Kernel::class)->handle($request);

// Livewire serves its script as a file response, which has no content until
// it is sent.
ob_start();
$response->sendContent();
$body = ob_get_clean();

echo json_encode([
    'status' => $response->getStatusCode(),
    'headers' => array_map(fn ($values) => implode(', ', $values), $response->headers->allPreserveCaseWithoutCookies()),
    'cookies' => count($response->headers->getCookies()),
    'body' => base64_encode((string) $body),
], JSON_UNESCAPED_SLASHES);
