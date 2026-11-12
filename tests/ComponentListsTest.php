<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as PlainTestCase;

/**
 * The lists and the counts the documentation states, held to the views.
 *
 * Batch after batch shipped a component and left one of the lists behind:
 * docs/README.md's table, which is the docs site's sidebar, or specs/README.md,
 * or the count in composer.json that Packagist shows as the whole description.
 * Each was corrected by hand the next time somebody noticed. Now the views are
 * the one source (Components::all()) and every list and count is read against
 * them.
 *
 * A stated total is written "N Blade components" or "N accessible components"
 * (digits or words), or "Built — N components" in CLAUDE.md. That phrase is
 * how this test finds it, so a total written any other way is a total nothing
 * checks; smaller counts ("twelve components hide themselves") never use it.
 */
final class ComponentListsTest extends PlainTestCase
{
    private const ROOT = __DIR__.'/..';

    #[Test]
    public function every_component_has_a_row_in_the_docs_index(): void
    {
        $this->assertSame([], $this->unlisted(
            file_get_contents(self::ROOT.'/docs/README.md'),
            fn (string $page) => "](components/{$page}.md)",
        ), 'docs/README.md is the docs site\'s sidebar: a component with no row there cannot be found');
    }

    #[Test]
    public function every_component_has_a_row_in_the_specs_index(): void
    {
        $this->assertSame([], $this->unlisted(
            file_get_contents(self::ROOT.'/specs/README.md'),
            fn (string $page) => "]({$page}.md)",
        ), 'specs/README.md has no row for these');
    }

    #[Test]
    public function every_component_is_named_in_the_readme_and_in_claude_md(): void
    {
        $readme = $this->between(file_get_contents(self::ROOT.'/README.md'), '### What you get', 'Wire pagination up');
        $state = $this->between(file_get_contents(self::ROOT.'/CLAUDE.md'), '**Built —', '**Every built component has a spec.**');

        $missing = [];

        foreach (Components::all() as $component) {
            if (! str_contains($readme, "`{$component}`")) {
                $missing[] = "README.md \"What you get\" does not name `{$component}`";
            }

            if (! preg_match('/(?<![\w-])'.preg_quote($component, '/').'(?![\w-])/', $state)) {
                $missing[] = "CLAUDE.md's State list does not name {$component}";
            }
        }

        $this->assertSame([], $missing, implode("\n", $missing));
    }

    #[Test]
    public function every_stated_component_count_is_the_real_one(): void
    {
        $real = Components::count();

        $files = [
            'README.md' => file_get_contents(self::ROOT.'/README.md'),
            'CLAUDE.md' => file_get_contents(self::ROOT.'/CLAUDE.md'),
            'composer.json (description)' => json_decode(file_get_contents(self::ROOT.'/composer.json'), true)['description'],
        ];

        foreach (glob(self::ROOT.'/docs/{,*/}*.md', GLOB_BRACE) as $path) {
            $files[substr($path, strlen(self::ROOT) + 1)] = file_get_contents($path);
        }

        $stated = [];
        $wrong = [];

        foreach ($files as $where => $text) {
            preg_match_all(
                '/\b([0-9]+|[A-Za-z]+(?:-[a-z]+)?)\s+(?:accessible|Blade)\s+components\b|Built\s+—\s+([0-9]+)\s+components\b/u',
                $text,
                $matches,
                PREG_SET_ORDER,
            );

            foreach ($matches as $match) {
                $number = self::number($match[2] ?? '' ?: $match[1]);

                if ($number === null) {
                    continue;
                }

                $stated[] = $where;

                if ($number !== $real) {
                    $wrong[] = "{$where}: \"{$match[0]}\" — there are {$real} (".self::words($real).')';
                }
            }
        }

        // The ones that must exist: Packagist shows nothing but the description,
        // and these three are where a reader looks first.
        foreach (['composer.json (description)', 'README.md', 'docs/README.md', 'CLAUDE.md'] as $required) {
            if (! in_array($required, $stated, true)) {
                $wrong[] = "{$required} states no component count in the form this test reads";
            }
        }

        $this->assertSame([], $wrong, implode("\n", $wrong));
    }

    #[Test]
    public function the_number_reader_reads_what_the_docs_write(): void
    {
        // The count check is only as good as this. Proven on the forms the
        // docs use, so a pass above means the counts were read.
        $this->assertSame(73, self::number('Seventy-three'));
        $this->assertSame(77, self::number('77'));
        $this->assertSame(100, self::number('one-hundred'));
        $this->assertNull(self::number('the'));
        $this->assertSame('seventy-seven', self::words(77));
        $this->assertSame('eighty', self::words(80));
    }

    /** @param  callable(string): string  $link */
    private function unlisted(string $index, callable $link): array
    {
        $rows = implode("\n", array_filter(explode("\n", $index), fn ($line) => str_starts_with(ltrim($line), '|')));

        $missing = [];

        foreach (Components::all() as $component) {
            $page = Components::pageOf($component);

            if (! str_contains($rows, $link($page))) {
                $missing[] = $page === $component ? $component : "{$component} (through {$page})";
            }
        }

        return array_values(array_unique($missing));
    }

    private function between(string $text, string $start, string $end): string
    {
        $from = strpos($text, $start);
        $this->assertNotFalse($from, "the section starting \"{$start}\" is gone");
        $to = strpos($text, $end, $from);
        $this->assertNotFalse($to, "the section ending \"{$end}\" is gone");

        return substr($text, $from, $to - $from);
    }

    private const UNITS = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten',
        'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];

    private const TENS = [2 => 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

    private static function number(string $token): ?int
    {
        if (ctype_digit($token)) {
            return (int) $token;
        }

        $token = strtolower($token);

        for ($n = 0; $n <= 199; $n++) {
            if (self::words($n) === $token || str_replace('one-hundred', 'one hundred', self::words($n)) === $token) {
                return $n;
            }
        }

        return null;
    }

    private static function words(int $n): string
    {
        if ($n >= 100) {
            return 'one-hundred'.($n > 100 ? '-'.self::words($n - 100) : '');
        }

        if ($n < 20) {
            return self::UNITS[$n];
        }

        return self::TENS[intdiv($n, 10)].($n % 10 ? '-'.self::UNITS[$n % 10] : '');
    }
}
